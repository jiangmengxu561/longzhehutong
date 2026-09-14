<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use think\Db;
use think\Log;
use app\admin\library\FranchiseService;

/**
 * 司机接口
 */
class Dricer extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 司机首页
     *
     */
    /**
     * 司机首页订单列表
     */
    public function OrderList()
    {
        $user = $this->auth->getUserinfo();

        if (!$user || !isset($user['id'])) {
            $this->error('请登录');
        }

        $userId = $user['id'];
        $searchTime = $this->request->param('searchtime', 0, 'intval');
        $address = trim((string)$this->request->param('address'));
        $needCityFilter = $address !== '';
        $normalizedCity = $address;
        if ($normalizedCity !== '' && mb_substr($normalizedCity, -1) !== '市') {
            $normalizedCity .= '市';
        }
        $cityCenter = $this->resolveCityCenter($normalizedCity);
        // 基础查询 - 只查询配车订单
        $query = Db::name('order')
            ->field('id, earliest_time, orderid, latest_time, loading, unload, createtime, find_car_type, direction, packaging_id, goods_type_id, pickup_driver_fee, shipment_driver_fee, logistics_id, type,pickup_distance,logistics_distance,shipmenty_distance')
            ->where('logistics_status', 1)
            ->where('find_car_type', '配车')
//            ->where('status', '<>', 0) // 添加有效订单状态检查
            ->order('createtime desc');

        // 时间筛选逻辑
        $todayStart = strtotime(date('Y-m-d'));
        $tomorrowStart = strtotime('+1 day', $todayStart);
        $dayAfterTomorrowStart = strtotime('+2 day', $todayStart);

        if ($searchTime == 1) {
            // 今天
            $query->whereBetween('earliest_time', [$todayStart, $tomorrowStart - 1]);
        } elseif ($searchTime == 2) {
            // 明天
            $query->whereBetween('earliest_time', [$tomorrowStart, $dayAfterTomorrowStart - 1]);
        } elseif ($searchTime == 3) {
            // 后天以后
            $query->where('earliest_time', '>=', $dayAfterTomorrowStart);
        }

        $orders = $query->select();

        $orderList = [];
   ;
        foreach ($orders as $order) {
            // 默认type为1（取货司机）
            $orderType = 1;
            $orderId = $order['orderid'];

            // 检查专线记录
            $lineOrder = Db::name('dricerorder')
                ->where('order_id', $orderId)
                ->where('type', 2)
                ->where('status', 1)
                ->find();

            if ($lineOrder) {
                $orderType = 1; // 专线已抢单，取货司机可见
            }

            // 检查送货专线记录
            $songlineOrder = Db::name('dricerorder')
                ->where('order_id', $orderId)
                ->where('type', 2)
                ->where('status', 2)
                ->find();

            if ($songlineOrder) {
                $orderType = 3; // 专线已到达，送货司机可见
            }


            if ($orderType == 1) {
                if (!$lineOrder) {
                    continue;
                }
            } elseif ($orderType == 3) {
                if (!$songlineOrder) {

                    continue;
                }
            }

            // 检查当前司机是否已抢过该订单的当前类型
            $existsDriverOrder = Db::name('dricerorder')
                ->where('order_id', $orderId)
                ->where('d_id', $userId)
//                ->where('type', $orderType)
                ->find();

            if ($existsDriverOrder) {
                // 当前司机已抢过该类型的订单，不再展示
                continue;
            }

            // 检查是否有其他司机抢过该订单的当前类型
            $otherDriverOrder = Db::name('dricerorder')
                ->where('order_id', $orderId)
                ->where('type', $orderType)
                ->where('status', '>', 0)
                ->find();

            if ($otherDriverOrder) {
                // 已有其他司机抢过该类型订单，不再展示
                continue;
            }

            // 设置订单类型
            $order['type'] = $orderType;
            $orderList[] = $order;
        }
//        print_r($orderList);die;
        // 组装展示数据
        $now = time();
        $tomorrowStr = date('Y-m-d', strtotime('+1 day'));
        foreach ($orderList as $idx => &$order) {
            // 获取物流信息
            $logisticsData = Db::name('logistics')
                ->where('id', $order['logistics_id'])
                ->find();

            if (!$logisticsData) {
                continue; // 物流信息不存在则跳过
            }
            // 如果传入城市，则限定城市中心200公里范围（优先用用户下单地址坐标）
            if ($cityCenter) {
                $centerLat = $cityCenter['lat'];
                $centerLng = $cityCenter['lng'];
                $targetLat = null;
                $targetLng = null;

                if ((int)$order['type'] === 1) {
                    // 取货司机：使用用户下单的提货地址坐标
                    $loading = Db::name('user_address')->where('id', $order['loading'])->find();
                    $targetLat = $loading['lat'] ?? null;
                    $targetLng = $loading['lng'] ?? null;
                    // 回退到物流发货中心坐标
                    if (empty($targetLat) || empty($targetLng)) {
                        $targetLat = $logisticsData['shipping_latitude'] ?? null;
                        $targetLng = $logisticsData['shipping_longitude'] ?? null;
                    }
                } else {
                    // 送货司机：使用用户下单的收货地址坐标
                    $unload = Db::name('user_address')->where('id', $order['unload'])->find();
                    $targetLat = $unload['lat'] ?? null;
                    $targetLng = $unload['lng'] ?? null;
                    // 回退到物流到达中心坐标
                    if (empty($targetLat) || empty($targetLng)) {
                        $targetLat = $logisticsData['arrival_latitude'] ?? null;
                        $targetLng = $logisticsData['arrival_longitude'] ?? null;
                    }
                }

                if (empty($targetLat) || empty($targetLng)) {
                    unset($orderList[$idx]); // 无法判断距离的订单直接剔除
                    continue;
                }

                $distanceToJinan = calculateDistance(
                    (float)$targetLat,
                    (float)$targetLng,
                    $centerLat,
                    $centerLng
                );

                if ($distanceToJinan > 200) {
                    unset($orderList[$idx]); // 超出范围剔除
                    continue;
                }
            } elseif ($needCityFilter) {
                // 地理编码失败时，用地址字符串兜底匹配
                $cityNeedle = $normalizedCity;
                $candidateTexts = [];
                // 物流中心地址
                $candidateTexts[] = (string)($logisticsData['shipping_logistics_address'] ?? '');
                $candidateTexts[] = (string)($logisticsData['arrival_logistics_address'] ?? '');
                // 用户下单地址
                $candidateTexts[] = (string)Db::name('user_address')->where('id', $order['loading'])->value('address');
                $candidateTexts[] = (string)Db::name('user_address')->where('id', $order['unload'])->value('address');

                $matched = false;
                foreach ($candidateTexts as $txt) {
                    if ($txt !== '' && mb_strpos($txt, $cityNeedle) !== false) {
                        $matched = true;
                        break;
                    }
                } 
                if (!$matched) {
                    unset($orderList[$idx]); // 未匹配到城市则剔除
                    continue;
                }
            }
            // 格式化时间
            $earliestTimestamp = $order['earliest_time'];
            $latestTimestamp = $order['latest_time'];
            if ($earliestTimestamp <= 0 || $latestTimestamp <= 0) {
                continue; // 时间无效则跳过
            }
            $earliestTime = date('H:i', $earliestTimestamp);
            $earliestDay = date('Y-m-d', $earliestTimestamp);
            $latestTime = date('H:i', $latestTimestamp);
            // 判断是否是明天
            if ($earliestDay === $tomorrowStr) {
                $formattedTime = "明天 " . $earliestTime . "-" . $latestTime;
            } else {
                $formattedTime = $earliestDay . " " . $earliestTime . "-" . $latestTime;
            }
            // 计算发布时间
            $timeDiff = $now - $order['createtime'];
            if ($timeDiff < 60) {
                $order['thistime'] = '刚刚';
            } elseif ($timeDiff < 3600) {
                $order['thistime'] = intval($timeDiff / 60) . '分钟前';
            } else {
                $order['thistime'] = intval($timeDiff / 3600) . '小时前';
            }

            // 处理方向
            $order['direction'] = ($order['direction'] ?? '') . '方';
            // 根据司机类型处理地址和价格
            $loadingAddress = '';
            $unloadAddress = '';
            if ($order['type'] == 1) {
                // 取货司机
                $loadingAddress = Db::name('user_address')
                    ->where('id', $order['loading'])
                    ->value('address');
                $unloadAddress = $logisticsData['shipping_logistics_address'] ?? '';
                $order['price'] = $order['pickup_driver_fee'] ?? 0;
                $order['distance'] = $order['pickup_distance'] ?? 0;
            } else {
                // 送货司机
                $loadingAddress = $logisticsData['arrival_logistics_address'] ?? '';

                $unloadAddress = Db::name('user_address')
                    ->where('id', $order['unload'])
                    ->value('address');

                $order['price'] = $order['shipment_driver_fee'] ?? 0;
                $order['distance'] = $order['shipment_distance'] ?? 0;

            }

            // 获取货物类型和包装信息
            $goodsType = Db::name('goods_type')
                ->where('id', $order['goods_type_id'])
                ->value('name');

            $packaging = Db::name('packaging')
                ->where('id', $order['packaging_id'])
                ->value('name');

            // 组装最终数据
            $order['loading_address'] = $loadingAddress ?: '地址信息缺失';
            $order['unload_address'] = $unloadAddress ?: '地址信息缺失';
            $order['time_range'] = $formattedTime;
            $order['createtime'] = date('Y-m-d H:i:s', $order['createtime']);
            $order['goods_type_id'] = $goodsType ?: '未知';
            $order['packaging_id'] = $packaging ?: '未知';
            $order['order_status'] = '待抢单'; // 添加订单状态
        } 
        // 移除无效的订单引用
        $orderList = array_values($orderList);
        if (!empty($orderList)) {
            $this->success('查询成功', $orderList);
        } else {
            $this->error('暂无订单');
        }
    }

    /**
     * 提取地址中的省市信息
     */
    private function extractProvinceCity($address)
    {
        // 简单的省市提取逻辑，可根据实际需求调整
        if (preg_match('/([^省]+省|[^自治区]+自治区|[^市]+市)?/u', $address, $matches)) {
            return $matches[1] . ($matches[2] ?? '');
        }
        return mb_substr($address, 0, 10);
    }

    /**
     * 将传入的城市名称解析为经纬度（百度地理编码）
     *
     * @param string $cityName
     * @return array|null ['lat'=>float,'lng'=>float] 失败返回 null
     */
    private function resolveCityCenter($cityName)
    {
        $cityName = trim((string)$cityName);
        if ($cityName === '') {
            return null;
        }
        // 兼容传入类似"济南"或"济南市"，统一补齐"市"后再查询
        if (mb_substr($cityName, -1) !== '市') {
            $cityName .= '市';
        }
        
        // 从配置文件读取AK列表
        $configAk = Config::get('site.BaiduKey');

        // 初始化AK列表
        $akList = [];
        if ($configAk) {
            // 如果是JSON字符串，解析为数组
            if (is_string($configAk)) {
                $decoded = json_decode($configAk, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $akList = $decoded;
                }
            } elseif (is_array($configAk)) {
                $akList = $configAk;
            }
        }
        
        // 如果没有配置AK，使用默认AK
        if (empty($akList)) {
            $akList = ['default' => 'T6fLs4Xa9Hj16REmRNyeY20ZU5ODkpV2'];
        }

        // 记录当前使用的AK索引
        static $currentAkIndex = 0;
        $akKeys = array_keys($akList);

        // 尝试所有AK，直到成功或全部失败
        $maxRetries = count($akList);

        for ($i = 0; $i < $maxRetries; $i++) {
            $currentKey = $akKeys[$currentAkIndex];
            $ak = $akList[$currentKey];
            $url = "http://api.map.baidu.com/geocoding/v3/?address=" . urlencode($cityName) . "&output=json&ak=" . $ak;

            try {
                // 调用API
                $result = @file_get_contents($url);

                if ($result === FALSE) {
                    // 网络请求失败，记录并尝试下一个AK
//                    Log::error("百度地图API网络请求失败，AK：" . $currentKey);
                    $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                    continue;
                }

                $data = json_decode($result, true);

                if ($data['status'] == 0 && isset($data['result']['location'])) {
                    // 成功获取坐标
                    $location = $data['result']['location'];
                    return [
                        'lat' => (float)$location['lat'],
                        'lng' => (float)$location['lng'],
                    ];
                } elseif ($data['status'] == 1 || $data['status'] == 101 || $data['status'] == 302 || $data['status'] == 401) {
                    // AK权限或额度问题：status=1(服务内部错误)、status=101(AK无效/无权限)、status=302(天配额超限)、status=401(并发超限)
//                    Log::warning("百度地图AK额度可能用尽，AK：" . $currentKey . "，状态码：" . $data['status']);
                    $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                    continue; // 尝试下一个AK
                } else {
                    // 其他错误（地址解析失败等）
//                    Log::error("百度地图API调用失败，城市：{$cityName}，AK：" . $currentKey . "，返回：" . json_encode($data));
                    break; // 如果是地址问题，不需要切换AK重试
                }
            } catch (\Exception $e) {
//                Log::error("获取坐标异常，AK：" . $currentKey . "，错误：" . $e->getMessage());
                $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                continue;
            }
        }

        // 所有AK都尝试失败
//        Log::error("所有百度地图AK都尝试失败，城市：{$cityName}");
        return null;
    }


    /**
     * @return void
     * 司机首页订单详情
     */
    public  function OrderlistDetail()
    {
        $user = $this->auth->id;

        $order_id = $this->request->param();
        if (empty($order_id)){
            $this->error('信息不完整');
        }
        $OrderData = DB::name('order')->where('orderid',$order_id['order_id'])->find();
        if (empty($OrderData)){
            $this->error('订单不存在');
        }
        $earliest_time =  date(' H:i',$OrderData['earliest_time']);
        $earliest_day =  date('Y-m-d',$OrderData['earliest_time']);
        $latest_time =  date('H:i',$OrderData['latest_time']);
        $now = new \DateTime();
        $tomorrow = clone $now;
        $tomorrow->modify('+1 day');

        if ($earliest_day === $tomorrow->format('Y-m-d')) {
            $formatted_time = "明天 " . $earliest_time . "-" . $latest_time;
        } else {
            $formatted_time = $earliest_day . " " . $earliest_time . "-" . $latest_time;
        }
        $OrderData['time_range'] = $formatted_time;
        $dricerOrder = Db::name('dricerorder')
            ->where('order_id',$order_id['order_id'])
            ->where('type',1)
            ->find();
        $logisticsdata = Db::name('logistics')->where('id',$OrderData['logistics_id'])->find();
        if ($dricerOrder){
            // 处理 loading 地址
            $OrderData['unload_address']  = $logisticsdata['arrival_logistics_address'];
            $OrderData['unload_mobile']  = $logisticsdata['arrival_logistics_mobile'];
            // 处理 unload 地址
            $unload = Db::name('user_address')->where('id', $OrderData['unload'])->find();
//            print_r($unload);die;
            $OrderData['loading_address']  = $unload['address'];
            $OrderData['loading_mobile']  = $unload['mobile'];
            $OrderData['price'] = $OrderData['shipment_driver_fee'];
        }else{
            // 处理 loading 地址
            $loading= Db::name('user_address')->where('id', $OrderData['loading'])->find();
            $OrderData['loading_address']  = $loading['address'];
            $OrderData['loading_mobile']  = $loading['mobile'];
            $OrderData['loading_name']  = $loading['user_name'];
            // 处理 unload 地址
            $OrderData['unload_address']  = $logisticsdata['shipping_logistics_address'];
            $OrderData['unload_mobile']  = $logisticsdata['shipping_logistics_mobile'];
            $OrderData['unload_name']  = $logisticsdata['shipping_logistics_mobile'];
            $OrderData['price'] = $OrderData['pickup_driver_fee'];
        }
        $OrderData['goods_type_id'] = Db::name('goods_type')->where('id',$OrderData['goods_type_id'])->value('name');
        $OrderData['packaging_id'] = Db::name('packaging')->where('id',$OrderData['packaging_id'])->value('name');
        if($OrderData['pay_type'] == 1){
            $OrderData['pay_type'] = '到付';
        }else{
            $OrderData['pay_type'] = '寄付';
        }
        if($OrderData['delivery'] == 1){
            $OrderData['delivery'] = '代收货款';
        }else{
            $OrderData['delivery'] = '不代收货款';
        }
        $OrderData['delivery_type_id'] = Db::name('delivery_type')->where('id',$OrderData['delivery_type_id'])->value('name');
        $OrderData['receipt_type_id']  = Db::name('receipt_type')->where('id',$OrderData['receipt_type_id'])->value('name');
        $OrderData['unpack_id'] = Db::name('unpack')->where('id',$OrderData['unpack_id'])->value('name');
        $OrderData['other_id'] = Db::name('other')->where('id',$OrderData['other_id'])->value('name');
        if($OrderData['text_message'] == 1){
            $OrderData['text_message'] = '短信通知收件方';
        }else{
            $OrderData['text_message'] = '短信通知发件方';
        }
        if($OrderData['pay_status'] == 1){
            $OrderData['pay_status'] = '待付款';
        }elseif ($OrderData['pay_status'] == 2){
            $OrderData['pay_status'] = '进行中';
        }elseif ($OrderData['pay_status'] == 3){
            $OrderData['pay_status'] = '已完成';
        }elseif ($OrderData['pay_status'] == 4){
            $OrderData['pay_status'] = '已取消';
        }elseif ($OrderData['pay_status'] == 5){
            $OrderData['pay_status'] = '待下单';
        }
        $OrderData['car_type_id'] = Db::name('car_type')->where('id',$OrderData['car_type_id'])->value('name');
        $OrderData['earliest_time'] = date('Y-m-d H:i:s',$OrderData['earliest_time']);
        $OrderData['latest_time'] = date('Y-m-d H:i:s',$OrderData['latest_time']);
        $OrderData['sizeList'] = Db::name('dimensions')->where('order_id',$OrderData['orderid'])->select();
        $OrderData['packaging_list'] = Db::name('packaging_num')->where('order_id',$OrderData['orderid'])->select();
//        $OrderData['status'] = $OrderData['status'];
        if ($OrderData){
            $this->success('订单详情查看',$OrderData);
        }else{
            $this->error('订单详情查看失败');
        }
    }

    /**
     * @return void
     * 司机订单详情
     */
    public  function OrderlistOrderDetail()
    {
        $user = $this->auth->id;
        $order_id = $this->request->param();

        if (empty($order_id)){
            $this->error('信息不完整');
        }
        $OrderData = DB::name('order')->where('orderid',$order_id['order_id'])->find();
        if (empty($OrderData)){
            $this->error('订单不存在');
        }
        $earliest_time =  date(' H:i',$OrderData['earliest_time']);
        $earliest_day =  date('Y-m-d',$OrderData['earliest_time']);
        $latest_time =  date('H:i',$OrderData['latest_time']);
        $now = new \DateTime();
        $tomorrow = clone $now;
        $tomorrow->modify('+1 day');

        if ($earliest_day === $tomorrow->format('Y-m-d')) {
            $formatted_time = "明天 " . $earliest_time . "-" . $latest_time;
        } else {
            $formatted_time = $earliest_day . " " . $earliest_time . "-" . $latest_time;
        }
        $OrderData['time_range'] = $formatted_time;
        $dricerOrder = Db::name('dricerorder')
            ->where('order_id',$order_id['order_id'])
            ->where('d_id',$user)
            ->where('type',$order_id['type'])
            ->find();
        if (empty($dricerOrder)){
            $this->error('订单有误');
        }
        $logisticsdata = Db::name('logistics')->where('id',$OrderData['logistics_id'])->find();
        if ($dricerOrder['type'] == 3){
            // 处理 loading 地址
            $OrderData['loading_address']  = $logisticsdata['arrival_logistics_address'];
            $OrderData['loading_mobile']  = $logisticsdata['arrival_logistics_mobile'];
            // 处理 unload 地址
            $unload = Db::name('user_address')->where('id', $OrderData['unload'])->find();
//            print_r($unload);die;
            $OrderData['unload_address']  = $unload['address'];
            $OrderData['unload_mobile']  = $unload['mobile'];
            $OrderData['price'] = $OrderData['shipment_driver_fee'];
            $OrderData['otherprice'] = Db::name('dirverother')
                ->where('order_id',$order_id['order_id'])
                ->where('type',2)
                ->select();
            $otherprice = 0;
            foreach ($OrderData['otherprice'] as &$value){
                $otherprice += $value['price'];
            }
            $OrderData['totalprice'] = $OrderData['price'] + $otherprice;
        }else{
            // 处理 loading 地址
            $loading= Db::name('user_address')->where('id', $OrderData['loading'])->find();
            $OrderData['loading_address']  = $loading['address'];
            $OrderData['loading_mobile']  = $loading['mobile'];
            $OrderData['loading_name']  = $loading['user_name'];
            // 处理 unload 地址
            $OrderData['unload_address']  = $logisticsdata['shipping_logistics_address'];
            $OrderData['unload_mobile']  = $logisticsdata['shipping_logistics_mobile'];
            $OrderData['unload_name']  = $logisticsdata['shipping_logistics_mobile'];
            $OrderData['price'] = $OrderData['pickup_driver_fee'];

            $OrderData['otherprice'] = Db::name('dirverother')
                ->where('order_id',$order_id['order_id'])
                ->where('type',1)
                ->select();
            $otherprice = 0;
            foreach ($OrderData['otherprice'] as &$value){
                $otherprice += $value['price'];
            }
            $OrderData['totalprice'] = $OrderData['price'] + $otherprice;
        }
        $OrderData['goods_type_id'] = Db::name('goods_type')->where('id',$OrderData['goods_type_id'])->value('name');
        $OrderData['packaging_id'] = Db::name('packaging')->where('id',$OrderData['packaging_id'])->value('name');
        if($OrderData['pay_type'] == 1){
            $OrderData['pay_type'] = '到付';
        }else{
            $OrderData['pay_type'] = '寄付';
        }
        if($OrderData['delivery'] == 1){
            $OrderData['delivery'] = '代收货款';
        }else{
            $OrderData['delivery'] = '不代收货款';
        }
        $OrderData['delivery_type_id'] = Db::name('delivery_type')->where('id',$OrderData['delivery_type_id'])->value('name');
        $OrderData['receipt_type_id']  = Db::name('receipt_type')->where('id',$OrderData['receipt_type_id'])->value('name');
        $OrderData['unpack_id'] = Db::name('unpack')->where('id',$OrderData['unpack_id'])->value('name');
        $OrderData['other_id'] = Db::name('other')->where('id',$OrderData['other_id'])->value('name');
        if($OrderData['text_message'] == 1){
            $OrderData['text_message'] = '短信通知收件方';
        }else{
            $OrderData['text_message'] = '短信通知发件方';
        }
        if($OrderData['pay_status'] == 1){
            $OrderData['pay_status'] = '待付款';
        }elseif ($OrderData['pay_status'] == 2){
            $OrderData['pay_status'] = '进行中';
        }elseif ($OrderData['pay_status'] == 3){
            $OrderData['pay_status'] = '已完成';
        }elseif ($OrderData['pay_status'] == 4){
            $OrderData['pay_status'] = '已取消';
        }elseif ($OrderData['pay_status'] == 5){
            $OrderData['pay_status'] = '待下单';
        }
//        print_r($OrderData);die;
        $OrderData['car_type_id'] = Db::name('car_type')->where('id',$OrderData['car_type_id'])->value('name');
        $OrderData['earliest_time'] = date('Y-m-d H:i:s',$OrderData['earliest_time']);
        $OrderData['latest_time'] = date('Y-m-d H:i:s',$OrderData['latest_time']);
        $OrderData['status'] = $dricerOrder['status'];
        if ($OrderData){
            $this->success('订单详情查看',$OrderData);
        }else{
            $this->error('订单详情查看失败');
        }
    }
    /**
     * @return void
     *
     * 司机订单页
     */
    public function dricerorder()
    {
        $user = $this->auth->id;
        $type = $this->request->post('type');

        // 构建查询
        $query = Db::name('dricerorder')
            ->alias('do')
            ->field('do.*, o.id as order_main_id, o.unload, o.loading, o.logistics_id, 
                 o.shipment_driver_fee, o.pickup_driver_fee, o.createtime, o.logistics_driver_cost,
                 l.shipping_logistics_address, l.shipping_logistics_mobile,
                 l.arrival_logistics_address, l.arrival_logistics_mobile')
            ->join('order o', 'do.order_id = o.orderid', 'LEFT')
            ->join('logistics l', 'o.logistics_id = l.id', 'LEFT')
            ->where('do.d_id', $user);

        // 使用传统的if判断
        if ($type != 0) {
            $query->where('do.status', $type);
        }

        $order_list = $query->order('do.id', 'desc')->select();

        // 收集所有需要查询的地址ID
        $loading_ids = [];
        $unload_ids = [];
        foreach ($order_list as $item) {
            if (!empty($item['loading'])) {
                $loading_ids[] = $item['loading'];
            }
            if (!empty($item['unload'])) {
                $unload_ids[] = $item['unload'];
            }
        }

        // 批量查询地址信息
        $address_ids = array_merge($loading_ids, $unload_ids);
        $addresses = [];
        if (!empty($address_ids)) {
            $address_list = Db::name('user_address')
                ->whereIn('id', array_unique($address_ids))
                ->select()
                ->toArray();

            foreach ($address_list as $addr) {
                $addresses[$addr['id']] = $addr;
            }
        }

        // 处理数据
        $result = [];
        foreach ($order_list as $value) {
            // 如果订单信息不存在，跳过该记录
            if (empty($value['order_main_id'])) {
                continue;
            }

            // 设置状态名称
            $status_map = [
                1 => '服务中',
                2 => '已完成',
                3 => '待接单',
                4 => '已取消'
            ];
            $value['status_name'] = $status_map[$value['status']] ?? '未知';

            // 设置创建时间
            $value['createtime'] = $value['order_id'];

            // 根据type处理地址和价格
            if ($value['type'] == 1) {
                // 处理loading地址（司机取货地址）
                $loading_addr = $addresses[$value['loading']] ?? [];
                $value['unload_address'] = $loading_addr['address'] ?? '';
                $value['unload_mobile'] = $loading_addr['mobile'] ?? '';

                // 处理unload地址（物流发货地址）
                $value['loading_address'] = $value['shipping_logistics_address'] ?? '';
                $value['loading_mobile'] = $value['shipping_logistics_mobile'] ?? '';

                $value['price'] = $value['pickup_driver_fee'] ?? 0;
            } else {
                // 处理loading地址（物流到达地址）
                $value['loading_address'] = $value['arrival_logistics_address'] ?? '';
                $value['loading_mobile'] = $value['arrival_logistics_mobile'] ?? '';

                // 处理unload地址（司机卸货地址）
                $unload_addr = $addresses[$value['unload']] ?? [];
                $value['unload_address'] = $unload_addr['address'] ?? '';
                $value['unload_mobile'] = $unload_addr['mobile'] ?? '';

                $value['price'] = $value['shipment_driver_fee'] ?? 0;
            }

            $result[] = $value;
        }

        $this->success('success', $result);
    }

    /**
     * @return void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 司机取消订单
     */
    public  function cancel()
    {
        $id = $this->request->param();
        if (empty($id)){
            $this->error('信息不完整');
        }
        $res = Db::name('dricerorder')->where('id',$id['id'])->update(['status'=>4]);
        $dricerorder = Db::name('dricerorder')->where('id',$id['id'])->find();
        $ress = Db::name('order')->where('id',$dricerorder['order_id'])->update(['mobile'=>'']);
        if ($res){
            $this->success('订单取消成功');
        }else{
            $this->error('订单取消失败');
        }
    }
    /**
     * @return void
     *
     * 提现
     */
    public function withdraw(){

        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请登录');
        }
        $data = $this->request->param();
//        print_r($data);die;
        if (empty($data)) {
            $this->error('填写信息不完整');
        }
//        $requiredFields = ['name', 'bankcode', 'bankname'];
//        foreach ($requiredFields as $field) {
//            if (empty($data[$field])) {
//                $this->error('填写信息不完整1');
//            }
//        }
        if ($data['money'] <= 0) {
            $this->error('提现金额需大于0元');
        }
        if ($data['money'] > $user->money) {
            $this->error('余额不足');
        }
        $data['user_id'] = $user->id;
        $data['createtime'] = time();
        unset($data['temp_url_path']);
        unset($data['token']);
        $res = Db::name('withdraw')->insert($data);
        if ($res) {
            $this->success('提现成功,等待后台审核');
        } else {
            $this->error('提现失败,请联系管理员处理');
        }
    }

    public function withdrawlist()
    {
        $user = $this->auth->id();
        $data = Db::name('withdraw')
            ->where('uid',$user)
            ->order('createtime desc')
            ->select();
        foreach ($data as &$v){
            $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
        }
    }

    /**
     * @return void
     * 专线入驻
     */
    public function addsettle()
    {
        $user = $this->auth->id;
        $company_name = $this->request->param('company_name');
        $username = $this->request->param('username');
        $mobile = $this->request->param('mobile');
        $start_point = $this->request->param('start_point');
        $end_point = $this->request->param('end_point');
        $time_limit = $this->request->param('time_limit');
        $heavy_pirce = $this->request->param('heavy_pirce');
        $bulky_price = $this->request->param('bulky_price');
        $postcard_front_image = $this->request->param('postcard_front_image');
        $postcard_back_image = $this->request->param('postcard_back_image');
        $doorway_image = $this->request->param('doorway_image');

        $data = [
            'uid' => $user,
            'company_name' => $company_name,
            'username' => $username,
            'mobile' => $mobile,
            'start_point' => $start_point,
            'end_point' => $end_point,
            'time_limit' => $time_limit,
            'heavy_pirce' => $heavy_pirce,
            'bulky_price' => $bulky_price,
            'postcard_front_image' => $postcard_front_image,
            'postcard_back_image' => $postcard_back_image,
            'doorway_image' => $doorway_image,
            'createtime' => time(),
        ];

        $res = Db::name('settle')->insert($data);
        if ($res) {
            $this->success('专线入驻申请提交成功,等待审核');
        } else {
            $this->error('专线入驻申请提交失败,请联系管理员处理');
        }
    }
    /**
     * @return void
     * 司机抢单
     */
    public function addorder(){
        $user = $this->auth->getUserinfo();
        if (isset($user['error'])) {
            $this->error('请登录');
        }
        $order_id = $this->request->param('order_id');
        if (empty($order_id)){
            $this->error('信息不完整');
        }
        $orderinfo  = Db::name('order')->where('orderid',$order_id)->find();
//        print_r($orderinfo);die;
        if (!$orderinfo){
            $this->error('订单不存在，无法抢单');
        }

        $existingOrder = Db::name('dricerorder')
            ->where('order_id', $order_id)
            ->where('type',$orderinfo['type'])
            ->find();
        if ($existingOrder) {
            $this->error('该订单已被抢单');
        }
        $data = [
            'd_id' => $user['id'],
            'driver_name' => $user['username'],
            'driver_mobile' => $user['mobile'],
            'order_id' => $order_id,
            'type' => $orderinfo['type'],
            'createtime' => time(),
            'grabbingtime' => time(),
            'status' => 1,
        ];
        if ($orderinfo['type'] == 1){
            $price = $orderinfo['pickup_fee'];
        }elseif ($orderinfo['type'] == 3){
            $price = $orderinfo['shipment_fee'];
        }else{
            $this->error('订单类型错误，无法抢单');
        }
        $data['price'] = $price;
        $res = Db::name('dricerorder')->insert($data);
        if ($res) {
            // 送/取货司机抢单后写入轨迹
            $numericOrder = Db::name('order')->where('orderid', $order_id)->find();
            if ($numericOrder) {
                $orderNumericId = $numericOrder['id'];
                $dispatch = $this->getAdminContactByRole($orderNumericId, 2);
                $dispatchMobile = $dispatch['mobile'] ?? '';
                if ($dispatchMobile !== '') {
                    if ($orderinfo['type'] == 1) {
                        $typeText = '已发货';
                    } elseif ($orderinfo['type'] == 3) {
                        $typeText = '派件中';
                    } else {
                        $typeText = '';
                    }
                    if ($typeText !== '') {
                        if($typeText == "派件中"){
                            $dispatch = $this->getAdminContactByRole($orderNumericId, 3);
                            $dispatchMobile = $dispatch['mobile'] ?? '';
                        }
                        Db::name('trajectory')->insert([
                            'order_id' => $orderNumericId,
                            'admin_name' => $dispatch['name'].$dispatchMobile ?? '调度',
                            'admin_mobile' => $dispatchMobile,
                            'createtime' => time(),
                            'type' => $typeText,
                        ]);
                    }
                }
            }

            $this->success('抢单成功');
        } else {
            $this->error('抢单失败,请联系管理员处理');
        }
    }
    /**
     * @return void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 确认完成订单
     */
    public function confirmorder(){
        $orderNumber = $this->request->param('id');

        if (empty($orderNumber)){
            $this->error('信息不完整');
        }
        $content = $this->request->param('content');
        $loading_images = $this->request->param('loading_images');
        $receipt_images = $this->request->param('receipt_images');
        $unloading_images = $this->request->param('unloading_images');

//        if (empty($images)){
//            $this->error('照片不能为空');
//        }
        $user = $this->auth->getUserinfo();

        if (!$user || isset($user['error'])) {
            $this->error('请登录');
        }
//        print_r($orderNumber);die;
        // 找到当前司机的进行中订单记录（支持取货 type=1 和送货 type=3）
        $dricerorder = Db::name('dricerorder')
            ->where('order_id', $orderNumber)
            ->where('d_id', $user['id'])
            ->where('status', 1)
            ->find();
        if ($dricerorder['type'] == 1) {
            if (empty($loading_images)) {
                $this->error('装货图片不能为空');
            }
        }
        if ($dricerorder['type'] == 3) {
            if (empty($receipt_images)) {
                $this->error('回执照片不能为空');
            }
            if (empty($unloading_images)){
                $this->error('卸货照片不能为空');
            }
        }
        if (!$dricerorder) {
            $this->error('未找到进行中的司机订单');
        }

        // ===== 加盟商订单钱包扣费：送货司机完成订单时，按总运费×比例扣加盟商钱包 =====
        // 放在状态更新之前：余额不足时直接阻止订单完成，避免司机订单已标记完成却报错
        if ((int)$dricerorder['type'] === 3) {
            $preOrder = Db::name('order')->where('orderid', $dricerorder['order_id'])->find();
            if ($preOrder) {
                try {
                    $settle = FranchiseService::settleOrderFranchiseFee((int)$preOrder['id']);
                    if (!$settle['success']) {
                        $this->error($settle['msg']);
                    }
                } catch (\Throwable $e) {
                    Log::error('加盟商订单扣费异常：' . $e->getMessage());
                }
            }
        }

        $data = [
            'content' => $content,
            'loading_images' => $loading_images,
            'receipt_images' => $receipt_images,
            'unloading_images' => $unloading_images,
            'status' => 2,
            'unsettime' => time()
        ];

        $res = Db::name('dricerorder')
            ->where('id', $dricerorder['id'])
            ->update($data);

        if (!$res) {
            $this->error('订单确认失败');
        }
        // 给司机结算费用
        $order = Db::name('order')->where('orderid', $dricerorder['order_id'])->find();
        if (!$order) {
            $this->error('订单信息有误');
        }

        if ($dricerorder['type'] == 1){
            $price = $order['pickup_driver_fee'] ?? 0;
        }elseif ($dricerorder['type'] == 3){
            $price = $order['shipment_driver_fee'] ?? 0;
        }else{
            $price = 0;
        }
        if ($price > 0) {
            $user_money =  Db::name('user')->where('id',$dricerorder['d_id'])->value('money');
            Db::name('user')->where('id',$dricerorder['d_id'])->setInc('money',$price);
            $user_money_log['user_id'] = $dricerorder['d_id'];
            $user_money_log['money'] = $price;
            $user_money_log['before'] = $user_money;
            $user_money_log['after'] = $user_money + $price;
            $user_money_log['memo'] = '完成订单';
            $user_money_log['createtime'] = time();
            Db::name('user_money_log')->insert($user_money_log);
        }

        if (false && (int)$dricerorder['type'] === 3 && (int)($order['pay_type'] ?? 0) === 2) {
            $dispatch = Db::name('admin_order')
                ->where('order_id', (int)$order['id'])
                ->alias('ao')
                ->join('auth_group_access aga', 'aga.uid = ao.admin_id', 'LEFT')
                ->join('auth_group ag', 'ag.id = aga.group_id', 'LEFT')
                ->where('ag.identity', 3)
                ->field('ao.admin_id')
                ->find();
            $dispatchId = (int)($dispatch['admin_id'] ?? 0);
            $profit = max(0, round((float)($order['pay_price'] ?? 0) - (float)($order['cost_cont'] ?? 0), 2));
            $deductAmount = round((float)($order['logistics_driver_cost'] ?? 0) + (float)($order['shipment_driver_fee'] ?? 0) + $profit, 2);
            if ($dispatchId > 0 && $deductAmount > 0 && !Db::name('dispatch_reserve_fund_log')->where('admin_id', $dispatchId)->where('order_id', (int)$order['id'])->where('direction', 'deduct')->where('remark', 'like', '月结送货扣除%')->find()) {
                Db::startTrans();
                try {
                    $fund = Db::name('dispatch_reserve_fund')->lock(true)->where('admin_id', $dispatchId)->find();
                    $before = round((float)($fund['balance'] ?? 0), 2);
                    $after = round($before - $deductAmount, 2);
                    if ($fund) {
                        Db::name('dispatch_reserve_fund')->where('admin_id', $dispatchId)->update([
                            'balance' => $after,
                            'total_deduct' => Db::raw('total_deduct+' . $deductAmount),
                            'updatetime' => time(),
                        ]);
                    } else {
                        Db::name('dispatch_reserve_fund')->insert(['admin_id' => $dispatchId, 'balance' => $after, 'total_recharge' => 0, 'total_deduct' => $deductAmount, 'createtime' => time(), 'updatetime' => time()]);
                    }
                    Db::name('dispatch_reserve_fund_log')->insert([
                        'admin_id' => $dispatchId,
                        'order_id' => (int)$order['id'],
                        'order_number' => (string)$order['orderid'],
                        'driver_order_id' => (int)$dricerorder['id'],
                        'type' => 0,
                        'amount' => $deductAmount,
                        'direction' => 'deduct',
                        'balance_before' => $before,
                        'balance_after' => $after,
                        'remark' => '月结送货扣除（干线费+送货费+利润）',
                        'admin_name' => '系统',
                        'createtime' => time(),
                    ]);
                    Db::commit();
                } catch (\Throwable $e) {
                    Db::rollback();
                    Log::error('月结送货备用金扣除失败：' . $e->getMessage());
                }
            }
        }

        // 记录物流轨迹
        $orderNumericId = $order['id'];

        if ($dricerorder['type'] == 1) {
            $logistics = Db::name('logistics')->where('id', $order['logistics_id'])->find();
            if ($logistics) {
                $customer = $this->getAdminContactByRole($orderNumericId, 3);
                $customerMobile = $customer['mobile'] ?? '';

                // 专线名称作为“济南转运中心”
                $centerName = $logistics['shipping_logistics_name'] ?? '';
                if ($centerName == '' && isset($logistics['shipping_logistics_address'])) {
                    $centerName = $logistics['shipping_logistics_address'];
                }
//                print_r($centerName);die;
                if ($centerName !== '' && $customerMobile !== '') {

                    Db::name('trajectory')->insert([
                        'order_id' => $orderNumericId,
                        'admin_name' => $customer['name'] . '电话' . $customerMobile,
                        'admin_mobile' => $customerMobile,
                        'createtime' => time(),
                        'type' => '('.$centerName.')'.'已收入',
                    ]);

                    Db::name('trajectory')->insert([
                        'order_id' => $orderNumericId,
                        'admin_name' => $customer['name'] . '电话'.$customerMobile,
                        'admin_mobile' => $customerMobile,
                        'createtime' => time(),
                        'type' => '运输中',
                    ]);
                }
            }
        }
        if ($dricerorder['type'] == 3) {
            $dispatch = $this->getAdminContactByRole($orderNumericId, 3);
            if (empty($dispatch)) {
                $dispatch['name'] = '调度电话';
            }
            $dispatchMobile = $dispatch['mobile'] ?? '';
            if ($dispatchMobile !== '') {
                Db::name('trajectory')->insert([
                    'order_id' => $orderNumericId,
                    'admin_name' => $dispatch['name'] .$dispatchMobile,
                    'admin_mobile' => $dispatchMobile,
                    'createtime' => time(),
                    'type' => '已收货',
                ]);
            }
            $order = Db::name('order')->where('orderid', $orderNumber)->find();
/************* 计算员工提成 *************/
            $admin_order = Db::name('admin_order')->where('order_id',$order['id'])->select();
                   foreach ($admin_order as $ao){
                   $auth_group_access = Db::name('auth_group_access')
                                   ->where('uid',$ao['admin_id'])
                                   ->value('group_id');
                     $group = Db::name('auth_group')
                            ->where('id',$auth_group_access)
                            ->find();
                        $peichelirun = ($order['logistics_cost'] + $order['pickup_driver_fee'] + $order['shipment_driver_fee']) - ($order['logistics_driver_cost'] + $order['pickup_fee'] + $order['shipment_fee']);
                        $zhuanlirun = ($order['pay_price']) - ($order['special_price']);
                        $peiprice = $order['logistics_cost'] + $order['pickup_driver_fee'] + $order['shipment_driver_fee'];
                        if ($order['find_car_type'] == "配车"){
                            if ($group['commission_type'] == 1){
                                //净利润
                                $base_amount = $peichelirun;
                            }elseif ($group['commission_type'] == 2){
                                //营业额
                                $base_amount = $peiprice;
                            }else{
                                $base_amount = 0;
                            }
                    }elseif ($order['find_car_type'] == "专车"){
                            if ($group['commission_type'] == 1){
                                //净利润
                                $base_amount = $zhuanlirun;
                            }elseif ($group['commission_type'] == 2){
                                //营业额
                                $base_amount = $order['pay_price'];
                            }else{
                                $base_amount = 0;
                            }
                    }

                    $commission_rate = $group['commission_rate'] ?? 0;
                    $commission = $base_amount * $commission_rate / 100;
                    // 写入员工提成记录
                    Db::name('commission')->insert([
                        'admin_id' => $ao['admin_id'],
                        'admin_group_id' => $group['id'],
                        'order_id' => $order['id'],
                        'city' => $group['city'],
                        'base_amount' => $base_amount,
                        'commission_rate' => $commission_rate,
                        'commission' => $commission,
                        'createtime' => time(),
                    ]);
                    }

        }

        $this->success('订单已确认');
    }

    /**
     * 根据订单ID和身份ID获取后台管理员联系方式
     *
     * @param int $orderId  order表主键ID
     * @param int $identityId  身份ID：2=线路/规划，3=调度
     * @return array|null
     */
    private function getAdminContactByRole($orderId, $identityId)
    {
        if (empty($orderId) || empty($identityId)) {
            return null;
        }

        $adminOrders = Db::name('admin_order')->where('order_id', $orderId)->select();

        if (!$adminOrders) {
            return null;
        }

        foreach ($adminOrders as $ao) {
            $adminId = $ao['admin_id'] ?? 0;
            if (!$adminId) {
                continue;
            }

            $groupId = Db::name('auth_group_access')->where('uid', $adminId)->value('group_id');
            if (!$groupId) {
                continue;
            }

            $group = Db::name('auth_group')->where('id', $groupId)->find();
            if (!$group) {
                continue;
            }
            $groupIdentity = isset($group['identity']) ? intval($group['identity']) : 0;
            if ($groupIdentity === intval($identityId)) {
                $mobile = Db::name('admin')->where('id', $adminId)->value('mobile');
                return [
                    'name' => $group['name'] ?? '',
                    'mobile' => $mobile ?: '',
                ];
            }
        }

        return null;
    }
}
