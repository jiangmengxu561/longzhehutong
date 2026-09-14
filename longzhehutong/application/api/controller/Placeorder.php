<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Bill;
use app\common\model\User;
use app\common\model\MoneyLog;
use think\Config;
use think\Db;
use think\Log;
use function fast\e;

/**
 * 用户端首页接口
 */
class Placeorder extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*']; 

    /**
     * 仅需要一个价格、且不需要匹配物流的找车类型   
     * @var array
     */
    protected $singlePriceTypes = ['专车', '小票快运'];

    /** @var array 下单必填字段 => 中文名 */  
    protected $orderRequiredFields = [
        'loading'        => '发货地址',  
        'unload'         => '收货地址',
        'find_car_type'  => '找车类型', 
        'quantity'       => '数量',
        'weight'         => '重量',
        'car_type_id'    => '车型',
        'earliest_time'  => '最早时间',
        'latest_time'    => '最晚时间',
    ];  

    /** @var array 订单修改允许字段白名单 */
    protected $orderUpdateAllowFields = [
        'loading', 'unload', 'find_car_type', 'quantity', 'weight',
        'long', 'wide', 'hige', 'goods_image', 'direction', 'goods_name',
        'earliest_time', 'latest_time', 'car_type_id', 'goods_type_id',
        'packaging_id', 'pay_type', 'delivery', 'isrequirements', 'delivery_type_id',
        'receipt_type_id', 'service', 'unpack_id', 'unpack_num', 'control',
        'text_message', 'break', 'information', 'information_image', 'deposit',
        'deliveryrequirements', 'loadingrequirements', 'pay_price',
    ];

    /** @var array 驳回字段 => 可修改的订单字段 */
    protected $orderRejectFieldMap = [
        'loading'              => ['loading'],
        'unload'               => ['unload'],
        'find_car_type'        => ['find_car_type'],
        'quantity'             => ['quantity'],
        'weight'               => ['weight'],
        'dimensions'           => ['long', 'wide', 'hige'],
        'goods_name'           => ['goods_name'],
        'goods_type_id'        => ['goods_type_id'],
        'time_range'           => ['earliest_time', 'latest_time'],
        'car_type_id'          => ['car_type_id'],
        'packaging_id'         => ['packaging_id'],
        'pay_type'             => ['pay_type'],
        'delivery'             => ['delivery'],
        'delivery_type_id'     => ['delivery_type_id'],
        'receipt_type_id'      => ['receipt_type_id'],
        'unpack_id'            => ['unpack_id', 'unpack_num'],
        'other_id'             => ['other_id'],
        'text_message'         => ['text_message'],
        'information'          => ['information', 'information_image'],
        'deposit'              => ['deposit'],
        'pay_price'            => ['pay_price'],
        'deliveryrequirements' => ['deliveryrequirements'],
        'loadingrequirements'  => ['loadingrequirements'],
    ];

    /**
     * 添加地址
     *
     */

   public function address()
    {
        $data = $this->request->param();

        $user = $this->auth->id;
        if (!$user){
            $this->error('请登录');
        }
        $arr = [
                'address'=>$data['address'],
                'user_name'=>$data['user_name'],

                'mobile'=>$data['mobile'],
                'detailed_address'=>$data['detailed_address'],
                'default'=>$data['default'],
                'type'=>$data['type'],
//                'lng'=> $data['lng'],
//                'lat'=>$data['lat']
                ];
        if (isset($data['company_name'])) {
            $arr['company_name']= $data['company_name'];
        }else{
            $arr['company_name']='';
        }
        $address = $data['detailed_address'] ;
        if (empty($address)){
            $address = $data['address'];
        }
        $isquan = hasRegion($address)? "有" : "无";
        if($isquan == "无"){
            $this->error('详细地址不全，请补充');
        }
        $log = $this->getCoordinatesFromBaiduMap($address);
//        print_r($log);die;
        $arr['lng'] = $log['lng'];
        $arr['lat'] = $log['lat'];
        if (empty($arr['lng']) || empty($arr['lat'])){
            $this->error('地址有误,请重新填写');
        }
        $arr['user_id']=$user;
        $arr['createtime']=time();
        $res = Db::name('user_address')->insert($arr);
        if ($data['default']==1){
            $default = Db::name('user_address')->where(['user_id'=>$user,'default'=>1])->find();
            if ($default){
                Db::name('user_address')->where(['id'=>$default['id']])->update(['default'=>0]);
            }
        }
        if ($res){
            $this->success('地址添加成功');
        }else{
            $this->error('系统错误，添加失败');
        }
    }
    public function recognizeaddress()
    {
        $data = $this->request->param();

        $user = $this->auth->id;
        if (!$user){
            $this->error('请登录');
        }

        $address = $data['address'] ;
        if (empty($address)){
            $this->error('请填写内容');
        }
        $isquan = hasRegion($address)? "有" : "无";
        if($isquan == "无"){
            $this->error('详细地址不全，请补充');
        }
        $log = $this->getCoordinatesFromBaiduMap($address);
        $arr['lng'] = $log['lng'];
        $arr['lat'] = $log['lat'];
        if (empty($arr['lng']) || empty($arr['lat'])){
            $this->error('地址有误,请重新填写');
        }
        $arr['user_id']=$user;
        $arr['createtime']=time();
        $res = Db::name('user_address')->insert($arr);
        if ($data['default']==1){
            $default = Db::name('user_address')->where(['user_id'=>$user,'default'=>1])->find();
            if ($default){
                Db::name('user_address')->where(['id'=>$default['id']])->update(['default'=>0]);
            }
        }
        if ($res){
            $this->success('地址添加成功');
        }else{
            $this->error('系统错误，添加失败');
        }
    }
    /**
     * @return void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改地址信息
     */
    public function updateaddress(){

       $id = $this->request->param('id');
       $data = $this->request->param();
//       print_r($data);die;
       if (isset($data['address'])){
           $arr['address'] = $data['address'];
       }
       if (isset($data['user_name'])){
           $arr['user_name'] = $data['user_name'];
       }
       if (isset($data['company_name'])){
           $arr['company_name'] = $data['company_name'];
       }
       if (isset($data['mobile'])){
           $arr['mobile'] = $data['mobile'];
       }
       if (isset($data['detailed_address'])){
           $arr['detailed_address'] = $data['detailed_address'];
       }
        $isquan = hasRegion($arr['detailed_address'])? "有" : "无";
        if($isquan == "无"){
            $this->error('详细地址不全，请补充');
        }
       if (isset($data['default'])){
           $arr['default'] = $data['default'];
       }
       if (isset($data['lng'])){
           $arr['lng'] = $data['lng'];
       }
       if (isset($data['lat'])){
           $arr['lat'] = $data['lat'];
       }
//       $arr = [
//           'address'=>$data['address'],
//           'user_name'=>$data['user_name'],
//           'mobile'=>$data['mobile'],
//           'detailed_address'=>$data['detailed_address'],
//           'default'=>$data['default'],
//           'lng'=> $data['lng'],
//           'lat'=>$data['lat'],
//           ];
       $res = Db::name('user_address')
           ->where(['id'=>$id])
           ->update($arr);
       if ($res){
           $this->success('修改成功');
       }else{
           $this->error('修改失败');
       }
    }

    /**
     * 删除地址
     */
    public function deleteaddress()
    {
        $id = $this->request->param('id');
        $userId = $this->auth->id;

        if (empty($userId)) {
            $this->error('请登录');
        }

        if (empty($id)) {
            $this->error('参数错误');
        }

        $address = Db::name('user_address')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->find();

        if (!$address) {
            $this->error('地址不存在');
        }

        $deleted = Db::name('user_address')->where('id', $id)->delete();

        if ($deleted) {
            $this->success('地址删除成功');
        } else {
            $this->error('地址删除失败');
        }
    }
    private function getCoordinatesFromBaiduMap($address)
    {
        if(empty($address)) {
            return ['lng' => '', 'lat' => ''];
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
            $akList = ['default' => 'Mc5XjX716IHNL85z9rZM24q6zxqmv7bu'];
        }

        // 记录当前使用的AK索引
        static $currentAkIndex = 0;
        $akKeys = array_keys($akList);

        // 尝试所有AK，直到成功或全部失败
        $maxRetries = count($akList);

        for ($i = 0; $i < $maxRetries; $i++) {
            $currentKey = $akKeys[$currentAkIndex];
            $ak = $akList[$currentKey];

            $url = "http://api.map.baidu.com/geocoding/v3/?address=" . urlencode($address) . "&output=json&ak=" . $ak;

            try {
                // 调用API（带超时，避免外部接口卡死导致下单一直加载）
                $result = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5], 'https' => ['timeout' => 5]]));

                if ($result === FALSE) {
                    // 网络请求失败，记录并尝试下一个AK
//                    Log::error("百度地图API网络请求失败，AK：" . $currentKey);
                    $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                    continue;
                }

                $data = json_decode($result, true);
//                print_r($data);die;
                if ($data['status'] == 0 && isset($data['result']['location'])) {
                    // 成功获取坐标
                    return [
                        'lng' => $data['result']['location']['lng'],
                        'lat' => $data['result']['location']['lat']
                    ];
                } elseif ($data['status'] == 1 || $data['status'] == 101 || $data['status'] == 302 || $data['status'] == 401) {
                    // AK权限或额度问题：status=1(服务内部错误)、status=101(AK无效/无权限)、status=302(天配额超限)、status=401(并发超限)
//                    Log::warning("百度地图AK额度可能用尽，AK：" . $currentKey . "，状态码：" . $data['status']);
                    $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                    continue; // 尝试下一个AK
                } else {
                    // 其他错误（地址解析失败等）
//                    Log::error("百度地图API调用失败，地址：{$address}，AK：" . $currentKey . "，返回：" . json_encode($data));
                    break; // 如果是地址问题，不需要切换AK重试
                }
            } catch (\Exception $e) {
//                Log::error("获取坐标异常，AK：" . $currentKey . "，错误：" . $e->getMessage());
                $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                continue;
            }
        }

        // 所有AK都尝试失败
//        Log::error("所有百度地图AK都尝试失败，地址：{$address}");
        return ['lng' => '', 'lat' => ''];
    }

    private function getCoordinatesdirectionlitep($address)
    {
        if(empty($address)) {
            return ['lng' => '', 'lat' => ''];
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

            $url = "https://api.map.baidu.com/directionlite/v1/driving?origin=40.01116,116.339303&destination=39.936404,116.452562&ak=" . $ak;

            try {
                // 调用API（带超时，避免外部接口卡死导致下单一直加载）
                $result = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5], 'https' => ['timeout' => 5]]));

                if ($result === FALSE) {
                    // 网络请求失败，记录并尝试下一个AK
//                    Log::error("百度地图API网络请求失败，AK：" . $currentKey);
                    $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                    continue;
                }

                $data = json_decode($result, true);

                if (isset($data['result']['location'])) {
                    // 成功获取坐标
                    return [
                        'lng' => $data['result']['location']['lng'],
                        'lat' => $data['result']['location']['lat']
                    ];
                } elseif ($data['status'] == 1 || $data['status'] == 101 || $data['status'] == 302 || $data['status'] == 401) {
                    // AK权限或额度问题：status=1(服务内部错误)、status=101(AK无效/无权限)、status=302(天配额超限)、status=401(并发超限)
//                    Log::warning("百度地图AK额度可能用尽，AK：" . $currentKey . "，状态码：" . $data['status']);
                    $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                    continue; // 尝试下一个AK
                } else {
                    // 其他错误
//                    Log::error("百度地图API调用失败，AK：" . $currentKey . "，返回：" . json_encode($data));
                    break; // 如果是地址问题，不需要切换AK重试
                }
            } catch (\Exception $e) {
//                Log::error("获取坐标异常，AK：" . $currentKey . "，错误：" . $e->getMessage());
                $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                continue;
            }
        }

        // 所有AK都尝试失败
//        Log::error("所有百度地图AK都尝试失败");
        return ['lng' => '', 'lat' => ''];
    }
    /**
     * 首页轮播图
     */
    public function banner(){
        $data = Config::get('site.banner');
        if ($data){
            $this->success('获取成功',$data);
        }else{
            $this->error('暂无数据');
        }
    }
    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 用户地址列表
     */
    public function address_list(){

          $user = $this->auth->id;
          $type = $this->request->param('type');
          if (empty($user)){
                $this->error('请登录');
          }
          $data = Db::name('user_address')
              ->where('user_id',$user)
              ->where('type',$type)
              ->order('id desc')
              ->select();

          if ($data){
              $this->success('查询成功',$data);
          }else{
              $this->error('暂无地址数据');
          }
    }
    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 商品类型
     */
    public function goods_type(){
        $data = Db::name('goods_type')->field('id,name,switch')->select();
        if ($data){
            $this->success('查询成功',$data);
        }else{
            $this->error('暂无地址数据');
        }
    }
    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 包装方式
     */
    public function packaging(){
        $data = Db::name('packaging')
            ->field('id,name')
//            ->order('weigh desc')
            ->select();
        if ($data){
            $this->success('查询成功',$data);
        }else{
            $this->error('暂无地址数据');
        }
    }
    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 车型类型
     */
    public function car_type(){
        $data = Db::name('car_type')
            ->field('id,name,car_image,car_boxlength car_long,car_weight as car_wide,car_carrierside as car_high')
            ->order('id asc')
            ->select();
        if ($data){
            $this->success('查询成功',$data);
        }else{
            $this->error('暂无地址数据');
        }
    }
    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 拆包服务
     */
    public function unpack(){
        $data = Db::name('unpack')->field('id,name,unpack_type_price')->select();
        if ($data){
            $this->success('查询成功',$data);
        }else{
            $this->error('暂无地址数据');
        }
    }
    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 回单服务
     */
    public function receipt_type(){
        $data = Db::name('receipt_type')->select();
        if ($data){
            $this->success('查询成功',$data);
        }else{
            $this->error('暂无地址数据');
        }
    }
    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 回单服务
     */
    public function other(){
        $data = Db::name('other')->select();
        if ($data){
            $this->success('查询成功',$data);
        }else{
            $this->error('暂无地址数据');
        }
    }
    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 送货服务  
     */ 
    public function delivery_type(){
        $data = Db::name('delivery_type')->select();
        if ($data){
            $this->success('查询成功',$data);
        }else{
            $this->error('暂无地址数据');
        }
    }


    /**
     * @return void
     * 生成订单
     */
    public function order()
    {
        $data = $this->request->param();

//        $this->error('系统维护中');
        unset($data['temp_url_path']);
        $user = $this->getLoginUserOrFail();

        $validationResult = $this->validateOrderData($data);
        if ($validationResult !== true) {
            $this->error($validationResult);
        }

        $this->checkDailyOrderLimit($user);
        $this->validateInvoiceData($data);

        $goodsTypePercentage = $this->getGoodsTypePercentage($data['goods_type_id'] ?? 0);

        $orderData = $this->prepareOrderData($data, $user);

        list($orderData, $isSinglePriceOrder) = $this->calculateOrderPricing($orderData, $data, $goodsTypePercentage, $user['id']);
        $orderData = $this->applyOrderExtraFields($orderData, $data, $user['membertype'] ?? 0);

        $orderId = $orderData['orderid'];
        Db::transaction(function () use ($data, $user, $orderData, $orderId, $isSinglePriceOrder) {
            if (!Db::name('order')->insert($orderData)) {
                $this->error('订单创建失败');
            }
            $this->saveOrderDimensions($orderId, $data, $orderData);
            $this->saveOrderPackaging($orderId, $data);

            if (!empty($data['isinvoice']) && (int)$data['isinvoice'] === 1) {
                $this->createInvoice($data, $user['id'], $orderId);
            }
            if (!empty($data['delivery']) && (int)$data['delivery'] === 1) {
                $this->createCharge($data, $orderId);
            }
            if (!$isSinglePriceOrder) {
                $this->createDedicatedOrder($orderId, $orderData['logistics_id'], $orderData['logistics_driver_cost'] ?? 0);
            }
        });

        $this->respondOrderCreated([
            'orderid'   => $orderData['orderid'],
            'pay_price' => $orderData['pay_price'],
        ], $orderData['find_car_type'], $user['id']);
    }

    /**
     * @return void
     * 修改订单（仅支持 pay_status=5 待下单 状态）
     */
    public function update_order()
    {
        $data = $this->request->param();
        $user = $this->getLoginUserOrFail();

        $orderId = $this->resolveOrderId($data);
        $order = $this->findOwnedOrder($orderId, $user['id']);

        $rejectContext = $this->getOrderRejectContext($order);
        $updateData = $this->buildOrderUpdatePayload($data, $rejectContext);

        if (isset($updateData['earliest_time']) || isset($updateData['latest_time'])) {
            $earliest = $updateData['earliest_time'] ?? $order['earliest_time'];
            $latest = $updateData['latest_time'] ?? $order['latest_time'];
            $this->validateOrderTimeRange($earliest, $latest);
        }
        $this->validateAddressExists(
            $updateData['loading'] ?? null,
            $updateData['unload'] ?? null
        );

        $canUpdateDimensions = !$rejectContext['is_reject_mode'] || $rejectContext['reject_field'] === 'dimensions';
        $canUpdatePackaging = !$rejectContext['is_reject_mode'] || $rejectContext['reject_field'] === 'packaging_id';
        $dimensionFallback = array_merge($order, $updateData);

        Db::transaction(function () use (
            $orderId, $user, $updateData, $order, $rejectContext,
            $data, $canUpdateDimensions, $canUpdatePackaging, $dimensionFallback
        ) {
            if (!empty($updateData)) {
                if (Db::name('order')->where('orderid', $orderId)->where('userid', $user['id'])->update($updateData) === false) {
                    $this->error('订单更新失败');
                }
            }

            if ($canUpdateDimensions && isset($data['sizeList']) && is_array($data['sizeList'])) {
                $this->saveOrderDimensions($orderId, $data, $dimensionFallback, true);
            }
            if ($canUpdatePackaging && isset($data['packaging_list']) && is_array($data['packaging_list'])) {
                $this->saveOrderPackaging($orderId, $data, true);
            }

            if ($rejectContext['is_rejected_order']) {
                $resetResult = Db::name('order')->where('orderid', $orderId)->where('userid', $user['id'])->update([
                    'pay_status'    => 1,
                    'reject'        => '',
                    'reject_field'  => '',
                ]);
                if ($resetResult === false) {
                    $this->error('订单状态更新失败');
                }
            }
        });

        $this->success('订单修改成功');
    }

    /**
     * 更换订单物流专线（配车订单，仅限排名前6的专线）
     */
    public function change_logistics()
    {
        $data = $this->request->param();
        $user = $this->getLoginUserOrFail();

        $orderId = $this->resolveOrderId($data);
        $logisticsId = isset($data['logistics_id']) ? intval($data['logistics_id']) : 0;
        if ($logisticsId <= 0) {
            $this->error('请选择物流专线');
        }

        $order = $this->findOwnedOrder($orderId, $user['id']);

        if ($order['find_car_type'] !== '配车') {
            $this->error('仅配车订单可更换物流专线');
        }

        $allowStatuses = [1, 5, 6, 7, 8];
        if (!in_array((int)$order['pay_status'], $allowStatuses, true)) {
            $this->error('当前订单状态不可更换物流专线');
        }

        if ((int)$order['logistics_id'] === $logisticsId) {
            $this->success('已是当前物流专线', $this->buildLogisticsChangeResponse($order));
        }

        $logisticsPricing = $this->resolveLogisticsPricingForOrder($order, $logisticsId);
        $updateData = $this->buildOrderAmountsWithLogistics($order, $logisticsPricing, $user['membertype'] ?? 0);

        Db::transaction(function () use ($orderId, $user, $updateData, $logisticsId) {
            if (Db::name('order')->where('orderid', $orderId)->where('userid', $user['id'])->update($updateData) === false) {
                $this->error('物流专线更换失败');
            }
            $this->syncDedicatedOrder($orderId, $logisticsId, $updateData['logistics_driver_cost']);
        });

        $this->success('物流专线更换成功', $this->buildLogisticsChangeResponse(array_merge($order, $updateData)));
    }

    /**
     * 验证订单数据
     */
    private function validateOrderData($data)
    {
        foreach ($this->orderRequiredFields as $field => $label) {
            if (empty($data[$field])) {
                return $label . '不能为空';
            }
        }

        if (!$this->addressExists($data['loading'], $data['unload'])) {
            return '发货或收货地址有误';
        }

        return true;
    }

    /**
     * 获取已登录用户，未登录则中断（统一转为数组，兼容 Auth 返回的 User 模型）
     */
    private function getLoginUserOrFail()
    {
        $user = $this->auth->getUser();
        if (!$user || empty($user['id'])) {
            $this->error('请登录');
        }
        if (is_object($user) && method_exists($user, 'toArray')) {
            return $user->toArray();
        }
        return is_array($user) ? $user : (array)$user;
    }

    /**
     * 从请求参数解析订单号
     */
    private function resolveOrderId(array $data)
    {
        $orderId = $data['order_id'] ?? ($data['orderid'] ?? '');
        if (empty($orderId)) {
            $this->error('订单号不能为空');
        }
        return $orderId;
    }

    /**
     * 查询当前用户拥有的订单
     */
    private function findOwnedOrder($orderId, $userId)
    {
        $order = Db::name('order')->where('orderid', $orderId)->where('userid', $userId)->find();
        if (empty($order)) {
            $this->error('订单不存在或无权操作');
        }
        return $order;
    }

    /**
     * 解析订单驳回修改上下文
     */
    private function getOrderRejectContext(array $order)
    {
        $rejectField = isset($order['reject_field']) ? trim($order['reject_field']) : '';
        $isRejectedOrder = ((int)$order['pay_status'] === 8);
        $isRejectMode = ($isRejectedOrder && $rejectField !== '');

        return [
            'reject_field'      => $rejectField,
            'is_rejected_order' => $isRejectedOrder,
            'is_reject_mode'    => $isRejectMode,
        ];
    }

    /**
     * 根据驳回上下文构建订单更新字段
     */
    private function buildOrderUpdatePayload(array $data, array $rejectContext)
    {
        $allowFields = $rejectContext['is_reject_mode']
            ? ($this->orderRejectFieldMap[$rejectContext['reject_field']] ?? [])
            : $this->orderUpdateAllowFields;

        $updateData = [];
        foreach ($allowFields as $field) {
            if (!isset($data[$field])) {
                continue;
            }
            if ($field === 'earliest_time' || $field === 'latest_time') {
                $updateData[$field] = is_numeric($data[$field]) ? intval($data[$field]) : strtotime($data[$field]);
            } elseif ($field === 'deliveryrequirements') {
                $updateData['deliveryrequirements_id'] = $data[$field];
            } elseif ($field === 'loadingrequirements') {
                $updateData['loadingrequirements_id'] = $data[$field];
            } else {
                $updateData[$field] = $data[$field];
            }
        }

        if (!($rejectContext['is_reject_mode'] && $rejectContext['reject_field'] !== 'other_id')) {
            if (isset($data['other_list']) && is_array($data['other_list'])) {
                $updateData['other_id'] = implode(',', $data['other_list']);
            } elseif (isset($data['other_id'])) {
                $updateData['other_id'] = $data['other_id'];
            }
        }

        if (array_key_exists('pay_price', $updateData)) {
            $updateData['pay_price'] = round(floatval($updateData['pay_price']), 2);
            $updateData['shipping_cost'] = $updateData['pay_price'];
        }

        return $updateData;
    }

    /**
     * 校验订单时间范围
     * @return array [earliestTs, latestTs]
     */
    private function validateOrderTimeRange($earliest, $latest)
    {
        $earliestTs = is_numeric($earliest) ? intval($earliest) : strtotime($earliest);
        $latestTs = is_numeric($latest) ? intval($latest) : strtotime($latest);
        if ($earliestTs >= $latestTs) {
            $this->error('最晚时间不能小于等于最早时间');
        }
        if ($earliestTs < time()) {
            $this->error('最早时间不能小于当前时间');
        }
        return [$earliestTs, $latestTs];
    }

    /**
     * 校验地址 ID 是否存在（传 null 则跳过）
     */
    private function validateAddressExists($loadingId = null, $unloadId = null)
    {
        if ($loadingId && !$this->addressExists($loadingId)) {
            $this->error('发货地址有误');
        }
        if ($unloadId && !$this->addressExists($unloadId)) {
            $this->error('收货地址有误');
        }
    }

    /**
     * 判断地址是否存在（支持批量校验）
     */
    private function addressExists($addressId, $secondAddressId = null)
    {
        if ($secondAddressId !== null) {
            $count = Db::name('user_address')->where('id', 'in', [$addressId, $secondAddressId])->count();
            return $count >= 2;
        }
        return (bool)Db::name('user_address')->where('id', $addressId)->find();
    }

    /**
     * 下单成功后的响应处理
     */
    private function respondOrderCreated(array $result, $findCarType, $userId)
    {
        if ($result['pay_price'] > 0 && $findCarType === '配车') {
            Db::name('user')->where('id', $userId)->setDec('query_num', 1);
            $this->success('订单添加成功', $result);
        } elseif (in_array($findCarType, $this->singlePriceTypes, true)) {
            $this->success('订单添加成功', $result);
        } else {
            $this->error('快递正在努力开发中');
        }
    }

    /**
     * 普通用户每日下单次数限制
     */
    private function checkDailyOrderLimit(array $user)
    {
        if ($user['membertype'] != 1) {
            return;
        }
        $count = Db::name('order')
            ->where('userid', $user['id'])
            ->whereTime('createtime', 'today')
            ->count();
        if ($count >= 10) {
            $this->error('今日暂无下单次数');
        }
    }

    /**
     * 开票信息前置校验（避免订单已写入后再失败）
     */
    private function validateInvoiceData(array $data)
    {
        if (empty($data['isinvoice']) || (int)$data['isinvoice'] !== 1) {
            return;
        }
        if (empty($data['company_letterhead'])) {
            $this->error('请填写公司抬头');
        }
        if (empty($data['company_tax_id'])) {
            $this->error('请填写税号');
        }
    }

    /**
     * 校验最晚送达时间是否超出运营时段
     */
    private function validateLatestDeliveryTime($findCarType, $latestTime, $startDistance)
    {
        $pickupTime = Config::get('site.PickupTime');
        $vehicleSpeed = Config::get('site.Vehiclespeed');
        if (!$pickupTime || !$vehicleSpeed) {
            return;
        }

        $estimatedEnd = date('H:i:s', strtotime($latestTime) + $pickupTime * 60 + $startDistance / $vehicleSpeed);

        if ($findCarType === '小票快运') {
            $limit = Config::get('site.kuaiyunLatestTime');
            if ($estimatedEnd > $limit) {
                $this->error('当前时间段无法下单，请更改下单时间');
            }
        } elseif ($findCarType === '配车') {
            $limit = Config::get('site.LatestTime');
            if ($estimatedEnd > $limit) {
                $this->error('当前时间段无法下单，请更改下单时间');
            }
        }
    }

    /**
     * 计算订单运费（单价格 / 物流专线两种路径）
     * @return array [orderData, isSinglePriceOrder]
     */
    private function calculateOrderPricing(array $orderData, array $data, $goodsTypePercentage, $userId = 0)
    {

        $isSinglePriceOrder = $this->isSinglePriceOrder($orderData['find_car_type']);
        if ($isSinglePriceOrder) {
            $orderData = $this->applySinglePriceStructure($orderData, $data, $goodsTypePercentage);
            $this->validateLatestDeliveryTime($data['find_car_type'], $data['latest_time'], 60);
            return [$orderData, true];
        }

        $logisticsId = isset($data['logistics_id']) ? intval($data['logistics_id']) : 0;

        if ($logisticsId > 0) {
            $logisticsResult = $this->resolveLogisticsPricingById(
                $logisticsId,
                $data['loading'],
                $data['unload'],
                $data['car_type_id'],
                $goodsTypePercentage,
                $userId,
                $data['weight'] ?? null,
                $data['direction'] ?? null
            );
        } else {
            $logisticsResult = $this->matchLogistics(
                $data['loading'],
                $data['unload'],
                $data['car_type_id'],
                $goodsTypePercentage
            );
        }
        if (!$logisticsResult) {
            $this->error('未找到合适的物流专线,请联系管理员');
        }

        $startDistance = $logisticsResult['loading_to_start'] ?? 0;

        $this->validateLatestDeliveryTime($data['find_car_type'], $data['latest_time'], $startDistance);

        $orderData = array_merge($orderData, $logisticsResult);

        if ($data['isrequirements'] == 1 && $data['service'] === '自提') {
            $orderData['shipment_fee'] = 0;
            $orderData['shipment_driver_fee'] = 0;
        }

        // 下单时，取货端和送货端司机费用默认包含 9% 税点
        $orderData['pickup_driver_fee'] = round(($orderData['pickup_driver_fee'] ?? 0) * 1.09, 2);
        $orderData['shipment_driver_fee'] = round(($orderData['shipment_driver_fee'] ?? 0) * 1.09, 2);
        $orderData['pickup_fee'] = round(($orderData['pickup_fee'] ?? 0) * 1.09, 2);
        $orderData['shipment_fee'] = round(($orderData['shipment_fee'] ?? 0) * 1.09, 2);

        $payPrice = ($orderData['logistics_cost'] ?? 0)
            + ($orderData['pickup_fee'] ?? 0)
            + ($orderData['shipment_fee'] ?? 0);
        $orderData['pay_price'] = round($payPrice, 2);
        return [$orderData, false];
    }

    /**
     * 合并附加字段并计算总成本 / 应付金额
     */
    private function applyOrderExtraFields(array $orderData, array $data, $memberType = 0)
    {
        $orderData['cost_cont'] = ($orderData['logistics_driver_cost'] ?? 0)
            + ($orderData['pickup_driver_fee'] ?? 0)
            + ($orderData['shipment_driver_fee'] ?? 0);
        $orderData['break'] = $data['break'];

        if (array_key_exists('information', $data)) {
            $orderData['information'] = $data['information'];
        }
        if (array_key_exists('deposit', $data)) {
            $orderData['deposit'] = $data['deposit'];
        }
        if (array_key_exists('information_image', $data)) {
            $orderData['information_image'] = $data['information_image'];
        }
        $orderData['goods_name'] = $data['goods_name'];

        if (isset($data['receipt_type_id'])) {
            $orderData['receipt_type_id'] = $data['receipt_type_id'];
        }
        if (isset($data['receipt_type_price']) && $data['receipt_type_price'] !== '') {
            $orderData['receipt_type_price'] = round(floatval($data['receipt_type_price']), 2);
        }
        if (isset($data['unpack_id'])) {
            $orderData['unpack_id'] = $data['unpack_id'];
        }
        if (isset($data['unpack_num'])) {
            $orderData['unpack_num'] = $data['unpack_num'];
        }
        if (isset($data['unpack_price']) && $data['unpack_price'] !== '') {
            $orderData['unpack_price'] = round(floatval($data['unpack_price']), 2);
        }
        if (isset($data['deliveryrequirements'])) {
            $orderData['deliveryrequirements_id'] = $data['deliveryrequirements'];
        }
        if (isset($data['loadingrequirements'])) {
            $orderData['loadingrequirements_id'] = $data['loadingrequirements'];
        }
        if (!empty($data['other_list'])) {
            $orderData['other_id'] = implode(',', $data['other_list']);
        }

        $receiptPrice = $orderData['receipt_type_price'] ?? 0;
        $unpackPrice = $orderData['unpack_price'] ?? 0;
        $infoFee = (isset($orderData['information']) && $orderData['information'] !== '')
            ? floatval($orderData['information']) : 0;
        $depositFee = (isset($orderData['deposit']) && $orderData['deposit'] !== '')
            ? floatval($orderData['deposit']) : 0;

        // 平台抽佣：仅对兼职（membertype=2）生效，优先取用户/站点配置比例，无配置时默认 5%
        $commissionRate = 0;
        if ((int)$memberType === 2) {
            $commissionRate = $this->getUserPlatformCommissionRate($orderData['userid'] ?? 0);
            if ($commissionRate <= 0) {
                $commissionRate = 5;
            }
        }
        $orderData['platform_commission'] = 0;
        if ($commissionRate > 0) {
            $commissionBase = round((float)($orderData['pay_price'] ?? 0) + $receiptPrice + $unpackPrice, 2);
            $orderData['platform_commission'] = round($commissionBase * ($commissionRate / 100), 2);
        }
        $commissionFee = floatval($orderData['platform_commission']);
        $orderData['cost_cont'] = ($orderData['cost_cont'] ?? 0) + $receiptPrice + $unpackPrice + $infoFee + $depositFee + $commissionFee;
        $orderData['pay_price'] = round(($orderData['pay_price'] ?? 0) + $receiptPrice + $unpackPrice, 2);

        if (!empty($data['isinvoice']) && (int)$data['isinvoice'] === 1
            && isset($data['tax_point']) && $data['tax_point'] !== '') {
            $taxPoint = floatval(str_replace('%', '', trim($data['tax_point'])));
            if ($taxPoint > 0) {
                $taxAmount = round($orderData['pay_price'] * ($taxPoint / 100), 2);
                $orderData['pay_price'] = round($orderData['pay_price'] + $taxAmount, 2);
                $orderData['cost_cont'] = round(($orderData['cost_cont'] ?? 0) + $taxAmount, 2);
            }
        }

        $orderData['shipping_cost'] = $orderData['pay_price'] ?? 0;
        return $orderData;
    }

    /**
     * 保存订单尺寸明细（$replace=true 时先删后插，用于修改订单）
     */
    private function saveOrderDimensions($orderId, array $data, array $fallbackOrder, $replace = false)
    {
        if ($replace) {
            Db::name('dimensions')->where('order_id', $orderId)->delete();
        }

        if (isset($data['sizeList']) && is_array($data['sizeList']) && !empty($data['sizeList'])) {
            $rows = [];
            foreach ($data['sizeList'] as $item) {
                $item['order_id'] = $orderId;
                $rows[] = $item;
            }
            Db::name('dimensions')->insertAll($rows);
            return;
        }

        Db::name('dimensions')->insert([
            'order_id' => $orderId,
            'long'     => $fallbackOrder['long'] ?? '',
            'wide'     => $fallbackOrder['wide'] ?? '',
            'hige'     => $fallbackOrder['hige'] ?? '',
        ]);
    }

    /**
     * 保存订单包装明细（$replace=true 时先删后插，用于修改订单）
     */
    private function saveOrderPackaging($orderId, array $data, $replace = false)
    {
        if (!isset($data['packaging_list']) || !is_array($data['packaging_list'])) {
            return;
        }
        if ($replace) {
            Db::name('packaging_num')->where('order_id', $orderId)->delete();
        }
        foreach ($data['packaging_list'] as $item) {
            $item['order_id'] = $orderId;
            Db::name('packaging_num')->insert($item);
        }
    }

    /**
     * 准备订单数据
     */
    private function prepareOrderData($data, $user)
    {
        list($earliestTs, $latestTs) = $this->validateOrderTimeRange($data['earliest_time'], $data['latest_time']);

        return [
            'orderid' => $this->generateOrderNumber(),
            'platform_commission' => 0,
            'userid' => $user['id'],
            'username' => $user['username'] ?? '',
            'mobile' => $user['mobile'] ?? '',
            'createtime' => time(),
            'loading' => $data['loading'],
            'unload' => $data['unload'],
            'find_car_type' => $data['find_car_type'],
            'quantity' => $data['quantity'],
            'weight' => $data['weight'],
            'long' => $data['long'],
            'wide' => $data['wide'],
            'goods_image' => $data['goods_image'],
            'hige' => $data['hige'],
            'direction' => $data['direction'],
            'isinvoice' => $data['isinvoice'],
            'goods_type_id' => $data['goods_type_id'],
            'packaging_id' => $data['packaging_id'],
            'pay_type' => $data['pay_type'],
            'delivery' => $data['delivery'],
            'car_type_id' => $data['car_type_id'],
            'earliest_time' => $earliestTs,
            'latest_time' => $latestTs,
            'isrequirements' => $data['isrequirements'] ?? 0,
            'delivery_type_id' => $data['delivery_type_id'] ?? '',
            'receipt_type_id' => $data['receipt_type_id'] ?? '',
            'service' => $data['service'] ?? '',
            'unpack_id' => $data['unpack_id'] ?? '',
            'control' => $data['control'] ?? '',
            'other_id' => $data['other_id'] ?? '',
            'text_message' => $data['text_message'] ?? '',
            'logistics_status' => 1,
            'pay_status' => 5,
            'backend_status' => 1,
        ];
    }

    /**
     * 判断是否为仅需单一价格的订单类型
     */
    private function isSinglePriceOrder($findCarType)
    {
        if (empty($findCarType)) {
            return false;
        }
        return in_array($findCarType, $this->singlePriceTypes, true);
    }

    /**
     * 对无需物流匹配的订单应用单价格结构
     */
    private function applySinglePriceStructure(array $orderData, array $requestData, $goodsTypePercentage = 0)
    {
        $singlePrice = $this->extractSinglePrice($requestData);
        
        // 应用货物类型价格上浮百分比
        if ($goodsTypePercentage > 0) {
            $singlePrice = $singlePrice * (1 + $goodsTypePercentage / 100);
        }

        $orderData['logistics_id'] = 0;
        $orderData['logistics_cost'] = 0;
        // 一口价订单无专线/取送拆分：与配车一致，将整笔报价计入干线司机成本，否则 cost_cont 仅含附加项导致总成本严重偏低
        $orderData['logistics_driver_cost'] = round($singlePrice, 2);
        $orderData['pickup_fee'] = 0;
        $orderData['pickup_driver_fee'] = 0;
        $orderData['shipment_fee'] = 0;
        $orderData['shipment_driver_fee'] = 0;
        $orderData['pay_price'] = round($singlePrice, 2);

        return $orderData;
    }

    /**
     * 提取用户提交的单一价格（若存在）
     */
    private function extractSinglePrice(array $requestData)
    {
        foreach (['single_price', 'price', 'pay_price'] as $field) {
            if (isset($requestData[$field]) && $requestData[$field] !== '') {
                return round(floatval($requestData[$field]), 2);
            }
        }

        return 0;
    }

    /**
     * 获取用户路线价格配置
     * @param int $userId 用户ID
     * @param string $loadingProvince 发货省份
     * @param string $unloadProvince 收货省份
     * @return array|false 返回配置数组，如果不存在返回false
     */
    private function getUserRoutePriceConfig($userId, $loadingProvince, $unloadProvince)
    {
        if (empty($userId) || empty($loadingProvince) || empty($unloadProvince)) {
            return false;
        }

        // 先查询用户专属配置
        $config = Db::name('user_route_price')
            ->where('user_id', $userId)
            ->where('type', 3)
            ->where('loading_province', $loadingProvince)
            ->where('unload_province', $unloadProvince)
            ->find();
        // 如果没找到用户专属配置，查询全局配置（user_id=0）
        if (!$config) {
            $config = Db::name('user_route_price')
                ->where('user_id', 0)
                ->where('loading_province', $loadingProvince)
                ->where('unload_province', $unloadProvince)
                ->find();
            if (!$config){
                $pid = Db::name('user')->where('id', $userId)->value('pid');
                $config['logistics_cost_percentage'] = Db::name('user')->where('id',$pid)->value('percentage');
                if ( $config['logistics_cost_percentage'] == 0){
                    $config['logistics_cost_percentage'] = Config::get('site.LogisticsDriverFreight');
                }
            }
        }
        if ($config) {
            return [
                'logistics_cost_percentage' => floatval($config['logistics_cost_percentage'] ?? 0),
            ];
        }

        return false;
    }

    /**
     * 获取货物类型的价格上浮百分比
     * @param int $goodsTypeId 货物类型ID
     * @return float 返回百分比值，如果不存在或为0则返回0
     */
    private function getUserPlatformCommissionRate($userId)
    {
        if (empty($userId)) {
            return 0;
        }

        $commission = Db::name('user')->where('id', $userId)->value('platform_commission');
        if ($commission === null || $commission === '') {
            $commission = Config::get('site.platform_commission');
        }

        $commission = floatval($commission);
        return $commission > 0 ? $commission : 0;
    }

    /**
     * 获取货物类型的价格上浮百分比
     * @param int $goodsTypeId 货物类型ID
     * @return float 返回百分比值，如果不存在或为0则返回0
     */
    private function getGoodsTypePercentage($goodsTypeId)
    {
        if (empty($goodsTypeId)) {
            return 0;
        }
        
        $goodsType = Db::name('goods_type')->where('id', $goodsTypeId)->find();
        if (!$goodsType || empty($goodsType['percentage'])) {
            return 0;
        }
        
        return floatval($goodsType['percentage']);
    }

    /**
     * 相邻省份表（按简称）。候选专线需考虑“发货地周边（含相邻省份）”的线路，以便择优。
     *
     * @return array<string, string[]>
     */
    private function adjacentProvincesMap(): array
    {
        return [
            '北京' => ['天津', '河北'],
            '上海' => ['江苏', '浙江'],
            '天津' => ['北京', '河北'],
            '重庆' => ['四川', '贵州', '湖北', '湖南', '陕西'],
            '河北' => ['北京', '天津', '山西', '山东', '河南', '辽宁', '内蒙古'],
            '山西' => ['河北', '陕西', '内蒙古', '河南'],
            '内蒙古' => ['辽宁', '吉林', '河北', '山西', '陕西', '宁夏', '甘肃', '黑龙江'],
            '辽宁' => ['内蒙古', '吉林', '河北'],
            '吉林' => ['辽宁', '内蒙古', '黑龙江'],
            '黑龙江' => ['吉林', '内蒙古'],
            '江苏' => ['上海', '浙江', '安徽', '山东', '河南'],
            '浙江' => ['上海', '江苏', '安徽', '江西', '福建'],
            '安徽' => ['江苏', '浙江', '江西', '湖北', '河南', '山东'],
            '福建' => ['浙江', '江西', '广东'],
            '江西' => ['浙江', '安徽', '湖北', '湖南', '广东', '福建'],
            '山东' => ['河北', '河南', '安徽', '江苏'],
            '河南' => ['河北', '山西', '陕西', '湖北', '安徽', '山东'],
            '湖北' => ['河南', '陕西', '重庆', '湖南', '江西', '安徽'],
            '湖南' => ['湖北', '重庆', '贵州', '江西', '广东', '广西'],
            '广东' => ['福建', '江西', '湖南', '广西', '海南'],
            '广西' => ['广东', '湖南', '贵州', '云南'],
            '海南' => ['广东'],
            '四川' => ['重庆', '贵州', '云南', '西藏', '青海', '甘肃', '陕西'],
            '贵州' => ['重庆', '四川', '云南', '广西', '湖南', '湖北'],
            '云南' => ['四川', '贵州', '广西', '西藏'],
            '西藏' => ['新疆', '青海', '四川', '云南'],
            '陕西' => ['山西', '河南', '湖北', '四川', '甘肃', '宁夏', '内蒙古'],
            '甘肃' => ['陕西', '四川', '青海', '新疆', '内蒙古', '宁夏'],
            '青海' => ['甘肃', '四川', '西藏', '新疆'],
            '宁夏' => ['陕西', '甘肃', '内蒙古'],
            '新疆' => ['甘肃', '青海', '西藏'],
        ];
    }

    /**
     * 取“该省 + 相邻省份”的简称列表（用于发货端匹配）
     *
     * @return string[]
     */
    private function loadingProvinceScope(string $province): array
    {
        $province = $this->normalizeProvinceName($province);
        $out = array_merge([$province], $this->adjacentProvincesMap()[$province] ?? []);

        return array_values(array_unique(array_filter(array_map('trim', $out))));
    }

    /**
     * 省份名归一化：去掉 省/市/自治区/特别行政区 等后缀
     */
    private function normalizeProvinceName(string $province): string
    {
        return trim(str_replace(
            ['省', '市', '自治区', '壮族自治区', '回族自治区', '维吾尔自治区', '特别行政区'],
            '',
            $province
        ));
    }

    /**
     * 判断专线是否与装卸货城市/省份匹配
     */
    private function isLogisticsRouteMatched(array $logistics, array $routeContext)
    {
        $loadingCity = $routeContext['loading_city'] ?? '';
        $unloadCity = $routeContext['unload_city'] ?? '';
        $loadingProvince = $routeContext['loading_province'] ?? '';
        $unloadProvince = $routeContext['unload_province'] ?? '';

        if (!$loadingCity && !$unloadCity && !$loadingProvince && !$unloadProvince) {
            return true;
        }

        $shippingCity     = trim($logistics['origincity'] ?? '');
        $arrivalCity      = trim($logistics['destination'] ?? '');
        $shippingProvince = trim($logistics['shipping_province'] ?? '');
        $arrivalProvince  = trim($logistics['province'] ?? '');

        $shippingMatch = false;
        if ($loadingCity) {
            if ($shippingCity && mb_strpos($shippingCity, $loadingCity) !== false) {
                $shippingMatch = true;
            } elseif ($loadingProvince && $shippingProvince
                && in_array($this->normalizeProvinceName($shippingProvince), $this->loadingProvinceScope($loadingProvince), true)) {
                $shippingMatch = true;
            }
        } elseif ($loadingProvince) {
            if ($shippingProvince
                && in_array($this->normalizeProvinceName($shippingProvince), $this->loadingProvinceScope($loadingProvince), true)) {
                $shippingMatch = true;
            }
        } else {
            $shippingMatch = true;
        }

        $arrivalMatch = false;
        if ($unloadCity) {
            if ($arrivalCity && mb_strpos($arrivalCity, $unloadCity) !== false) {
                $arrivalMatch = true;
            } elseif ($unloadProvince && $arrivalProvince
                && in_array($this->normalizeProvinceName($arrivalProvince), $this->loadingProvinceScope($unloadProvince), true)) {
                $arrivalMatch = true;
            }
        } elseif ($unloadProvince) {
            if ($arrivalProvince
                && in_array($this->normalizeProvinceName($arrivalProvince), $this->loadingProvinceScope($unloadProvince), true)) {
                $arrivalMatch = true;
            }
        } else {
            $arrivalMatch = true;
        }

        return $shippingMatch && $arrivalMatch;
    }

    /**
     * 构建装卸货路线上下文
     */
    private function buildLogisticsRouteContext($loadingAddressStr, $unloadAddressStr)
    {
        return [
            'loading_city'     => $this->extractCityFromAddress($loadingAddressStr),
            'unload_city'      => $this->extractCityFromAddress($unloadAddressStr),
            'loading_province' => $this->extractProvinceFromAddress($loadingAddressStr),
            'unload_province'  => $this->extractProvinceFromAddress($unloadAddressStr),
        ];
    }

    /**
     * 按指定专线计算运费（传入 logistics_id 时可跳过全量专线扫描）
     */
    private function resolveLogisticsPricingById($logisticsId, $loadingId, $unloadId, $carTypeId, $goodsTypePercentage = 0, $userId = 0, $weight = null, $direction = null)
    {
        $loading = Db::name('user_address')->where('id', $loadingId)->find();
        $unload = Db::name('user_address')->where('id', $unloadId)->find();
        $carType = Db::name('car_type')->where('id', $carTypeId)->find();
        $logistics = Db::name('logistics')
            ->where('id', $logisticsId)
            ->where('logistics_status', 1)
            ->where('status', 2)
            ->find();

        if (!$loading || !$unload || !$carType || !$logistics) {
            return false;
        }
        if (empty($logistics['shipping_longitude']) || empty($logistics['shipping_latitude'])
            || empty($logistics['arrival_longitude']) || empty($logistics['arrival_latitude'])) {
            return false;
        }

        $loadingAddressStr = $loading['detailed_address'] ?? ($loading['address'] ?? '');
        $unloadAddressStr  = $unload['detailed_address'] ?? ($unload['address'] ?? '');

        $routeContext = $this->buildLogisticsRouteContext($loadingAddressStr, $unloadAddressStr);
        if (!$this->isLogisticsRouteMatched($logistics, $routeContext)) {
            return false;
        }

        $distancesReal = $this->calculateSegmentDistances($loading, $unload, $logistics, true);
        $totalDistanceReal = ($distancesReal['loading_to_start'] ?? 0) + ($distancesReal['logistics_line'] ?? 0) + ($distancesReal['end_to_unload'] ?? 0);
        $logisticsInfo = [
            'logistics_id'   => $logistics['id'],
            'logistics_name' => $logistics['name'] ?? '',
            'distances'      => $distancesReal,
            'total_distance' => $totalDistanceReal,
            'logistics_data' => $logistics,
        ]; 

        return $this->calculateLogisticsCost(
            $logisticsInfo,
            $carType,
            $distancesReal,
            $goodsTypePercentage,
            $loading,
            $unload,
            $userId,
            $weight,
            $direction
        );
    }

    /**
     * 匹配物流专线（与后台一致：按城市/省份筛选，按总价、总距离排序，取第一条）
     */
    public function matchLogistics($loadingId, $unloadId, $carTypeId, $goodsTypePercentage = 0)
    {

        $user = $this->auth->getUser();
        $userId = $user['id'] ?? 0;
        $candidates = $this->collectLogisticsCandidates(
            $loadingId,
            $unloadId,
            $carTypeId,
            $goodsTypePercentage,
            $userId
        );
//        print_r($candidates);die;
        if (empty($candidates)) {
            return false;
        }

        $loading = $candidates['loading'];
        $unload = $candidates['unload'];
        $carType = $candidates['car_type'];
        $selected = $candidates['list'][0]['logistics'];

        $distancesReal = $this->calculateSegmentDistances($loading, $unload, $selected, true);
        $totalDistanceReal = ($distancesReal['loading_to_start'] ?? 0) + ($distancesReal['logistics_line'] ?? 0) + ($distancesReal['end_to_unload'] ?? 0);
        $logisticsInfo = [
            'logistics_id'   => $selected['id'],
            'logistics_name' => $selected['name'] ?? '',
            'distances'      => $distancesReal,
            'total_distance' => $totalDistanceReal,
            'logistics_data' => $selected,
        ];
        return $this->calculateLogisticsCost(
            $logisticsInfo,
            $carType,
            $distancesReal,
            $goodsTypePercentage,
            $loading,
            $unload,
            $userId
        );
    }

    /**
     * 收集并排序物流专线候选（按三端总价升序，再按总距离升序）
     */
    private function collectLogisticsCandidates($loadingId, $unloadId, $carTypeId, $goodsTypePercentage = 0, $userId = 0, $weight = null, $direction = null)
    {
        $loading = Db::name('user_address')->where('id', $loadingId)->find();
        $unload = Db::name('user_address')->where('id', $unloadId)->find();
        $carType = Db::name('car_type')->where('id', $carTypeId)->find();

        if (!$loading || !$unload || !$carType) {
            return false;
        }

        $loadingAddressStr = $loading['detailed_address'] ?? ($loading['address'] ?? '');
        $unloadAddressStr  = $unload['detailed_address'] ?? ($unload['address'] ?? '');
        $routeContext = $this->buildLogisticsRouteContext($loadingAddressStr, $unloadAddressStr);

        $loadingCity     = $routeContext['loading_city'] ?? '';
        $unloadCity      = $routeContext['unload_city'] ?? '';
        $loadingProvince = $routeContext['loading_province'] ?? '';
        $unloadProvince  = $routeContext['unload_province'] ?? '';

        // 粗筛：发货/收货各方圆 300km 内（数值经纬度列走索引），再用 isLogisticsRouteMatched 精确判断城市/省份
        $radiusKm = 300;
        $loadLat = (float)($loading['lat'] ?? 0);
        $loadLng = (float)($loading['lng'] ?? 0);
        $unloLat = (float)($unload['lat'] ?? 0);
        $unloLng = (float)($unload['lng'] ?? 0);
        if ($loadLat == 0 || $loadLng == 0 || $unloLat == 0 || $unloLng == 0) {
            // 坐标缺失兜底：仍按在用物流粗筛，后续由 isLogisticsRouteMatched 精确判断城市/省份
            $logisticsList = Db::name('logistics')->where('logistics_status', 1)->where('status', 2)->select();
        } else {
            $radLatDeg = rad2deg($radiusKm / 6371.0);
            $cosLat = abs(cos(deg2rad($loadLat)));
            $radLngDeg = $cosLat > 0.01 ? rad2deg(($radiusKm / 6371.0) / $cosLat) : $radLatDeg * 4;
            $cosLat2 = abs(cos(deg2rad($unloLat)));
            $radLngDeg2 = $cosLat2 > 0.01 ? rad2deg(($radiusKm / 6371.0) / $cosLat2) : $radLatDeg * 4;
            $rawSql = "logistics_status = 1 AND status = 2"
                . " AND shipping_lat_n BETWEEN " . ($loadLat - $radLatDeg) . " AND " . ($loadLat + $radLatDeg)
                . " AND shipping_lng_n BETWEEN " . ($loadLng - $radLngDeg) . " AND " . ($loadLng + $radLngDeg)
                . " AND arrival_lat_n BETWEEN " . ($unloLat - $radLatDeg) . " AND " . ($unloLat + $radLatDeg)
                . " AND arrival_lng_n BETWEEN " . ($unloLng - $radLngDeg2) . " AND " . ($unloLng + $radLngDeg2)
                . " AND 6371 * ACOS(LEAST(1, COS(RADIANS($loadLat))*COS(RADIANS(shipping_lat_n))*COS(RADIANS(shipping_lng_n)-RADIANS($loadLng))+SIN(RADIANS($loadLat))*SIN(RADIANS(shipping_lat_n)))) <= $radiusKm"
                . " AND 6371 * ACOS(LEAST(1, COS(RADIANS($unloLat))*COS(RADIANS(arrival_lat_n))*COS(RADIANS(arrival_lng_n)-RADIANS($unloLng))+SIN(RADIANS($unloLat))*SIN(RADIANS(arrival_lat_n)))) <= $radiusKm";
            $logisticsQuery = Db::name('logistics')->whereRaw($rawSql);
            $logisticsList = $logisticsQuery->select();
        }
//        print_r($logisticsList);die;
        $candidates = [];
        foreach ($logisticsList as $logistics) {
            if (empty($logistics['shipping_longitude']) || empty($logistics['shipping_latitude'])) {
                continue;
            }
            if (empty($logistics['arrival_longitude']) || empty($logistics['arrival_latitude'])) {
                continue;
            }

            if (!$this->isLogisticsRouteMatched($logistics, $routeContext)) {
                continue;
            }

            $distances = $this->calculateSegmentDistances($loading, $unload, $logistics, false);
            $totalDistance = ($distances['loading_to_start'] ?? 0) + ($distances['logistics_line'] ?? 0) + ($distances['end_to_unload'] ?? 0);
            $logisticsInfo = [
                'logistics_id'   => $logistics['id'],
                'logistics_name' => $logistics['name'] ?? '',
                'distances'      => $distances,
                'total_distance' => $totalDistance,
                'logistics_data' => $logistics,
            ];
            $costResult = $this->calculateLogisticsCost(
                $logisticsInfo,
                $carType,
                $distances,
                $goodsTypePercentage,
                $loading,
                $unload,
                $userId,
                $weight,
                $direction
            );
            $totalPrice = ($costResult['logistics_cost'] ?? 0) + ($costResult['pickup_fee'] ?? 0) + ($costResult['shipment_fee'] ?? 0);
            $candidates[] = [
                'logistics'            => $logistics,
                'total_price_value'    => $totalPrice,
                'total_distance_value' => $totalDistance,
                'cost_result'          => $costResult,
            ];
        }

        if (empty($candidates)) {
            return false;
        }

        usort($candidates, function ($a, $b) {
            $priceCmp = ($a['total_price_value'] ?? PHP_FLOAT_MAX) <=> ($b['total_price_value'] ?? PHP_FLOAT_MAX);
            if ($priceCmp !== 0) {
                return $priceCmp;
            }
            return ($a['total_distance_value'] ?? 0) <=> ($b['total_distance_value'] ?? 0);
        });

        return [
            'loading'          => $loading,
            'unload'           => $unload,
            'car_type'         => $carType,
            'list'             => $candidates,
            'pricing_context'  => $this->buildLogisticsPricingContext($carType, $loading, $unload),
        ];
    }

    /**
     * 获取第2~15便宜的备选物流专线（与当前订单同路线、同排序规则）
     * @param int|null $excludeLogisticsId 排除当前已选专线
     */
    private function getAlternativeLogisticsList(array $orderContext, $excludeLogisticsId = null)
    {
        $goodsTypePercentage = $this->getGoodsTypePercentage($orderContext['goods_type_id'] ?? 0);
        $ranked = $this->collectLogisticsCandidates(
            $orderContext['loading'],
            $orderContext['unload'],
            $orderContext['car_type_id'],
            $goodsTypePercentage,
            $orderContext['userid'] ?? 0,
            $orderContext['weight'] ?? null,
            $orderContext['direction'] ?? null
        );
        if (!$ranked || empty($ranked['list'])) {
            return [];
        }

        $loading = $ranked['loading'];
        $unload = $ranked['unload'];
        $pricingContext = $ranked['pricing_context'];
        $alternatives = [];

        foreach (array_slice($ranked['list'], 0, 16) as $candidate) {
            $logistics = $candidate['logistics'];
            if ($excludeLogisticsId && (int)$logistics['id'] === (int)$excludeLogisticsId) {
                continue;
            }
            $distancesReal = $this->calculateSegmentDistances($loading, $unload, $logistics, true);
            $costResult = $this->applyDistanceToLogisticsCost(
                $candidate['cost_result'],
                $distancesReal,
                $pricingContext
            );
            $alternatives[] = $this->formatLogisticsDetailItem($logistics, $costResult);
            if (count($alternatives) >= 16) {
                break;
            }
        }

        return $alternatives;
    }

    /**
     * 构建订单物流匹配上下文
     */
    private function buildOrderLogisticsContext(array $order)
    {
        return [
            'loading'       => $order['loading'],
            'unload'        => $order['unload'],
            'car_type_id'   => $order['car_type_id'],
            'goods_type_id' => $order['goods_type_id'],
            'weight'        => $order['weight'],
            'direction'     => $order['direction'],
            'userid'        => $order['userid'],
        ];
    }

    /**
     * 获取订单可选物流专线 ID（排名前15）
     */
    private function getSelectableLogisticsIds(array $order, $limit = 15)
    {
        $context = $this->buildOrderLogisticsContext($order);
        $goodsTypePercentage = $this->getGoodsTypePercentage($context['goods_type_id'] ?? 0);
        $ranked = $this->collectLogisticsCandidates(
            $context['loading'],
            $context['unload'],
            $context['car_type_id'],
            $goodsTypePercentage,
            $context['userid'] ?? 0,
            $context['weight'] ?? null,
            $context['direction'] ?? null
        );
        if (!$ranked) {
            return [];
        }
        $ids = [];
        foreach (array_slice($ranked['list'], 0, $limit) as $candidate) {
            $ids[] = (int)$candidate['logistics']['id'];
        }
        return $ids;
    }

    /**
     * 计算指定专线的运费明细（须在可选排名前15内）
     */
    private function resolveLogisticsPricingForOrder(array $order, $logisticsId)
    {
        $context = $this->buildOrderLogisticsContext($order);
        $goodsTypePercentage = $this->getGoodsTypePercentage($context['goods_type_id'] ?? 0);
        $ranked = $this->collectLogisticsCandidates(
            $context['loading'],
            $context['unload'],
            $context['car_type_id'],
            $goodsTypePercentage,
            $context['userid'] ?? 0,
            $context['weight'] ?? null,
            $context['direction'] ?? null
        );
        if (!$ranked || empty($ranked['list'])) {
            $this->error('未找到合适的物流专线');
        }

        $allowedIds = [];
        foreach (array_slice($ranked['list'], 0, 15) as $candidate) {
            $allowedIds[] = (int)$candidate['logistics']['id'];
        }
        if (!in_array((int)$logisticsId, $allowedIds, true)) {
            $this->error('所选物流专线不在可选范围内');
        }

        foreach ($ranked['list'] as $candidate) {
            if ((int)$candidate['logistics']['id'] !== (int)$logisticsId) {
                continue;
            }
            $distancesReal = $this->calculateSegmentDistances(
                $ranked['loading'],
                $ranked['unload'],
                $candidate['logistics'],
                true
            );
            return $this->applyDistanceToLogisticsCost(
                $candidate['cost_result'],
                $distancesReal,
                $ranked['pricing_context']
            );
        }

        $this->error('物流专线不存在');
    }

    /**
     * 根据新专线运费重算订单应付金额与成本
     */
    private function buildOrderAmountsWithLogistics(array $order, array $logisticsPricing, $memberType = 0)
    {
        $pickupFee = round(floatval($logisticsPricing['pickup_fee'] ?? 0) * 1.09, 2);
        $shipmentFee = round(floatval($logisticsPricing['shipment_fee'] ?? 0) * 1.09, 2);
        $pickupDriverFee = round(floatval($logisticsPricing['pickup_driver_fee'] ?? 0) * 1.09, 2);
        $shipmentDriverFee = round(floatval($logisticsPricing['shipment_driver_fee'] ?? 0) * 1.09, 2);

        if ((int)($order['isrequirements'] ?? 0) === 1 && ($order['service'] ?? '') === '自提') {
            $shipmentFee = 0;
            $shipmentDriverFee = 0;
        }

        $basePayPrice = round(
            floatval($logisticsPricing['logistics_cost'] ?? 0) + $pickupFee + $shipmentFee,
            2
        );

        $receiptPrice = round(floatval($order['receipt_type_price'] ?? 0), 2);
        $unpackPrice = round(floatval($order['unpack_price'] ?? 0), 2);
        $infoFee = (isset($order['information']) && $order['information'] !== '') ? round(floatval($order['information']), 2) : 0;
        $depositFee = (isset($order['deposit']) && $order['deposit'] !== '') ? round(floatval($order['deposit']), 2) : 0;

        $payPrice = round($basePayPrice + $receiptPrice + $unpackPrice, 2);

        $commission = 0;
        $commissionRate = 0;
        if ((int)$memberType === 2) {
            $commissionRate = $this->getUserPlatformCommissionRate($order['userid'] ?? 0);
            if ($commissionRate <= 0) {
                $commissionRate = 5;
            }
        }
        if ($commissionRate > 0) {
            $commission = round($payPrice * ($commissionRate / 100), 2);
        }

        $costCont = round(
            floatval($logisticsPricing['logistics_driver_cost'] ?? 0)
            + $pickupDriverFee
            + $shipmentDriverFee
            + $receiptPrice
            + $unpackPrice
            + $infoFee
            + $depositFee
            + $commission,
            2
        );

        if (!empty($order['isinvoice']) && (int)$order['isinvoice'] === 1) {
            $taxRow = Db::name('tax')->where('orderid', $order['orderid'])->find();
            if ($taxRow && isset($taxRow['tax_point']) && $taxRow['tax_point'] !== '') {
                $taxPoint = floatval(str_replace('%', '', trim($taxRow['tax_point'])));
                if ($taxPoint > 0) {
                    $taxAmount = round($payPrice * ($taxPoint / 100), 2);
                    $payPrice = round($payPrice + $taxAmount, 2);
                    $costCont = round($costCont + $taxAmount, 2);
                }
            }
        }

        return [
            'logistics_id'          => $logisticsPricing['logistics_id'],
            'arrivaltime'           => $logisticsPricing['arrivaltime'] ?? '',
            'logistics_cost'        => round(floatval($logisticsPricing['logistics_cost'] ?? 0), 2),
            'logistics_driver_cost' => round(floatval($logisticsPricing['logistics_driver_cost'] ?? 0), 2),
            'pickup_driver_fee'     => $pickupDriverFee,
            'shipment_driver_fee'   => $shipmentDriverFee,
            'pickup_fee'            => $pickupFee,
            'shipment_fee'          => $shipmentFee,
            'pickup_distance'       => $logisticsPricing['pickup_distance'] ?? 0,
            'logistics_distance'    => $logisticsPricing['logistics_distance'] ?? 0,
            'shipmenty_distance'    => $logisticsPricing['shipmenty_distance'] ?? 0,
            'loading_to_start'      => $logisticsPricing['loading_to_start'] ?? 0,
            'pay_price'             => round($payPrice, 2),
            'cost_cont'             => round($costCont, 2),
            'shipping_cost'         => round($payPrice, 2),
            'platform_commission'   => round($commission, 2),
        ];
    }

    /**
     * 更换物流后返回给前端的专线信息
     */
    private function buildLogisticsChangeResponse(array $order)
    {
        $loghit = Db::name('logistics')->where('id', $order['logistics_id'])->find();
        $pickupDistance = floatval($order['pickup_distance'] ?? 0);
        $logisticsDistance = floatval($order['logistics_distance'] ?? 0);
        $shipmentDistance = floatval($order['shipmenty_distance'] ?? 0);

        $response = [
            'orderid'            => $order['orderid'],
            'logistics_id'       => (int)$order['logistics_id'],
            'pay_price'          => $order['pay_price'],
            'logistics_cost'     => $order['logistics_cost'] ?? 0,
            'pickup_fee'         => $order['pickup_fee'] ?? 0,
            'shipment_fee'       => $order['shipment_fee'] ?? 0,
            'pickup_distance'    => $order['pickup_distance'] ?? 0,
            'logistics_distance' => $order['logistics_distance'] ?? 0,
            'shipmenty_distance' => $order['shipmenty_distance'] ?? 0,
            'distance_cont'      => round($pickupDistance + $logisticsDistance + $shipmentDistance, 2),
            'arrivaltime'        => $order['arrivaltime'] ?? '',
        ];

        if ($loghit) {
            $response['logistics_name']   = $loghit['name'] ?? '';
            $response['shipping_name']    = $loghit['shipping_logistics_name'] ?? '';
            $response['shipping_address'] = $loghit['shipping_logistics_address'] ?? '';
            $response['shipping_mobile']  = $loghit['shipping_logistics_mobile'] ?? '';
            $response['arrival_name']     = $loghit['arrival_logistics_name'] ?? '';
            $response['arrival_address']  = $loghit['arrival_logistics_address'] ?? '';
        }

        $response['alternative_logistics_list'] = $this->getAlternativeLogisticsList(
            $this->buildOrderLogisticsContext($order),
            $order['logistics_id']
        );

        return $response;
    }

    /**
     * 同步专线司机订单（更换物流时更新待接单记录）
     */
    private function syncDedicatedOrder($orderId, $logisticsId, $logisticsDriverCost)
    {
        $existing = Db::name('dricerorder')->where('order_id', $orderId)->where('type', 2)->find();
        if ($existing) {
            if ((int)$existing['status'] === 3) {
                Db::name('dricerorder')->where('id', $existing['id'])->update([
                    'price' => $logisticsDriverCost,
                ]);
            }
            return;
        }
        $this->createDedicatedOrder($orderId, $logisticsId);
    }

    /**
     * 格式化物流专线详情（与订单详情主专线字段结构一致）
     */
    private function formatLogisticsDetailItem(array $logistics, array $costResult)
    {
        $pickupDistance = $costResult['pickup_distance'] ?? 0;
        $logisticsDistance = $costResult['logistics_distance'] ?? 0;
        $shipmentDistance = $costResult['shipmenty_distance'] ?? 0;
        $totalPrice = round(
            ($costResult['logistics_cost'] ?? 0) + ($costResult['pickup_fee'] ?? 0) + ($costResult['shipment_fee'] ?? 0),
            2
        );

        return [
            'logistics_id'       => $costResult['logistics_id'] ?? ($logistics['id'] ?? 0),
            'logistics_name'     => $logistics['name'] ?? '',
            'shipping_address'   => $logistics['shipping_logistics_address'] ?? '',
            'shipping_name'      => $logistics['shipping_logistics_name'] ?? '',
            'shipping_mobile'    => $logistics['shipping_logistics_mobile'] ?? '',
            'arrival_address'    => $logistics['arrival_logistics_address'] ?? '',
            'arrival_name'       => $logistics['arrival_logistics_name'] ?? '',
            'arrivaltime'        => $costResult['arrivaltime'] ?? ($logistics['time_limit'] ?? ''),
            'total_price'        => $totalPrice,
            'level'              => $logistics['level'] ?? '',
            'logistics_cost'     => $costResult['logistics_cost'] ?? 0,
            'pickup_fee'         => $costResult['pickup_fee'] ?? 0,
            'shipment_fee'       => $costResult['shipment_fee'] ?? 0,
            'pickup_distance'    => $pickupDistance,
            'logistics_distance' => $logisticsDistance,
            'shipmenty_distance' => $shipmentDistance,
            'distance_cont'      => round($pickupDistance + $logisticsDistance + $shipmentDistance, 2),
        ];
    }

    /**
     * 检查距离匹配
     */
    private function checkDistanceMatch($lat1, $lng1, $lat2, $lng2, $maxDistance)
    {
        if (empty($lat1) || empty($lng1) || empty($lat2) || empty($lng2)) {
            return false;
        }

        $distance = calculateDistance($lat1, $lng1, $lat2, $lng2);
        return $distance <= $maxDistance;
    }

    /**
     * 计算三段距离
     */
    private function calculateSegmentDistances($loading, $unload, $logistics, $preferDrivingDistance = false)
    {
        if ($preferDrivingDistance) {
            $segments = [
                [
                    $loading['lat'], $loading['lng'],
                    $logistics['shipping_latitude'], $logistics['shipping_longitude'],
                ],
                [
                    $unload['lat'], $unload['lng'],
                    $logistics['arrival_latitude'], $logistics['arrival_longitude'],
                ],
            ];
            list($segment1, $segment3) = calculateDrivingDistancesParallel($segments);
            return [
                'loading_to_start' => $segment1,
                'logistics_line'   => $logistics['distance'] ?? 0,
                'end_to_unload'    => $segment3,
            ];
        }

        $segment1 = $this->checkDistanceMatch(
            $loading['lat'], $loading['lng'],
            $logistics['shipping_latitude'], $logistics['shipping_longitude'],
            PHP_FLOAT_MAX
        ) ? calculateDistance(
            $loading['lat'], $loading['lng'],
            $logistics['shipping_latitude'], $logistics['shipping_longitude']
        ) : 0;
        $segment2 = $logistics['distance'] ?? 0;

        $segment3 = $this->checkDistanceMatch(
            $unload['lat'], $unload['lng'],
            $logistics['arrival_latitude'], $logistics['arrival_longitude'],
            PHP_FLOAT_MAX
        ) ? calculateDistance(
            $unload['lat'], $unload['lng'],
            $logistics['arrival_latitude'], $logistics['arrival_longitude']
        ) : 0;

        return [
            'loading_to_start' => $segment1,
            'logistics_line' => $segment2,
            'end_to_unload' => $segment3
        ];
    }
    /**
     * 根据需求返回驾车距离或直线距离，并做简单缓存避免重复调用
     */
    private function resolveDistance($lat1, $lng1, $lat2, $lng2, $preferDrivingDistance = false)
    {
        if (!$preferDrivingDistance) {
            return calculateDistance($lat1, $lng1, $lat2, $lng2);
        }

        static $distanceCache = [];
        $cacheKey = implode(':', [
            round($lat1, 5),
            round($lng1, 5),
            round($lat2, 5),
            round($lng2, 5)
        ]);

        if (!isset($distanceCache[$cacheKey])) {
            $distanceCache[$cacheKey] = calculateDrivingDistance($lat1, $lng1, $lat2, $lng2);
        }

        return $distanceCache[$cacheKey];
    }

    /**
     * 从地址中提取省份
     * @param string $address 完整地址
     * @return string 省份名称（不带"省"字，如：山东、北京）
     */
    private function extractProvinceFromAddress($address)
    {
        return extractAddressProvince($address);
    }

    /**
     * 从地址字符串中提取城市（与后台路线筛选逻辑一致）
     * @param string $address 地址字符串
     * @return string 城市名称
     */
    private function extractCityFromAddress($address)
    {
        $address = trim((string)$address);
        if ($address === '') {
            return '';
        }
        $municipalities = ['北京', '上海', '天津', '重庆'];
        foreach ($municipalities as $m) {
            if (mb_strpos($address, $m . '市') === 0 || mb_strpos($address, $m) === 0) {
                return $m;
            }
        }
        $core = $address;
        if (preg_match('/^(.*?(?:省|自治区))(.*)$/u', $address, $m)) {
            $core = trim($m[2]);
        }
        if (preg_match('/^([\x{4e00}-\x{9fa5}]{2,8}?)(?:市|地区|自治州|盟)/u', $core, $m2)) {
            return trim($m2[1]);
        }
        if (preg_match('/([\x{4e00}-\x{9fa5}]{2,8}?)(?:市|地区|自治州|盟)/u', $address, $m3)) {
            return trim($m3[1]);
        }
        return '';
    }

    /**
     * 根据车型type和省份查询provinceprice表获取价格
     * @param int $carTypeType 车型的type值
     * @param string $province 省份名称
     * @return array|false 返回包含startingfare和price的数组，如果未找到返回false
     */
    private function getProvincePrice($carTypeType, $province)
    {
        static $priceCache = [];
        $cacheKey = $carTypeType . '|' . $province;
        if (array_key_exists($cacheKey, $priceCache)) {
            return $priceCache[$cacheKey];
        }
        if (empty($carTypeType) || empty($province)) {
            $priceCache[$cacheKey] = false;
            return false;
        }
//        print_r($carTypeType);die;
        $provincePrice = Db::name('provinceprice')
            ->where('car_type', $carTypeType)
            ->where('city', 'like','%'.$province.'%')
            ->find();
//        print_r($provincePrice);die;
        if ($provincePrice) {
            // 处理字段名大小写问题，兼容不同的命名方式
            $startingfare = $provincePrice['Startingfare'] ?? $provincePrice['startingfare'] ?? 0;
            $price = $provincePrice['Price'] ?? $provincePrice['price'] ?? 0;
            $result = [
                'startingfare' => floatval($startingfare),
                'price' => floatval($price)
            ];
            $priceCache[$cacheKey] = $result;

            return $result;
        }
        $priceCache[$cacheKey] = false;

        return false;
    }

    /**
     * 构建物流计价上下文（取送省份价格 + 上浮配置）
     */
    private function buildLogisticsPricingContext(array $carType, array $loading, array $unload)
    {
        $carTypeType = $carType['type'] ?? 0;

        $loadingProvince = '';
        if (!empty($loading['detailed_address'])) {
            $loadingProvince = $this->extractProvinceFromAddress($loading['detailed_address']);
        }
        if ($loadingProvince === '') {
            $loadingProvince = $this->extractProvinceFromAddress($loading['address'] ?? '');
        }
        if ($loadingProvince === '') {
            $this->error('装货地址信息不全，缺少省份信息');
        }

        $unloadProvince = '';
        if (!empty($unload['detailed_address'])) {
            $unloadProvince = $this->extractProvinceFromAddress($unload['detailed_address']);
        }
        if ($unloadProvince === '') {
            $unloadProvince = $this->extractProvinceFromAddress($unload['address'] ?? '');
        }
        if ($unloadProvince === '') {
            $this->error('卸货地址信息不全，缺少省份信息');
        }

        $pickupPrice = $this->getProvincePrice($carTypeType, $loadingProvince);
        if (!$pickupPrice) {
            $pickupPrice = [
                'startingfare' => floatval($carType['Startingfare'] ?? $carType['startingfare'] ?? 0),
                'price'        => floatval($carType['Price'] ?? $carType['price'] ?? 0),
            ];
        }

        $shipmentPrice = $this->getProvincePrice($carTypeType, $unloadProvince);
        if (!$shipmentPrice) {
            $shipmentPrice = [
                'startingfare' => floatval($carType['Startingfare'] ?? $carType['startingfare'] ?? 0),
                'price'        => floatval($carType['Price'] ?? $carType['price'] ?? 0),
            ];
        }

        return [
            'pickup_price'   => $pickupPrice,
            'shipment_price' => $shipmentPrice,
            'freight_config' => [
                'pickup'    => Config::get('site.PickUpDriverFreight') ?: 0,
                'logistics' => Config::get('site.LogisticsDriverFreight') ?: 0,
                'delivery'  => Config::get('site.DeliveryDriverFreight') ?: 0,
            ],
        ];
    }

    /**
     * 驾车距离更新后，仅重算取送段费用
     */
    private function applyDistanceToLogisticsCost(array $costResult, array $distances, array $pricingContext)
    {
        $pickupPrice = $pricingContext['pickup_price'];
        $shipmentPrice = $pricingContext['shipment_price'];
        $freightConfig = $pricingContext['freight_config'];

        if ($distances['loading_to_start'] > 5) {
            $pickupDriverFee = $pickupPrice['startingfare'] + ($distances['loading_to_start'] - 5) * $pickupPrice['price'];
        } else {
            $pickupDriverFee = $pickupPrice['startingfare'];
        }

        if ($distances['end_to_unload'] > 5) {
            $shipmentDriverFee = $shipmentPrice['startingfare'] + ($distances['end_to_unload'] - 5) * $shipmentPrice['price'];
        } else {
            $shipmentDriverFee = $shipmentPrice['startingfare'];
        }

        $costResult['pickup_driver_fee'] = round($pickupDriverFee, 2);
        $costResult['shipment_driver_fee'] = round($shipmentDriverFee, 2);
        $costResult['pickup_fee'] = round($pickupDriverFee * (1 + $freightConfig['pickup'] / 100), 2);
        $costResult['shipment_fee'] = round($shipmentDriverFee * (1 + $freightConfig['delivery'] / 100), 2);
        $costResult['loading_to_start'] = $distances['loading_to_start'];
        $costResult['pickup_distance'] = round($distances['loading_to_start'], 2);
        $costResult['logistics_distance'] = round($distances['logistics_line'], 2);
        $costResult['shipmenty_distance'] = round($distances['end_to_unload'], 2);

        return $costResult;
    }

    /**
     * 计算物流费用
     */
    private function calculateLogisticsCost($logisticsInfo, $carType, $distances, $goodsTypePercentage = 0, $loading = null, $unload = null, $userId = 0, $weight = null, $direction = null)
    {
        $logistics = $logisticsInfo['logistics_data'];
        $weight = $weight !== null ? $weight : $this->request->param('weight');
        $direction = $direction !== null ? $direction : $this->request->param('direction');
        $priceResult = calculatePrice(
            $weight,
            $direction,
            $logistics['perton'],
            $logistics['side'],
            $logistics['reflux'],
            $logistics['bulky'],
        );

        $PickUpDriverFreight = Config::get('site.PickUpDriverFreight') ?: 0;
        $LogisticsDriverFreight = Config::get('site.LogisticsDriverFreight') ?: 0;
        $DeliveryDriverFreight = Config::get('site.DeliveryDriverFreight') ?: 0;
        // 获取车型的type值
        $carTypeType = $carType['type'] ?? 0;
        // 从地址中提取省份
        $loadingProvince = '';
        $unloadProvince = '';
//        print_r($loading);die;
        if (!empty($loading['detailed_address'])) {
            $loadingProvince = $this->extractProvinceFromAddress($loading['detailed_address']);
        }
        if (empty($loadingProvince)) {
            $loadingProvince = $this->extractProvinceFromAddress($loading['address']);
        }
        if (empty($loadingProvince)) {
            $this->error('装货地址信息不全，缺少省份信息');
        }
        if (!empty($unload['detailed_address'])) {
            $unloadProvince = $this->extractProvinceFromAddress($unload['detailed_address']);
        }
        if (empty($unloadProvince)) {
            $unloadProvince = $this->extractProvinceFromAddress($unload['address']);
        }
        if (empty($unloadProvince)) {
            $this->error('卸货地址信息不全，缺少省份信息');
        }
        // 根据发货省份查询取货价格
        $pickupPrice = $this->getProvincePrice($carTypeType, $loadingProvince);

        // 如果未找到，使用车型表的默认价格作为备用
        if (!$pickupPrice) {
            // 处理字段名大小写问题
            $carStartingfare = $carType['Startingfare'] ?? $carType['startingfare'] ?? 0;
            $carPrice = $carType['Price'] ?? $carType['price'] ?? 0;
            $pickupPrice = [
                'startingfare' => floatval($carStartingfare),
                'price' => floatval($carPrice)
            ];
        }
        // 根据到货省份查询送货价格
        $shipmentPrice = $this->getProvincePrice($carTypeType, $unloadProvince);
//        print_r($shipmentPrice);die;
        // 如果未找到，使用车型表的默认价格作为备用
        if (!$shipmentPrice) {
            // 处理字段名大小写问题
            $carStartingfare = $carType['Startingfare'] ?? $carType['startingfare'] ?? 0;
            $carPrice = $carType['Price'] ?? $carType['price'] ?? 0;
            $shipmentPrice = [
                'startingfare' => floatval($carStartingfare),
                'price' => floatval($carPrice)
            ];
        }
        // 计算取货费用（使用发货省份的价格）
        if ($distances['loading_to_start'] > 5) {
            $pickupDriverFee = $pickupPrice['startingfare'] + ($distances['loading_to_start'] - 5) * $pickupPrice['price'];
        } else {
            $pickupDriverFee = $pickupPrice['startingfare'];
        }
        // 计算送货费用（使用到货省份的价格）
        if ($distances['end_to_unload'] > 5) {
            $shipmentDriverFee = $shipmentPrice['startingfare'] + ($distances['end_to_unload'] - 5) * $shipmentPrice['price'];
        } else {
            $shipmentDriverFee = $shipmentPrice['startingfare'];
        }

        //取货司机价格（基础费 * (1 + 取货上浮%)）
        $pickupFee = $pickupDriverFee * (1 + $PickUpDriverFreight / 100);
        //送货司机价格（基础费 * (1 + 送货上浮%)）
        $shipmentFee = $shipmentDriverFee * (1 + $DeliveryDriverFreight / 100);
//
        //专线物流成本（给客户）：底价 * (1 + 专线上浮%)
        $logisticsCost = $priceResult['price'] * (1 + $LogisticsDriverFreight / 100);
        //专线司机成本：底价 × (1 + 货物类型%)
        $logistics_driver_cost = $priceResult['price'];
//        print_r($logisticsCost); echo '----';print_r  ($logistics_driver_cost);die;
        if ($goodsTypePercentage > 0) {
            $logistics_driver_cost = $logistics_driver_cost * (1 + $goodsTypePercentage / 100);
        }
        // 应用货物类型价格上浮到专线物流成本（给客户）
        if ($goodsTypePercentage > 0) {
            $logisticsCost = $logisticsCost * (1 + $goodsTypePercentage / 100);
        }
        return [
            'logistics_id' => $logisticsInfo['logistics_id'],
            'arrivaltime' => $logistics['time_limit'],
            'logistics_cost' => round($logisticsCost, 2),
            'logistics_driver_cost' => round($logistics_driver_cost,2),
            'pickup_driver_fee' =>round($pickupDriverFee,2) ,
            'shipment_driver_fee' => round($shipmentDriverFee,2) ,
            'pickup_fee' => round($pickupFee, 2),
            'shipment_fee' => round($shipmentFee, 2),
            'loading_to_start' => $distances['loading_to_start'], // 取货地到专线起点距离（供时间等计算）
            'pickup_distance' => round($distances['loading_to_start'], 2), // 取货地到专线起点的距离
            'logistics_distance' => round($distances['logistics_line'], 2), // 专线距离
            'shipmenty_distance' => round($distances['end_to_unload'], 2) // 专线终点到卸货地的距离
        ];
    }
    /**
     * 创建发票记录
     */
    private function createInvoice($data, $userId, $orderNumber)
    {
        $taxData = [
            'uid' => $userId,
            'orderid' => $orderNumber,
            'type' => $data['type'],
            'tax_point' => $data['tax_point'],
            'company_letterhead' => $data['company_letterhead'],
            'company_email' => $data['company_email']??'',
            'company_tax_id' => $data['company_tax_id']??'',
            'company_mobile' => $data['company_mobile']??'',
            'bank_deposits' => $data['bank_deposits']??'',
            'createtime' => time()
        ];

        return Db::name('tax')->insert($taxData);
    }

    /**
     * 创建代收货款记录
     */
    private function createCharge($data, $orderNumber)
    {
        $chargeData = [
            'orderid' => $orderNumber,
            'behalf_price' => $data['behalf_price'],
            'bank_branch' => $data['bank_branch'],
            'open_number' => $data['open_number'],
            'bank_type' => $data['bank_type'],
            'open_name' => $data['open_name'],
            'open_type' => $data['open_type'],
            'charge' => $data['charge'],
            'createtime' => time()
        ];

        return Db::name('charge')->insert($chargeData);
    }


    /**
     * @return string
     * 生成订单号
     */
    private function generateOrderNumber()
    {

        // 获取随机数（4位）
        $randomPart = str_pad(rand(1000000000, 9999999999), 4, '0', STR_PAD_LEFT);
        // 组合订单号
        $orderNumber = 'LZ'.'66'.$randomPart;
        return $orderNumber;
    }
    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 订单列表
     */
    public function orderlist()
    {
        $user = $this->auth->getUser();
        if (!$user) {
            $this->error('请登录');
        }
        // 前端传入的筛选状态（1/2/3），不再直接等同于订单表里的 pay_status
        $status = $this->request->param('type', 0);
        if ($status === '' || $status === null) {
            $status = 0;
        }
        // 基础查询：当前用户的所有订单
        if ($status == 0) {
            $data = Db::name('order')
                ->where('userid', $user['id'])
                ->order('createtime desc')
                ->where('pay_status','<>',5)
                ->select();
        } else {
            // 按照需求用 dricerorder 关联筛选
            if ($status == 1) {
                // 1：查询在 dricerorder 中不存在记录的订单
                $data = Db::name('order')
                    ->where('userid', $user['id'])
                    ->where('pay_status',1)
                    ->order('createtime desc')
                    ->select();
            } elseif ($status == 2) {
                // 2：查询在 dricerorder 中有一条 type = 1 的订单
                $data = Db::name('order')
                    ->where('userid', $user['id'])
                    ->where('logistics_status','<>',1)
                    ->where('logistics_status','<>',7)
                    ->order('createtime desc')
                    ->select();
            } elseif ($status == 3) {
                // 3：查询在 dricerorder 中有一条 type = 3 且 status = 2 的订单
                $data = Db::name('order')
                    ->where('userid', $user['id'])
                    ->where('pay_status',3)
                    ->where('logistics_status',7)
                    ->order('createtime desc')
                    ->select();
            } else {
                // 其他未定义状态，默认返回全部
                $data = Db::name('order')
                    ->where('userid', $user['id'])
                    ->order('createtime desc')
                    ->select();
            }
        }
        foreach ($data as &$v) {
            if ($v['find_car_type'] != '配车') {
                if ($v['pay_price'] == '0.00') {
                    $v['pay_price'] = '价格测算中';
                    $v['pay_status'] = 6;
                }else{
                    $admin_order = Db::name('admin_order')->where('order_id', $v['id'])->find();
                    if ($admin_order['status'] == 1 && $v['pay_status'] == 6) {
                        $v['pay_status'] = 7;
                    }
                }
            }
            $v['createtime'] = date('Y-m-d H:i:s', $v['createtime']);
            $v['loading_address'] = Db::name('user_address')
                ->where('id', $v['loading'])
                ->field('id,user_name,mobile,address,detailed_address,lng,lat')
                ->find();
            $v['unload_address'] = Db::name('user_address')
                ->field('id,user_name,mobile,address,detailed_address,lng,lat')
                ->where('id', $v['unload'])
                ->find();
            $v['membertype'] = $user['membertype'];
        }
        if ($data) {
            $this->success('查询成功', $data);
        } else {
            $this->error('暂无数据');
        }
    }

    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 订单详情
     */
    public function orderdetaile(){
        $order_id = $this->request->param('order_id');
        $data = Db::name('order')->where('orderid',$order_id)->find();
        if (empty($data)){
            $this->error('订单号有误');
        }
        $user = $this->auth->id;
        $membertype = Db::name('user')->where('id', $user)->value('membertype');

        // 再来一单：在覆盖为展示值之前，保存表单所需的原始 ID/值，供前端直接用于下单
        $reorder_data = [
            'loading' => $data['loading'],
            'unload' => $data['unload'],
            'find_car_type' => $data['find_car_type'],
            'quantity' => $data['quantity'],
            'weight' => $data['weight'],
            'long' => $data['long'],
            'wide' => $data['wide'],
            'hige' => $data['hige'],
            'direction' => $data['direction'],
            'isinvoice' => isset($data['isinvoice']) ? $data['isinvoice'] : 0,
            'goods_type_id' => $data['goods_type_id'],
            'packaging_id' => $data['packaging_id'],
            'pay_type' => $data['pay_type'],
            'delivery' => $data['delivery'],
            'car_type_id' => $data['car_type_id'],
            'earliest_time' => isset($data['earliest_time']) ? date('Y-m-d H:i', $data['earliest_time']) : '',
            'latest_time' => isset($data['latest_time']) ? date('Y-m-d H:i', $data['latest_time']) : '',
            'isrequirements' => isset($data['isrequirements']) ? $data['isrequirements'] : 0,
            'delivery_type_id' => isset($data['delivery_type_id']) ? $data['delivery_type_id'] : '',
            'receipt_type_id' => isset($data['receipt_type_id']) ? $data['receipt_type_id'] : '',
            'receipt_type_price' => isset($data['receipt_type_price']) ? $data['receipt_type_price'] : '',
            'service' => isset($data['service']) ? $data['service'] : '',
            'unpack_id' => isset($data['unpack_id']) ? $data['unpack_id'] : '',
            'unpack_num' => isset($data['unpack_num']) ? $data['unpack_num'] : '',
            'unpack_price' => isset($data['unpack_price']) ? $data['unpack_price'] : '',
            'control' => isset($data['control']) ? $data['control'] : '',
            'text_message' => isset($data['text_message']) && $data['text_message'] !== '' ? $data['text_message'] : '',
            'break' => isset($data['break']) ? $data['break'] : '',
            'goods_name' => isset($data['goods_name']) ? $data['goods_name'] : '',
            'goods_image' => isset($data['goods_image']) ? $data['goods_image'] : '',
            'information' => isset($data['information']) ? $data['information'] : '',
            'information_image' => isset($data['information_image']) ? $data['information_image'] : '',
            'deposit' => isset($data['deposit']) ? $data['deposit'] : '',
            'deliveryrequirements' => isset($data['deliveryrequirements_id']) ? $data['deliveryrequirements_id'] : '',
            'loadingrequirements' => isset($data['loadingrequirements_id']) ? $data['loadingrequirements_id'] : '',
            'pay_price' => isset($data['pay_price']) ? $data['pay_price'] : '',
        ];
        // other_id 可能为逗号分隔的多个 ID，下单接口需要 other_list 数组
        if (!empty($data['other_id'])) {
            $reorder_data['other_id'] = $data['other_id'];
            $reorder_data['other_list'] = is_string($data['other_id']) ? array_map('trim', explode(',', $data['other_id'])) : (array)$data['other_id'];
        } else {
            $reorder_data['other_id'] = '';
            $reorder_data['other_list'] = [];
        }
        // 开票信息在 fa_tax 表，按订单号取
        if (!empty($data['isinvoice']) && (int)$data['isinvoice'] === 1) {
            $taxRow = Db::name('tax')->where('orderid', $data['orderid'])->find();
            if ($taxRow) {
                $reorder_data['tax_point'] = isset($taxRow['tax_point']) ? $taxRow['tax_point'] : '';
                $reorder_data['type'] = isset($taxRow['type']) ? $taxRow['type'] : '';
                $reorder_data['company_letterhead'] = isset($taxRow['company_letterhead']) ? $taxRow['company_letterhead'] : '';
                $reorder_data['company_email'] = isset($taxRow['company_email']) ? $taxRow['company_email'] : '';
                $reorder_data['company_tax_id'] = isset($taxRow['company_tax_id']) ? $taxRow['company_tax_id'] : '';
                $reorder_data['company_mobile'] = isset($taxRow['company_mobile']) ? $taxRow['company_mobile'] : '';
                $reorder_data['bank_deposits'] = isset($taxRow['bank_deposits']) ? $taxRow['bank_deposits'] : '';
            }
        }
        // 代收货款在 fa_charge 表
        if (!empty($data['delivery']) && (int)$data['delivery'] === 1) {
            $chargeRow = Db::name('charge')->where('orderid', $data['orderid'])->find();
            if ($chargeRow) {
                $reorder_data['behalf_price'] = isset($chargeRow['behalf_price']) ? $chargeRow['behalf_price'] : '';
                $reorder_data['bank_branch'] = isset($chargeRow['bank_branch']) ? $chargeRow['bank_branch'] : '';
                $reorder_data['open_number'] = isset($chargeRow['open_number']) ? $chargeRow['open_number'] : '';
                $reorder_data['bank_type'] = isset($chargeRow['bank_type']) ? $chargeRow['bank_type'] : '';
                $reorder_data['open_name'] = isset($chargeRow['open_name']) ? $chargeRow['open_name'] : '';
                $reorder_data['open_type'] = isset($chargeRow['open_type']) ? $chargeRow['open_type'] : '';
                $reorder_data['charge'] = isset($chargeRow['charge']) ? $chargeRow['charge'] : '';
            }
        }
        $reorder_data['sizeList'] = Db::name('dimensions')->where('order_id', $data['orderid'])->select();
        $reorder_data['packaging_list'] = Db::name('packaging_num')->where('order_id', $data['orderid'])->select();
        $data['reorder_data'] = $reorder_data;

        $data['createtime'] = date('Y-m-d H:i:s',$data['createtime']);
        $data['loading_address'] = Db::name('user_address')
            ->where('id',$data['loading'])
            ->field('id,user_name,mobile,address,detailed_address,lng,lat')
            ->find();
        $data['unload_address'] = Db::name('user_address')
            ->field('id,user_name,mobile,address,detailed_address,lng,lat')
            ->where('id',$data['unload'])
            ->find();
        $data['goods_type_id'] = Db::name('goods_type')->where('id',$data['goods_type_id'])->value('name');
        $data['packaging_id'] = Db::name('packaging')->where('id',$data['packaging_id'])->value('name');
        if($data['pay_type'] == 1){
            $data['pay_type'] = '到付';
        }elseif($data['pay_type'] == 2){
            $data['pay_type'] = '月结';
        }else{
            $data['pay_type'] = '寄付';
        }
        if($data['delivery'] == 1){
            $data['delivery'] = '代收货款';
        }else{
            $data['delivery'] = '不代收货款';
        }
        $data['delivery_type_id'] = Db::name('delivery_type')->where('id',$data['delivery_type_id'])->value('name');
        $data['receipt_type_id']  = Db::name('receipt_type')->where('id',$data['receipt_type_id'])->value('name');
        $data['unpack_id'] = Db::name('unpack')->where('id',$data['unpack_id'])->value('name');
        $data['other_id'] = Db::name('other')->where('id',$data['other_id'])->value('name');
        if($data['text_message'] == 1){
            $data['text_message'] = '短信通知收件方';
        }elseif($data['text_message'] == 2){
            $data['text_message'] = '短信通知发件方';
        }else{
            $data['text_message'] = '';
        }
        if($data['pay_status'] == 1){
            $data['pay_status_name'] = '待付款';
        }elseif ($data['pay_status'] == 2){
            $data['pay_status_name'] = '进行中';
        }elseif ($data['pay_status'] == 3){
            $data['pay_status_name'] = '已完成';
        }elseif ($data['pay_status'] == 4){
            $data['pay_status_name'] = '已取消';
        }elseif ($data['pay_status'] == 5){
            $data['pay_status_name'] = '待下单';
        }elseif ($data['pay_status'] == 6){
            $data['pay_status_name'] = '确认价格';
        }elseif ($data['pay_status'] == 7){
            $data['pay_status_name'] = '已出价';
        }elseif ($data['pay_status'] == 8){
            $data['pay_status_name'] = '驳回';
        }
        // 驳回时返回驳回原因与驳回项，前端修改订单时可仅展示该项
        $data['reject'] = isset($data['reject']) ? $data['reject'] : '';
        $data['reject_field'] = isset($data['reject_field']) ? $data['reject_field'] : '';
        $data['car_type_id'] = Db::name('car_type')->where('id',$data['car_type_id'])->value('name');
        $data['earliest_time'] = date('Y-m-d H:i',$data['earliest_time']);
        $data['latest_time'] = date('Y-m-d H:i',$data['latest_time']);
        $data['sizeList'] = Db::name('dimensions')->where('order_id',$data['orderid'])->select();
        $data['packaging_list'] = Db::name('packaging_num')->where('order_id',$data['orderid'])->select();
        $data['membertype'] = $membertype;
        $data['platform_commission_rate'] = $this->getUserPlatformCommissionRate($data['userid'] ?? 0);
        if($data['find_car_type'] == '配车'){
            $loghit = Db::name('logistics')->where('id',$data['logistics_id'])->find();
            $data['distance_cont']  = $data['logistics_distance'] + $data['pickup_distance'] + $data['shipmenty_distance'];
            if ($loghit) {
                $data['shipping_address'] = $loghit['shipping_logistics_address'];
                $data['shipping_name'] = $loghit['shipping_logistics_name'];
                $data['shipping_mobile'] = $loghit['shipping_logistics_mobile'];
                $data['arrival_address'] = $loghit['arrival_logistics_address'];
                $data['arrival_name'] = $loghit['arrival_logistics_name'] ?? '';
                $data['level'] = $loghit['level'] ?? '';
            }
            $data['alternative_logistics_list'] = $this->getAlternativeLogisticsList([
                'loading'       => $reorder_data['loading'],
                'unload'        => $reorder_data['unload'],
                'car_type_id'   => $reorder_data['car_type_id'],
                'goods_type_id' => $reorder_data['goods_type_id'],
                'weight'        => $reorder_data['weight'],
                'direction'     => $reorder_data['direction'],
                'userid'        => $data['userid'],
            ], $data['logistics_id']);
        }else{
            $data['deliveryinfo'] = Db::name('deliveryinfo')->where('order_id',$data['id'])->find();
        }
        $pickup_load_image = Db::name('dricerorder') 
            ->where('order_id',$data['orderid'])
            ->where('type',1)
            ->where('status',2)
            ->find();
        $line_load_image = Db::name('dricerorder')
            ->where('order_id',$data['orderid'])
            ->where('type',2)
            ->where('status',2)
            ->find();
        $delivery_unload_image =  Db::name('dricerorder')
            ->where('order_id',$data['orderid'])
            ->where('type',2)
            ->where('status',2)
            ->find();
        $cost_extra_price = Db::name('cost_extra_price')
            ->where('order_id',$data['orderid'])
            ->select();
        foreach ($cost_extra_price as &$value) {
            $value['createtime'] = date('Y-m-d H:i',$value['createtime']);
        }
        $order_extra_price = Db::name('order_extra_price')
            ->where('order_id',$data['orderid'])
            ->select();
        foreach ($order_extra_price as &$value) {
            $value['createtime'] = date('Y-m-d H:i',$value['createtime']);
        }
        $dirverother = Db::name('dirverother')
            ->where('order_id',$data['orderid'])
            ->select();
        foreach ($dirverother as &$value) {
//            $value['createtime'] = date('Y-m-d H:i',$value['createtime']);
            if ($value['type'] == 1){
                $value['type'] = '取货';
            }
            if ($value['type'] == 2){
                $value['type'] = '送货';
            }
        }
            $data['cost_extra_price'] = $cost_extra_price;
            $data['order_extra_price'] = $order_extra_price;
            $data['dirverother'] = $dirverother;
            $data['pickup_load_image'] = $pickup_load_image['loading_images']??'';
            $data['pickup_unload_image'] = $pickup_load_image['unloading_images']??'';
            $data['line_load_image']  = $line_load_image['unloading_images']??'';
            $data['line_unload_image']  = $line_load_image['loading_images']??'';
            $data['receipt_image']  = $delivery_unload_image['receipt_images']??'';
            $data['delivery_load_image']  = $delivery_unload_image['loading_images']??'';
            $data['delivery_unload_image']  = $delivery_unload_image['unloading_images']??'';
            $data['monad_image']  = Db::name('monad')->where('order_id',$data['orderid'])->value('image')??'';
//           if ($data['pay_price'] >0 && $data['pay_price'] >0) {
//               print_r( $data['pay_price']);echo '---'; print_r($data['cost_cont']);
//               $data['profit'] = $data['pay_price'] - $data['cost_cont'];
//           }else{
//               $data['pay_profit'] = 0;
//           }
        if($data){
            $this->success('订单详情查询成功',$data);
        }else{
            $this->error('订单号有误');
        }
    }
    /**
     * @return void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     *
     * 用户取消订单
     */
    public  function CancelOrder()
    {
    $order_id = $this->request->param('order_id');
    $user_id = $this->auth->getUser();
    if (!$user_id){
        $this->error('请登录');
    }
    $res = Db::name('order')->where('orderid',$order_id)->where('userid',$user_id['id'])->update(['pay_status'=>4]);
    if ($res){
        $this->success('订单取消成功');
    }else{
        $this->error('订单不存在，取消失败');
    }
    }

    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *
     * 用户发票列表
     */
    public function UserInvoice(){

        $user = $this->auth->getUser();

        $data = Db::name('tax')
            ->where('uid',$user)
            ->field('id,createtime,company_letterhead,status,invoice_image')
            ->select();
        foreach ($data as $k=>$v){
            $data[$k]['createtime'] = date('Y-m-d H:i',$v['createtime']);
        }
        if ($data){
            $this->success('查询成功',$data);
        }else{
            $this->error('暂无数据');
        }
    }

    /**
     * @return void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     *
     * 修改发票
     */
    public  function updateInvice()
    {
        $data = $this->request->param();
        $taxData = [
//            'orderid' => $data['orderid'],
            'type' => $data['type'],
            'tax_point' => $data['tax_point'],
            'company_letterhead' => $data['company_letterhead'],
            'company_email' => $data['company_email'],
            'company_tax_id' => $data['company_tax_id'],
            'company_mobile' => $data['company_mobile'],
            'bank_deposits' => $data['bank_deposits'],
            'createtime' => time()
        ];
        $res = Db::name('tax')->where('id',$data['id'])->update($taxData);
        if ($res){
            $this->success('修改成功');
        }else{
            $this->error('修改失败');
        }
    }

    /**
     * 根据发货区物流电话查询专线用户并生成专线订单
     * @param string $orderId 订单号
     * @param int $logisticsId 物流专线ID
     * @return bool
     */
    private function createDedicatedOrder($orderId, $logisticsId, $driverCost = null)
    {
            $logistics = Db::name('logistics')->where('id', $logisticsId)->find();
            if (!$logistics || empty($logistics['shipping_logistics_mobile'])) {
                return false;
            }

            $existingOrder = Db::name('dricerorder')
                ->where('order_id', $orderId)
                ->where('type', 2)
                ->find();

            if ($existingOrder) {
                return false;
            }

            if ($driverCost === null) {
                $driverCost = Db::name('order')->where('orderid', $orderId)->value('logistics_driver_cost') ?? 0;
            }

            $dricerOrderData = [
                'order_id' => $orderId,
                'type' => 2,
                'price' => $driverCost,
                'createtime' => time(),
                'status' => 3,
            ];
            $res = Db::name('dricerorder')->insert($dricerOrderData);
            return $res !== false;
    }

    /**
     * 创建专线用户
     * @param string $mobile 手机号
     * @param array $logistics 物流信息
     * @return array|false 返回用户信息或false
     */
    private function createDedicatedUser($mobile, $logistics)
    {
        try {
            // 检查该手机号是否已存在（不管identity）
            $existingUser = Db::name('user')->where('mobile', $mobile)->find();
            if ($existingUser) {
                // 如果用户已存在但不是专线用户，更新为专线用户
                if ($existingUser['identity'] != 3) {
                    Db::name('user')->where('id', $existingUser['id'])->update(['identity' => 3]);
                    // 重新查询更新后的用户数据
                    $existingUser = Db::name('user')->where('id', $existingUser['id'])->find();
                }
                // 返回用户信息
                return $existingUser;
            }

            // 生成随机密码
            $defaultPassword = \fast\Random::alnum(8);
            $salt = \fast\Random::alnum();
            $encryptedPassword = \app\common\library\Auth::instance()->getEncryptPassword($defaultPassword, $salt);

            // 生成用户名（使用手机号）
            $username = $mobile;
            
            // 生成昵称（隐藏部分手机号）
            $nickname = preg_match("/^1[3-9]{1}\d{9}$/", $mobile) ? substr_replace($mobile, '****', 3, 4) : $mobile;

            $ip = request()->ip();
            $time = time();

            // 准备用户数据
            $userData = [
                'group_id' => 1, // 默认组
                'username' => $username,
                'nickname' => $nickname,
                'password' => $encryptedPassword,
                'salt' => $salt,
                'mobile' => $mobile,
                'identity' => 3, // 专线用户
                'level' => 1,
                'score' => 0,
                'money' => 0.00,
                'avatar' => '',
                'status' => 'normal',
                'jointime' => $time,
                'joinip' => $ip,
                'logintime' => $time,
                'loginip' => $ip,
                'prevtime' => $time,
                'createtime' => $time,
                'updatetime' => $time,
            ];

            // 插入用户
            $userId = Db::name('user')->insertGetId($userData);
            if (!$userId) {
                return false;
            }

            // 返回用户信息
            $userData['id'] = $userId;
            return $userData;
        } catch (Exception $e) {
            // 记录错误但不影响主流程
            return false;
        }
    }

    /**
     * @return void
     * 账单列表
     */
    public function billList(){
        $user = $this->auth->id;
        $data = Db::name('bill')
            ->where('uid',$user)
            ->order('createtime desc')
            ->select();
        foreach ($data as $k=>$v){
            $data[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
        }
        if ($data){
            $this->success('查询成功',$data);
        }else{
            $this->error('暂无数据');
        }
    }

    public function walletSummary(){
        $user = $this->auth->getUser();
        if (!$user || empty($user['id'])) {
            $this->error('请登录');
        }
        $this->success('查询成功', $this->walletSummaryData($user['id']));
    }

    public function walletOrderList(){
        $user = $this->auth->getUser();
        if (!$user || empty($user['id'])) {
            $this->error('请登录');
        }

        $type = trim((string)$this->request->param('type', 'available'));
        $rows = $this->getWalletOrderList($user['id'], $type);
        $this->success('查询成功', $rows);
    }

    public function withdrawList(){
        $user = $this->auth->getUser();
        if (!$user || empty($user['id'])) {
            $this->error('请登录');
        }
        $data = Db::name('withdraw')
            ->where('user_id', $user['id'])
            ->order('createtime desc')
            ->select();
        foreach ($data as $k => $v) {
            $data[$k]['createtime'] = date('Y-m-d H:i:s', $v['createtime']);
            if (!empty($v['processtime'])) {
                $data[$k]['processtime'] = date('Y-m-d H:i:s', $v['processtime']);
            }
        }
        $this->success('查询成功', $data ?: []);
    }

    public function withdrawApply(){
        $user = $this->auth->getUser();
        if (!$user || empty($user['id'])) {
            $this->error('请登录');
        }
        $userId = $user['id'];
        $authInfo = Db::name('user_bankcard_auth')->where('uid', $userId)->where('status', '01')->find();
        if (!$authInfo) {
            $this->error('请先完成实名认证');
        }
        if (trim((string)($authInfo['name'] ?? '')) === '' || trim((string)($authInfo['account_no'] ?? '')) === '') {
            $this->error('实名认证信息不完整');
        }

        $availableOrders = Db::name('order')
            ->where('userid', $userId)
            ->where('createtime', '>=', strtotime('2026-07-27 00:00:00'))
            ->where('pay_status', 'in', [3, 7])
            ->whereRaw('(is_pay_salary IS NULL OR is_pay_salary = 0)')
            ->order('createtime desc')
            ->select();

        $withdrawnOrders = Db::name('withdraw')
            ->where('user_id', $userId)
            ->where('createtime', '>=', strtotime('2026-07-27 00:00:00'))
            ->column('orderid');
        $withdrawnOrders = array_filter(array_map('strval', (array)$withdrawnOrders));

        $targetOrders = [];
        foreach ($availableOrders as $order) {
            if (in_array((string)($order['orderid'] ?? ''), $withdrawnOrders, true)) {
                continue;
            }
            $targetOrders[] = $order;
        }
        if (empty($targetOrders)) {
            $this->error('暂无可提现订单');
        }

        $memo = trim((string)$this->request->param('memo', ''));
        $now = time();
        $total = 0;
        $orderIds = [];
        foreach ($targetOrders as $order) {
            $amount = $this->calculateWithdrawableAmount($order);
            $total += $amount;
            $orderIds[] = (string)($order['orderid'] ?? '');
        }

        if ($total <= 0) {
            $this->error('可提现金额为0');
        }

        Db::name('withdraw')->insert([
            'user_id' => $userId,
            'money' => round($total, 2),
            'handingfee' => 0,
            'taxes' => 0,
            'type' => 'wallet',
            'account' => $authInfo['account_no'],
            'name' => $authInfo['name'],
            'memo' => $memo,
            'orderid' => implode(',', array_filter($orderIds)),
            'status' => 'created',
            'transactionid' => '',
            'createtime' => $now,
            'updatetime' => $now,
        ]);

        $this->success('提交成功', [
            'count' => count($orderIds),
            'total' => round($total, 2),
        ]);
    }

    private function walletSummaryData($userId)
    {
        $startTime = strtotime('2026-07-27 00:00:00');

        $withdrawnOrders = Db::name('withdraw')
            ->where('user_id', $userId)
            ->where('createtime', '>=', $startTime)
            ->column('orderid');
        $withdrawnOrders = array_filter(array_map('strval', (array)$withdrawnOrders));

        $availableOrders = Db::name('order')
            ->where('userid', $userId)
            ->where('createtime', '>=', $startTime)
            ->where('pay_status', 'in', [3, 7])
            ->whereRaw('(is_pay_salary IS NULL OR is_pay_salary = 0)')
            ->select();
        $pendingOrders = Db::name('order')
            ->where('userid', $userId)
            ->where('createtime', '>=', $startTime)
            ->where('pay_status', '<>', 3)
            ->whereRaw('(is_pay_salary IS NULL OR is_pay_salary = 0)')
            ->select();

        $available = 0;
        foreach ($availableOrders as $order) {
            if (in_array((string)($order['orderid'] ?? ''), $withdrawnOrders, true)) {
                continue;
            }
            $available += $this->calculateWithdrawableAmount($order);
        }

        $pending = 0;
        foreach ($pendingOrders as $order) {
            $pending += $this->calculateWithdrawableAmount($order);
        }

        $history = Db::name('withdraw')
            ->where('user_id', $userId)
            ->where('createtime', '>=', $startTime)
            ->where('status', 'successed')
            ->sum('money');

        return [
            'available' => round($available, 2),
            'pending' => round($pending, 2),
            'history' => round(floatval($history), 2),
        ];
    }

    private function getWalletOrderList($userId, $type = 'available')
    {
        $startTime = strtotime('2026-07-27 00:00:00');
        $withdrawnOrders = Db::name('withdraw')
            ->where('user_id', $userId)
            ->where('createtime', '>=', $startTime)
            ->column('orderid');
        $withdrawnOrders = array_filter(array_map('strval', (array)$withdrawnOrders));

        if ($type === 'available') {
            $orders = Db::name('order')
                ->where('userid', $userId)
                ->where('createtime', '>=', $startTime)
                ->where('pay_status', 'in', [3, 7])
                ->whereRaw('(is_pay_salary IS NULL OR is_pay_salary = 0)')
                ->order('createtime desc')
                ->select();
            $statusText = '可提现';
        } elseif ($type === 'pending') {
            $orders = Db::name('order')
                ->where('userid', $userId)
                ->where('createtime', '>=', $startTime)
                ->where('pay_status', '<>', 3)
                ->whereRaw('(is_pay_salary IS NULL OR is_pay_salary = 0)')
                ->order('createtime desc')
                ->select();
            $statusText = '待到账';
        } else {
            return [];
        }

        $rows = [];
        foreach ($orders as $order) {
            if ($type === 'available' && in_array((string)($order['orderid'] ?? ''), $withdrawnOrders, true)) {
                continue;
            }
            $amount = $this->calculateWithdrawableAmount($order);
            $rows[] = [
                'orderid' => $order['orderid'] ?? '',
                'amount' => round($amount, 2),
                'pay_price' => round(floatval($order['pay_price'] ?? 0), 2),
                'createtime' => !empty($order['createtime']) ? date('Y-m-d H:i:s', $order['createtime']) : '',
                'status_text' => $statusText,
                'pay_status' => $order['pay_status'] ?? '',
            ];
        }

        return $rows;
    }

    private function getWithdrawableOrderIds($userId)
    {
        $orders = Db::name('order')->where('userid', $userId)->where('pay_status', 'in', [3, 7])->order('createtime desc')->select();
        $ids = [];
        foreach ($orders as $order) {
            $ids[] = (string)($order['orderid'] ?? '');
        }
        return array_values(array_filter($ids));
    }

    private function calculateWithdrawableAmount(array $order)
    {
        $total = floatval($order['pay_price'] ?? 0);
        $pickup = floatval($order['pickup_driver_fee'] ?? 0);
        $shipment = floatval($order['shipment_driver_fee'] ?? 0);
        $logistics = floatval($order['logistics_driver_cost'] ?? 0);
        $commission = floatval($order['platform_commission'] ?? 0);
        return round($total - $pickup - $shipment - $logistics - $commission, 2);
    }

    /**
     * @return void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 确认下单
     */
    public function  confirm_order(){
        $order_id = $this->request->param('order_id');

        $orderinfo = Db::name('order')->where('orderid',$order_id)->find();
        $loadingaddress = Db::name('user_address')->where('id',$orderinfo['loading'])->find();
        $unloadaddress = Db::name('user_address')->where('id',$orderinfo['unload'])->find();
// 检查装货地址必填项
        if (empty($loadingaddress['user_name']) || empty($loadingaddress['mobile'])) {
            $this->error('装货地址信息不全，请重新填写');
        }

// 检查卸货地址必填项
        if (empty($unloadaddress['user_name']) || empty($unloadaddress['mobile'])) {
            $this->error('卸货地址信息不全，请重新填写');
        }

// 所有验证通过，执行下单
        $res = Db::name('order')->where('orderid', $order_id)->update(['pay_status' => 1]);
        if ($res) {
            $this->success('确认下单成功');
        } else {
            $this->error('确认下单失败');
        }
    }

    /**
     * @return void
     * 确认价格
     */
    public function cancel_price(){
    $order_id = $this->request->param('order_id');
    $orderinfo = Db::name('order')->where('orderid',$order_id)->find();
    if ($orderinfo) {
        $res = Db::name('order')->where('orderid', $order_id)->update(['pay_status' => 1]);
        if ($res) {
            $this->success('价格确认成功');
        }else{
            $this->error('价格确认失败');
        }
    }else{
        $this->error('订单不存在');
    }

}

    /**
     * @return void
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 用户删除订单
     */
    public function DeleteOrder(){
        $order_id = $this->request->param('order_id');
        $orderinfo = Db::name('order')->where('orderid',$order_id)->find();
        if ($orderinfo) {
            $res = Db::name('order')->where('orderid', $order_id)->delete();
            if ($res) {
                $this->error('删除成功');
            }
        }
    }






}
