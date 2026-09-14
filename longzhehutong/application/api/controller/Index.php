<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Config;
use think\Db;
use think\Env;
use think\Log;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     *
     */
    public function index()
    {
        $this->success('请求成功');
    }
    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     *
     *
     * 首页公告
     */
    public function announcement()
    {
        $data = Db::name('announcement')->order('id desc')->select();
        $this->success('success',$data);
    }

    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 物流轨迹
     */
    public function trajectory()
    {
        $id = $this->request->param('id');
        $data = Db::name('trajectory')->where('order_id',$id)->order('id asc')->select();
        foreach ($data as &$v){
            $v['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
        }
        $this->success('success',$data);
    }

    public function deliveryrequirements()
    {
        $data = Db::name('deliveryrequirements')->order('id desc')->select();
        $this->success('success',$data);
    }

    public function loadingrequirements()
    {
        $data = Db::name('loadingrequirements')->order('id desc')->select();
        $this->success('success',$data);
    }
    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 渠道来源
     */
    public function channel(){
        $data = Db::name('channel')->order('id desc')->select();
        $this->success('查询成功',$data);
    }

    public function logisticslist()
    {
        // 1. 查询所有物流数据
        $data = Db::name('logistics')->select();
        $needUpdate = [];

        foreach ($data as $v) {
            // 检查发货地址
            if (empty($v['shipping_longitude']) || empty($v['arrival_longitude'])) {
                array_push($needUpdate, $v);
            }
        }
        print_r($needUpdate);
    }

    /**
     * 物流列表接口：取出 logistics 数据，缺经纬度的通过百度地图补全并写库后返回
     * @return void
     */
    public function logisticsWithGeocode()
    {
//        echo  1111;die;
        $data = Db::name('logistics')
//            ->whereNull('shipping_longitude') // 筛选字段为 NULL 的数据
//            ->whereOr('shipping_longitude', '') // 筛选字段为空字符串的情况
                ->where('id',37612)
            ->select();
//        print_r($data);die;
        $updatedCount = 0;

        foreach ($data as &$row) {
            $updateData = [];

            // 发货地缺经纬度时，用百度地图获取
            $needShipping = empty($row['shipping_longitude']) || empty($row['shipping_latitude']);
            if ($needShipping) {

                $shippingAddress = ($row['shipping_province'] ?? '') . ($row['origincity'] ?? '') . ($row['shipping_area'] ?? '') . ($row['shipping_logistics_address'] ?? '');

                $shippingCoords = $this->getBaiduCoordinates($shippingAddress, $row['origincity'] ?? '');

                if ($shippingCoords) {
                    $updateData['shipping_longitude'] = $shippingCoords['lng'];
                    $updateData['shipping_latitude'] = $shippingCoords['lat'];
                    $row['shipping_longitude'] = $shippingCoords['lng'];
                    $row['shipping_latitude'] = $shippingCoords['lat'];
                }
            }

            // 到货地缺经纬度时，用百度地图获取
            $needArrival = empty($row['arrival_longitude']) || empty($row['arrival_latitude']);
            if ($needArrival) {
                $arrivalAddress = ($row['destination'] ?? '') . ($row['province'] ?? '') . ($row['arrival_area'] ?? '') . ($row['arrival_logistics_address'] ?? '');
                $arrivalCoords = $this->getBaiduCoordinates($arrivalAddress, $row['destination'] ?? '');

                if ($arrivalCoords) {
                    $updateData['arrival_longitude'] = $arrivalCoords['lng'];
                    $updateData['arrival_latitude'] = $arrivalCoords['lat'];
                    $row['arrival_longitude'] = $arrivalCoords['lng'];
                    $row['arrival_latitude'] = $arrivalCoords['lat'];
                }
            }

            // 驾车距离缺时：发货、到货经纬度齐全则调百度驾车路线获取并写入
            $hasShipping = !empty($row['shipping_latitude']) && !empty($row['shipping_longitude']);
            $hasArrival = !empty($row['arrival_latitude']) && !empty($row['arrival_longitude']);
            $needDistance = ($hasShipping && $hasArrival) && (empty($row['distance']) || (float)$row['distance'] <= 0);
            if ($needDistance) {
                $drivingKm = calculateDrivingDistance(
                    (float)$row['shipping_latitude'],
                    (float)$row['shipping_longitude'],
                    (float)$row['arrival_latitude'],
                    (float)$row['arrival_longitude']
                );
                if ($drivingKm > 0) {
                    $updateData['distance'] = $drivingKm;
                    $row['distance'] = $drivingKm;
                }
            }

            if (!empty($updateData)) {
                Db::name('logistics')->where('id', $row['id'])->update($updateData);
                $updatedCount++;
            }
        }
        unset($row);

        $this->success('success', [
            'list' => $data,
            'total' => count($data),
            'updated_count' => $updatedCount,
        ]);
    }

    public function logistics(){
        // 1. 查询所有物流数据
        $data = Db::name('logistics')
            ->whereNull('shipping_province') // 筛选字段为 NULL 的数据
            ->whereOr('shipping_province', '') // 筛选字段为空字符串的情况
            ->select();
        print_r($data);die;
        $needUpdate = [];

        foreach ($data as $v){
            $updateData = [];
                $shipping_address = $v['shipping_province'].$v['origincity'].$v['shipping_area'].$v['shipping_logistics_address'];
                $shippingCoords = $this->getBaiduCoordinates($shipping_address, $v['origincity'] ?? '');
                if($shippingCoords) {
                    $updateData['shipping_longitude'] = $shippingCoords['lng'];
                    $updateData['shipping_latitude'] = $shippingCoords['lat'];
                }
                $arrival_address = $v['destination'].$v['province'].$v['arrival_area'].$v['arrival_logistics_address'];
                $arrivalCoords = $this->getBaiduCoordinates($arrival_address, $v['destination'] ?? '');
                if($arrivalCoords) {
                    $updateData['arrival_longitude'] = $arrivalCoords['lng'];
                    $updateData['arrival_latitude'] = $arrivalCoords['lat'];
                }
            // 如果有需要更新的字段
            if(!empty($updateData)) {
                $needUpdate[] = [
                    'id' => $v['id'],
                    'data' => $updateData
                ];
            }
        }

        // 批量更新
        if(!empty($needUpdate)) {
            foreach ($needUpdate as $update) {
                Db::name('logistics')
                    ->where('id', $update['id'])
                    ->update($update['data']);
            }
            echo "成功更新了 " . count($needUpdate) . " 条数据的经纬度信息\n";
        } else {
            echo "所有数据都有完整的经纬度信息\n";
        }
    }
    /**
     * 解析结果过粗时不宜落库（多为行政区几何中心，易偏离真实门址）
     */
    private static $baiduGeocodeCoarseLevels = ['国家', '省', '市', '区县', '商圈'];

    /**
     * 百度正地理编码：过滤歧义大、落点过粗或置信度过低的结果
     */
    private function isBaiduGeocodeResultReliable(array $result)
    {
        $level = isset($result['level']) ? (string)$result['level'] : '';
        if ($level !== '' && in_array($level, self::$baiduGeocodeCoarseLevels, true)) {
            return false;
        }

        if (isset($result['comprehension']) && (int)$result['comprehension'] < 50) {
            return false;
        }

        $precise = array_key_exists('precise', $result) ? (int)$result['precise'] : null;
        $confidence = isset($result['confidence']) ? (int)$result['confidence'] : null;

        if ($precise === 1) {
            return $confidence === null || $confidence >= 30;
        }
        if ($precise === 0) {
            return $confidence !== null && $confidence >= 70;
        }

        return $confidence === null || $confidence >= 55;
    }

    /**
     * 调用百度地图API获取坐标
     * @param string $address 地址（建议含省市区+详细）
     * @param string $city 城市限定，缩小重名/简称歧义（如发货 origincity、到货 destination）
     * @return array|false 返回经纬度数组或false
     */
    private function getBaiduCoordinates($address, $city = '') {

        if(empty($address)) {
            return false;
        }

        $city = is_string($city) ? trim($city) : '';

        // 也可以从配置文件读取，优先级更高
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

            $url = "http://api.map.baidu.com/geocoding/v3/?address=" . urlencode($address) . "&output=json&ak=" . $ak;
            if ($city !== '') {
                $url .= "&city=" . urlencode($city);
            }

            try {
                // 调用API
                $result = @file_get_contents($url);

                if ($result === FALSE) {
                    // 网络请求失败，记录并尝试下一个AK
                    Log::error("百度地图API网络请求失败，AK：" . $currentKey);
                    $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                    continue;
                }

                $data = json_decode($result, true);
//                print_r($data);die;
                if ($data['status'] == 0 && isset($data['result']['location'])) {
                    $res = $data['result'];
                    if (!$this->isBaiduGeocodeResultReliable($res)) {
                        Log::warning("百度地理编码结果未采纳（可信度/精度过低），地址：{$address}，city：{$city}，result：" . json_encode($res, JSON_UNESCAPED_UNICODE));
                        return false;
                    }
                    return [
                        'lng' => $res['location']['lng'],
                        'lat' => $res['location']['lat']
                    ];
                } elseif ($data['status'] == 1 || $data['status'] == 101 || $data['status'] == 302 || $data['status'] == 401) {
                    // AK权限或额度问题：status=1(服务内部错误)、status=101(AK无效/无权限)、status=302(天配额超限)、status=401(并发超限)
                    Log::warning("百度地图AK额度可能用尽，AK：" . $currentKey . "，状态码：" . $data['status']);
                    $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                    continue; // 尝试下一个AK
                } else {
                    // 其他错误（地址解析失败等）
                    Log::error("百度地图API调用失败，地址：{$address}，AK：" . $currentKey . "，返回：" . json_encode($data));
                    break; // 如果是地址问题，不需要切换AK重试
                }
            } catch (\Exception $e) {
                Log::error("获取坐标异常，AK：" . $currentKey . "，错误：" . $e->getMessage());
                $currentAkIndex = ($currentAkIndex + 1) % count($akList);
                continue;
            }
        }

        // 所有AK都尝试失败
        Log::error("所有百度地图AK都尝试失败，地址：{$address}");
        return false;
    }


    public function dedata(){
        // 查询需要判断重复的字段
        $list = Db::name('logistics')
            ->field('id,shipping_province,origincity,province,destination,shipping_logistics_address,arrival_logistics_address')
            ->select();

        if (empty($list)) {
            echo "暂无数据";
            return;
        }

        $seen = [];      // 记录已出现过的组合
        $deleteIds = []; // 需要删除的 id

        foreach ($list as $row) {
            // 使用 6 个字段拼成唯一 key
            $key = implode('|', [
                (string)$row['shipping_province'], 
                (string)$row['origincity'],
                (string)$row['province'],
                (string)$row['destination'],
                (string)$row['shipping_logistics_address'],
                (string)$row['arrival_logistics_address'],
            ]);

            if (isset($seen[$key])) {
                // 该组合之前已经出现过，视为重复，当前这条标记为删除
                $deleteIds[] = $row['id'];
            } else {
                // 第一次出现，保留
                $seen[$key] = $row['id'];
            }
        }

        if (!empty($deleteIds)) {
            $count = Db::name('logistics')
                ->where('id', 'in', $deleteIds)
                ->delete();
            echo "已删除重复数据条数：" . $count;
        } else {
            echo "没有检测到重复数据";
        }
    }

    /**
     * 获取打印数据接口（GBK编码）
     * 接口路径：Index/getPrintData 或 Placeorder/getPrintData（根据路由配置）
     * 请求方式：POST
     * 参数：
     *   - order_id: 订单ID
     *   - print_type: 打印类型（waybill=运单, receipt=签收单）
     */
    public function getPrintData()
    {
        $order_id = $this->request->param('order_id');
        $print_type = $this->request->param('print_type', 'waybill');

        if (empty($order_id)) $this->error('订单ID不能为空');
        $order = Db::name('order')->where('orderid', $order_id)->find();
        if (empty($order)) $this->error('订单不存在');

        // 原有格式化数据逻辑不变
        $order['createtime'] = date('Y-m-d H:i:s', $order['createtime']);
        $order['loading_address'] = Db::name('user_address')->where('id', $order['loading'])->field('id,user_name as name,mobile,address,detailed_address as address_detail')->find();
        $order['unload_address'] = Db::name('user_address')->field('id,user_name as name,mobile,address,detailed_address as address_detail')->where('id', $order['unload'])->find();
        if ($order['loading_address']) {
            $order['loading_address']['address'] = !empty($order['loading_address']['address_detail']) ? $order['loading_address']['address_detail'] : $order['loading_address']['address'];
        }
        if ($order['unload_address']) {
            $order['unload_address']['address'] = !empty($order['unload_address']['address_detail']) ? $order['unload_address']['address_detail'] : $order['unload_address']['address'];
        }
        $goodsType = Db::name('goods_type')->where('id', $order['goods_type_id'])->find();
        $order['goods_name'] = $goodsType ? $goodsType['name'] : '货物';
        $packaging = Db::name('packaging')->where('id', $order['packaging_id'])->find();
        $order['pack_type'] = $packaging ? $packaging['name'] : '标准';
        if (!empty($order['long']) && !empty($order['wide']) && !empty($order['hige'])) {
            $order['volume'] = round(($order['long'] * $order['wide'] * $order['hige']) / 1000000, 2);
        } else {
            $order['volume'] = '0';
        }
        $order['num'] = $order['quantity'] ?? '1';
        $order['pay_type_name'] = $order['pay_type'] == 1 ? '到付' : '现付';
        $order['declare_value'] = '0.00';
        $order['service_fee'] = '0.00';
        $order['cod_amount'] = '0.00';
        $order['delivery_type'] = '标准';

        // 1. ESC/POS原始二进制指令（单引号、不转义）
        $init = "\x1B\x40";//初始化复位
        $bigFont = "\x1D\x21\x11";//宽2高2放大
        //$bigFont = "\x1D\x21\x22";//超大3倍

        // 2. 生成原有内容、补空行=一张纸
        if ($print_type === 'waybill') {
            $content = $this->generateWaybillContent($order);
            $blank = str_repeat("\n", 15);//补空行、强制一张纸
        } else if ($print_type === 'receipt') {
            $content = $this->generateReceiptContent($order);
            $blank = str_repeat("\n", 15);
        } else {
            $this->error('打印类型错误');
        }
        // 顺序：复位→放大→内容→空行；**纯二进制拼接、不要任何转码**
        $printBinary = $init . $bigFont . $content . $blank;
        $base64Data = base64_encode($printBinary);

        $this->success('获取成功', ['print_data' => $base64Data]);
    }
    /**
     * 生成空白行，强制打印内容占满一整张纸
     * @param $type waybill-运单 / receipt-签收单
     * @return string
     */
    private function getPaperBlankLines($type)
    {
        // 热敏打印机：\n 是换行，一行一行往下顶
        // 数值越大，空白越多，越能撑满一张纸
        if ($type === 'waybill') {
            // 运单：补 60 行空白（可根据你的纸张大小微调）
            return str_repeat("\n", 12);
        } elseif ($type === 'receipt') {
            // 签收单：补 50 行空白（可微调）
            return str_repeat("\n", 50);
        }
        return '';
    }
    /**
     * 生成运单内容（单页紧凑版，7.5cm 宽；Logo 光栅条左上角、正文居中、小程序码光栅条右下角）
     */
    private function generateWaybillContent($item)
    {
        $ESC = "\x1B";
        $GS = "\x1D";
        $LF = "\x0A";
        $paperW = $this->getWaybillPrintWidthDots();
        $lineW = 20;
        $addrCap = 40;
 
        $waybillNo = isset($item['orderid']) ? strval($item['orderid']) : '';
        $recvName = isset($item['unload_address']['name']) ? $item['unload_address']['name'] : '';
        $recvPhone = isset($item['unload_address']['mobile']) ? $item['unload_address']['mobile'] : '';
        $recvAddr = isset($item['unload_address']['address']) ? $item['unload_address']['address'] : '';
        $sendAddr = isset($item['loading_address']['address']) ? $item['loading_address']['address'] : '';
        $goodsName = isset($item['goods_name']) ? $item['goods_name'] : '货物';
        $weight = isset($item['weight']) ? $item['weight'] : '0';
        $pieces = isset($item['num']) ? $item['num'] : '1';
        $freight = isset($item['pay_price']) ? $item['pay_price'] : '0.00';
        $createTime = isset($item['createtime']) ? $item['createtime'] : '';
 
        $recvAddr = mb_substr($recvAddr, 0, $addrCap, 'UTF-8');
        $sendAddr = mb_substr($sendAddr, 0, $addrCap, 'UTF-8');

        $logoPath = $this->isWaybillRasterEnabled()
            ? $this->resolvePublicAssetFullPath(Config::get('site.waybill_print_logo')) : '';
        $miniQrPath = $this->isWaybillRasterEnabled()
            ? $this->resolvePublicAssetFullPath(Config::get('site.waybill_print_mini_qr')) : '';

        $binaryTop = '';
        $binaryTop .= $ESC . '@';
        $binaryTop .= $ESC . '2';
        $binaryTop .= $ESC . '3' . "\x08";
        $binaryTop .= $ESC . 'a' . "\x00";
        $binaryTop .= $ESC . "\x20\x00";
        $stripLogo = $this->escPosWaybillStripLogoTopLeft($logoPath, $paperW, 118, 4, 4);
        if ($stripLogo !== '') {
            $binaryTop .= $stripLogo . $LF;
        }

        $utf8 = '';
        $utf8 .= $ESC . 'a' . "\x01";
        $utf8 .= $ESC . '!' . "\x00";
        $utf8 .= $ESC . '!' . "\x08";
        $utf8 .= $ESC . '!' . "\x00";
        $utf8 .= $ESC . 'a' . "\x00";
        if (strlen($waybillNo) > 0) {
            $utf8 .= $ESC . 'a' . "\x01";
            $utf8 .= $GS . 'h' . "\x20";
            $utf8 .= $GS . 'w' . "\x02";
            $utf8 .= $GS . 'H' . "\x02";
            $len = chr(strlen($waybillNo));
            $utf8 .= $GS . 'k' . "\x49" . $len . $waybillNo;
            $utf8 .= $LF;
            $utf8 .= $ESC . 'a' . "\x00";
        }

        $utf8 .= '【收】' . $this->truncateText($recvName, 8) . $LF;
        $utf8 .= '电:' . $this->truncateText($recvPhone, $lineW - 2) . $LF;
        foreach ($this->wrapMbToLines($recvAddr, $lineW - 1) as $ln) {
            $utf8 .= '址' . $ln . $LF;
        }
        $utf8 .= '【寄】' . $LF;
        foreach ($this->wrapMbToLines($sendAddr, $lineW - 1) as $ln) {
            $utf8 .= $ln . $LF;
        }
        $utf8 .= '货:' . $this->truncateText($goodsName, 8) . ' ' . $pieces . '件 ' . $weight . '吨' . $LF;
        $payStatus = isset($item['pay_status']) ? $item['pay_status'] : null;
        $utf8 .= '费:￥' . $freight . ' ' . $this->getStatusText($payStatus) . $LF;
        $utf8 .= '时:' . mb_substr($createTime, 0, 16, 'UTF-8') . $LF;
        $utf8 .= '请核对签收' . $LF;
//        $utf8 .= $ESC . 'a' . "\x00"; 
        $utf8 .= $ESC . 'a' . "\x01";
        $qrStripMargin = max(0, (int)Config::get('site.waybill_print_qr_margin_dots', 2));
        $binaryBottom = $this->escPosWaybillStripQrBottomRight($miniQrPath, $paperW, 260, $qrStripMargin);
        if ($binaryBottom !== '') {
            $binaryBottom .= $LF;
        }

        $tail = $ESC . 'd' . "\x01" . $GS . 'V' . "\x00";
 
        return $binaryTop
            . mb_convert_encoding($utf8, 'GBK', 'UTF-8')
            . $binaryBottom
            . $tail;
    }

    /**
     * 是否输出 Logo/二维码光栅（关则仅文字，避免个别固件卡死时无法打印）
     */
    private function isWaybillRasterEnabled()
    {
        $v = Config::get('site.waybill_print_raster_enable', 1);
        if ($v === '0' || $v === 0 || $v === false) {
            return false;
        }
        if (is_string($v) && strtolower(trim($v)) === 'no') {
            return false;
        }
        return true;
    }

    /**
     * 热敏纸有效打印宽度（点），避免超出机头宽度导致 Logo/二维码花屏
     */
    private function getWaybillPrintWidthDots()
    {
        // 默认 576 适配 80mm（如芝柯 K31）；58mm 请在站点配置中显式改为 384
        $w = (int)Config::get('site.waybill_print_width_dots', 576);
        if ($w < 192) {
            $w = 192; 
        }
        $maxW = $this->escPosMaxRasterWidthDots();
        if ($w > $maxW) {
            $w = $maxW;
        }
        return $w;
    }

    /**
     * ESC/POS 光栅头：固定 GS v 0（\\x1d\\x76\\x30\\x00）。短格式 \\x1d\\x76\\x00 易使部分机型死等数据，已移除。
     */
    private function escPosRasterGsHeader()
    {
        return "\x1d\x76\x30\x00";
    }

    /**
     * 单张 GS v0 光栅最大高度（点），小程序码条带 nh+上下边距不得超过此值
     */
    private function escPosMaxRasterHeight()
    {
        return 480;
    }

    /**
     * 单行 GS v0 光栅最大宽度（点）。80mm 常用 576；7.5cm(75mm) 约 600；略放宽到 640 以兼容宽头，仍勿超过打印机手册
     */
    private function escPosMaxRasterWidthDots()
    {
        return 640;
    }
    /**
     * 整行宽光栅条：Logo 贴纸张左上角（条带内左上，留白尽量小以免视觉「沉底」）
     *
     * @param int      $bandMaxHeight 允许的最大条带高度（点），用于缩放上限
     * @param int      $marginLeft     距左（点小更易靠左）
     * @param int|null $marginTop      距上；null 时与 $marginLeft 相同（兼容旧调用）
     */
    private function escPosWaybillStripLogoTopLeft($logoPath, $paperWidthDots, $bandMaxHeight, $marginLeft, $marginTop = null)
    {
        if ($logoPath === '' || !is_readable($logoPath) || !function_exists('imagecreatetruecolor')) {
            return '';
        }
        $marginLeft = max(0, (int)$marginLeft);
        if ($marginTop === null) {
            $marginTop = $marginLeft;
        } else {
            $marginTop = max(0, (int)$marginTop);
        }
        $bottomPad = 6;
        $bandMaxHeight = max(24, min(128, (int)$bandMaxHeight));

        $raw = @file_get_contents($logoPath);
        if ($raw === false || $raw === '') {
            return '';
        }
        $im = @imagecreatefromstring($raw);
        if (!$im) {
            return '';
        }
        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($im);
        }
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w < 1 || $h < 1) {
            imagedestroy($im);
            return '';
        }
        $innerH = max(16, $bandMaxHeight - $marginTop - $bottomPad);
        $maxLogoW = min((int)($paperWidthDots * 0.78), max(48, $paperWidthDots - $marginLeft - 4));
        // 允许放大小图（原先 min(...,1.0) 导致 Logo 一直很小）；上限避免糊成块
        $maxScale = 7.0;
        $scale = min($maxLogoW / $w, $innerH / $h, $maxScale);
        if ($scale <= 0) {
            imagedestroy($im);
            return '';
        }
        $nw = max(1, (int)round($w * $scale));
        $nh = max(1, (int)round($h * $scale));
        if ($nh > $innerH) {
            $scale = $innerH / $h;
            $nw = max(1, (int)round($w * $scale));
            $nh = max(1, (int)round($h * $scale));
        }
        if ($nw > $maxLogoW) {
            $scale = $maxLogoW / $w;
            $nw = max(1, (int)round($w * $scale));
            $nh = max(1, (int)round($h * $scale));
            if ($nh > $innerH) {
                $scale = $innerH / $h;
                $nw = max(1, (int)round($w * $scale));
                $nh = max(1, (int)round($h * $scale));
            }
        }

        $logoResized = imagecreatetruecolor($nw, $nh);
        if (!$logoResized) {
            imagedestroy($im);
            return '';
        }
        $wcol = imagecolorallocate($logoResized, 255, 255, 255);
        imagefilledrectangle($logoResized, 0, 0, $nw, $nh, $wcol);
        imagecopyresampled($logoResized, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($im);

        $bandH = min(128, $marginTop + $nh + $bottomPad);
        $canvas = imagecreatetruecolor($paperWidthDots, $bandH);
        if (!$canvas) {
            imagedestroy($logoResized);
            return '';
        }
        imagealphablending($canvas, true);
        imagesavealpha($canvas, false);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $paperWidthDots, $bandH, $white);
        imagealphablending($logoResized, true);
        imagecopy($canvas, $logoResized, $marginLeft, $marginTop, 0, 0, $nw, $nh);
        imagedestroy($logoResized);

        return $this->escPosRasterFromGd($canvas);
    }

    /**
     * 小程序码：GS v0 光栅，目标为纸张右下角。
     * 若纸宽仍为 384 而打印机为 80mm，请调 waybill_print_width_dots 或 waybill_print_qr_canvas_width_dots。
     */
    private function escPosWaybillStripQrBottomRight($qrPath, $paperWidthDots, $qrBoxDots, $marginDots)
    {
        if ($qrPath === '' || !is_readable($qrPath) || !function_exists('imagecreatetruecolor')) {
            return '';
        }
        $raw = @file_get_contents($qrPath);
        if ($raw === false || $raw === '') {
            return '';
        }
        $im = @imagecreatefromstring($raw);
        if (!$im) {
            return '';
        }
        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($im);
        }
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w < 1 || $h < 1) {
            imagedestroy($im);
            return '';
        }
        $marginDots = max(0, (int)$marginDots);
        $maxStripH = $this->escPosMaxRasterHeight();
        $maxByHeight = max(32, $maxStripH - 2 * $marginDots);
        $maxRasterW = $this->escPosMaxRasterWidthDots();
        $qrCanvasCfg = (int)Config::get('site.waybill_print_qr_canvas_width_dots', 0);
        $layoutDots = $qrCanvasCfg >= 192 ? $qrCanvasCfg : (int)$paperWidthDots;
        $layoutDots = min($maxRasterW, max(192, $layoutDots));
        $maxQrEdgeDots = max(48, $layoutDots - 2 * $marginDots);
        $box = max(48, (int)$qrBoxDots);
        $box = min($box, $maxByHeight, $maxQrEdgeDots);
        $scale = min($box / $w, $box / $h);
        if ($scale <= 0) {
            imagedestroy($im);
            return '';
        }
        $nw = max(1, (int)round($w * $scale));
        $nh = max(1, (int)round($h * $scale));
        if ($nh + 2 * $marginDots > $maxStripH) {
            $s = ($maxStripH - 2 * $marginDots) / $nh;
            $nw = max(1, (int)round($nw * $s));
            $nh = max(1, (int)round($nh * $s));
        }
        $qrResized = imagecreatetruecolor($nw, $nh);
        if (!$qrResized) {
            imagedestroy($im);
            return '';
        }
        $wcol = imagecolorallocate($qrResized, 255, 255, 255);
        imagefilledrectangle($qrResized, 0, 0, $nw, $nh, $wcol);
        imagecopyresampled($qrResized, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($im);

        $canvasWDots = $layoutDots;
        $maxNwOnPaper = max(8, $canvasWDots - $marginDots);
        if ($nw > $maxNwOnPaper) {
            $f = $maxNwOnPaper / $nw;
            $newW = max(1, (int)round($nw * $f));
            $newH = max(1, (int)round($nh * $f));
            $tmp = imagecreatetruecolor($newW, $newH);
            if (!$tmp) {
                imagedestroy($qrResized);
                return '';
            }
            $wcol2 = imagecolorallocate($tmp, 255, 255, 255);
            imagefilledrectangle($tmp, 0, 0, $newW, $newH, $wcol2);
            imagecopyresampled($tmp, $qrResized, 0, 0, 0, 0, $newW, $newH, $nw, $nh);
            imagedestroy($qrResized);
            $qrResized = $tmp;
            $nw = $newW;
            $nh = $newH;
        }

        $bandH = $nh + 2 * $marginDots;
        $yQr = $bandH - $nh - $marginDots;
        $ESC = "\x1B";
        $xShift = (int)Config::get('site.waybill_print_qr_x_shift_dots', 0);
        $posMode = strtolower(trim((string)Config::get('site.waybill_print_qr_pos', 'fullwidth')));
        $useEscDollar = ($posMode === 'esc_dollar' || $posMode === '1' || $posMode === 'yes');

        if ($useEscDollar) {
            $stripW = $nw + $marginDots;
            $canvas = imagecreatetruecolor($stripW, $bandH);
            if (!$canvas) {
                imagedestroy($qrResized);
                return '';
            }
            imagealphablending($canvas, true);
            imagesavealpha($canvas, false);
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $stripW, $bandH, $white);
            imagealphablending($qrResized, true);
            imagecopy($canvas, $qrResized, 0, $yQr, 0, 0, $nw, $nh);
            imagedestroy($qrResized);
            if ((int)Config::get('site.waybill_print_qr_flip_h', 0) === 1 && function_exists('imageflip')) {
                imageflip($canvas, IMG_FLIP_HORIZONTAL);
            }
            $raster = $this->escPosRasterFromGd($canvas);
            if ($raster === '') {
                return '';
            }
            $xAbs = max(0, min(65535, $layoutDots - $stripW + $xShift));
            $prefix = $ESC . 'a' . "\x00" . $ESC . '$' . chr($xAbs & 0xFF) . chr(($xAbs >> 8) & 0xFF);
            return $prefix . $raster;
        }

        $canvas = imagecreatetruecolor($canvasWDots, $bandH);
        if (!$canvas) {
            imagedestroy($qrResized);
            return '';
        }
        imagealphablending($canvas, true);
        imagesavealpha($canvas, false);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $canvasWDots, $bandH, $white);
        imagealphablending($qrResized, true);
        $x = $canvasWDots - $nw - $marginDots + $xShift;
        if ($x < 0) {
            $x = 0;
        }
        if ($x + $nw > $canvasWDots) {
            $x = max(0, $canvasWDots - $nw);
        }
        imagecopy($canvas, $qrResized, $x, $yQr, 0, 0, $nw, $nh);
        imagedestroy($qrResized);
        if ((int)Config::get('site.waybill_print_qr_flip_h', 0) === 1 && function_exists('imageflip')) {
            imageflip($canvas, IMG_FLIP_HORIZONTAL);
        }

        return $ESC . 'a' . "\x00" . $this->escPosRasterFromGd($canvas);
    }

    /**
     * 将 GD 图像转为 ESC/POS GS v 0 光栅（销毁图像资源）
     */
    private function escPosRasterFromGd($im)
    {
        if (!$im) {
            return '';
        }
        $w = imagesx($im);
        $h = imagesy($im);
        if ($w < 1 || $h < 1) {
            imagedestroy($im);
            return '';
        }
        $maxH = $this->escPosMaxRasterHeight();
        $maxW = $this->escPosMaxRasterWidthDots();
        if ($w > $maxW || $h > $maxH) {
            imagedestroy($im);
            return '';
        }
        $invert = (int)Config::get('site.waybill_print_raster_invert', 0) === 1;
        $lsbFirst = (int)Config::get('site.waybill_print_raster_lsb', 0) === 1;
        $bytesPerLine = (int)ceil($w / 8);
        $expectedLen = $bytesPerLine * $h;
        if ($expectedLen > 45000) {
            imagedestroy($im);
            return '';
        }
        $buf = '';
        for ($y = 0; $y < $h; $y++) {
            for ($byteIdx = 0; $byteIdx < $bytesPerLine; $byteIdx++) {
                $byte = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $x = $byteIdx * 8 + $bit;
                    $black = false;
                    if ($x < $w) {
                        $rgb = imagecolorat($im, $x, $y);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $blue = $rgb & 0xFF;
                        $alpha = ($rgb >> 24) & 0x7F;
                        if ($alpha >= 100) {
                            $gray = 255;
                        } else {
                            $gray = (int)(($r * 299 + $g * 587 + $blue * 114) / 1000);
                        }
                        if ($invert) {
                            if ($gray >= 140) {
                                $black = true;
                            }
                        } elseif ($gray < 140) {
                            $black = true;
                        }
                    }
                    if ($black) {
                        if ($lsbFirst) {
                            $byte |= 1 << $bit;
                        } else {
                            $byte |= 1 << (7 - $bit);
                        }
                    }
                }
                $buf .= chr($byte);
            }
        }
        imagedestroy($im);
        if (strlen($buf) !== $expectedLen) {
            return '';
        }
        $xL = $bytesPerLine & 0xFF;
        $xH = ($bytesPerLine >> 8) & 0xFF;
        $yL = $h & 0xFF;
        $yH = ($h >> 8) & 0xFF;
        return $this->escPosRasterGsHeader() . chr($xL) . chr($xH) . chr($yL) . chr($yH) . $buf;
    }

    /**
     * 站点配置的 public 相对路径转为绝对路径（运单 Logo / 小程序码等）
     */
    private function resolvePublicAssetFullPath($relativePath)
    {
        $rel = trim((string)$relativePath);
        if ($rel === '') {
            return '';
        }
        $rel = str_replace('\\', '/', $rel);
        $rel = '/' . ltrim($rel, '/');
        $full = ROOT_PATH . 'public' . str_replace('/', DS, $rel);
        return is_file($full) ? $full : '';
    }

    /**
     * 生成签收单内容（7.5cm×9.5cm 单页；Logo 条左上、正文、小程序码条右下；中文已转 GBK）
     */
    private function generateReceiptContent($item)
    {
        $ESC = "\x1B";
        $GS = "\x1D";
        $LF = "\x0A";
        $paperW = $this->getWaybillPrintWidthDots();
        $lineW = 20;
        // 顶/底各一条光栅，地址总长略收以控制总高
        $addrMaxChars = 44;

        $waybillNo = isset($item['orderid']) ? strval($item['orderid']) : '';
        $recvName = isset($item['unload_address']['name']) ? $item['unload_address']['name'] : '';
        $recvPhone = isset($item['unload_address']['mobile']) ? $item['unload_address']['mobile'] : '';
        $recvAddr = isset($item['unload_address']['address']) ? $item['unload_address']['address'] : '';
        $sendName = isset($item['loading_address']['name']) ? $item['loading_address']['name'] : '';
        $sendPhone = isset($item['loading_address']['mobile']) ? $item['loading_address']['mobile'] : '';
        $sendAddr = isset($item['loading_address']['address']) ? $item['loading_address']['address'] : '';

        $goodsName = isset($item['goods_name']) ? $item['goods_name'] : '货物';
        $packType = isset($item['pack_type']) ? $item['pack_type'] : '标准';
        $weight = isset($item['weight']) ? $item['weight'] : '0';
        $volume = isset($item['volume']) ? $item['volume'] : '0';
        $pieces = isset($item['num']) ? $item['num'] : '1';
        $freight = isset($item['pay_price']) ? $item['pay_price'] : '0.00';
        $declareValue = isset($item['declare_value']) ? $item['declare_value'] : '0.00';
        $serviceFee = isset($item['service_fee']) ? $item['service_fee'] : '0.00';
        $codAmount = isset($item['cod_amount']) ? $item['cod_amount'] : '0.00';
        $createTime = isset($item['createtime']) ? $item['createtime'] : '';
        $payType = isset($item['pay_type_name']) ? $item['pay_type_name'] : '现付';

        $recvAddr = mb_substr($recvAddr, 0, $addrMaxChars, 'UTF-8');
        $sendAddr = mb_substr($sendAddr, 0, $addrMaxChars, 'UTF-8');

        $logoPath = $this->isWaybillRasterEnabled()
            ? $this->resolvePublicAssetFullPath(Config::get('site.waybill_print_logo')) : '';
        $miniQrPath = $this->isWaybillRasterEnabled()
            ? $this->resolvePublicAssetFullPath(Config::get('site.waybill_print_mini_qr')) : '';

        $binaryTop = '';
        $binaryTop .= $ESC . '@';
        $binaryTop .= $GS . 'L' . "\x00\x00";
        $binaryTop .= $ESC . '3' . "\x06";
        $binaryTop .= $ESC . 'a' . "\x00";
        $binaryTop .= $ESC . "\x20\x00";
        $stripLogo = $this->escPosWaybillStripLogoTopLeft($logoPath, $paperW, 118, 14, 4);
        if ($stripLogo !== '') {
            $binaryTop .= $stripLogo . $LF;
        }

        $utf8 = '';
        $utf8 .= $ESC . 'a' . "\x01";
        $utf8 .= $ESC . '!' . "\x08";
        $utf8 .= '签收单' . $LF;
        $utf8 .= $ESC . '!' . "\x00";
        $utf8 .= '单号' . $waybillNo . $LF;
        $utf8 .= $ESC . 'a' . "\x00";

        $utf8 .= '【收】' . $this->truncateText($recvName, 10) . $LF;
        $utf8 .= '电:' . $this->truncateText($recvPhone, $lineW - 2) . $LF;
        foreach ($this->wrapMbToLines($recvAddr, $lineW - 1) as $ln) {
            $utf8 .= '址' . $ln . $LF;
        }

        $utf8 .= '【寄】' . $this->truncateText($sendName, 10) . $LF;
        $utf8 .= '电:' . $this->truncateText($sendPhone, $lineW - 2) . $LF;
        foreach ($this->wrapMbToLines($sendAddr, $lineW - 1) as $ln) {
            $utf8 .= '址' . $ln . $LF;
        }

        $utf8 .= '货:' . $this->truncateText($goodsName, 8);
        $utf8 .= ' ' . $pieces . '件 ' . $weight . '吨 ' . $this->truncateText($payType, 4) . $LF;
        $utf8 .= '包:' . $this->truncateText($packType, 6) . ' 体:' . $volume . 'm³' . $LF;

        $utf8 .= '运费:￥' . $freight;
        if ((float)$declareValue != 0) {
            $utf8 .= ' 保:￥' . $declareValue;
        }
        if ((float)$serviceFee != 0) {
            $utf8 .= ' 服:￥' . $serviceFee;
        }
        $utf8 .= $LF;

        $utf8 .= '时:' . mb_substr($createTime, 0, 19, 'UTF-8') . $LF;
        if ((float)$codAmount != 0) {
            $utf8 .= '代收:￥' . $codAmount . $LF;
        }

        $utf8 .= $ESC . 'a' . "\x00";
        $qrStripMargin = max(0, (int)Config::get('site.waybill_print_qr_margin_dots', 2));
        $binaryBottom = $this->escPosWaybillStripQrBottomRight($miniQrPath, $paperW, 240, $qrStripMargin);
        if ($binaryBottom !== '') {
            $binaryBottom .= $LF;
        }

        $tail = $ESC . 'd' . "\x01" . $GS . 'V' . "\x00";

        return $binaryTop
            . mb_convert_encoding($utf8, 'GBK', 'UTF-8')
            . $binaryBottom
            . $tail;
    }

    /**
     * 按固定字符宽度折行（中文按 1 字计，用于窄纸）
     *
     * @param string $text
     * @param int    $width
     * @return string[]
     */
    private function wrapMbToLines($text, $width)
    {
        $text = trim((string)$text);
        if ($text === '' || $width < 1) {
            return [];
        }
        $out = [];
        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i += $width) {
            $out[] = mb_substr($text, $i, $width, 'UTF-8');
        }
        return $out;
    }
    /**
     * 截断文本
     */
    private function truncateText($text, $maxLength)
    {
        if ($text === '' || $text === null) {
            return '';
        }
        $maxLength = (int)$maxLength;
        if ($maxLength < 1) {
            return '';
        }
        $len = mb_strlen((string)$text, 'UTF-8');
        if ($len <= $maxLength) {
            return (string)$text;
        }
        return mb_substr((string)$text, 0, $maxLength, 'UTF-8') . '...';
    }

    /**
     * 获取状态文本
     */
    private function getStatusText($status)
    {
        $statusMap = [
            1 => '待付款',
            2 => '服务中',
            3 => '已完成',
            4 => '已取消',
            5 => '待下单',
            6 => '测算中',
            7 => '已出价'
        ];
        return isset($statusMap[$status]) ? $statusMap[$status] : '未知状态';
    }


    public function detele_data(){
        $table = 'logistics';
        $prefix = Config::get('database.prefix');
        $fullTable = $prefix . $table;

        // 动态获取字段，排除主键(通常为自增id)，否则“全字段重复”永远不成立
        $columns = Db::query("SHOW COLUMNS FROM `{$fullTable}`");
        if (empty($columns)) {
            $this->success('表不存在或无字段', [
                'table' => $table,
                'groups' => [],
            ]);
        }

        $allFields = [];
        $autoIncrementField = null;
        foreach ($columns as $col) {
            if (!isset($col['Field'])) {
                continue;
            }
            $allFields[] = $col['Field'];
            if (!empty($col['Extra']) && stripos($col['Extra'], 'auto_increment') !== false) {
                $autoIncrementField = $col['Field'];
            }
        }

        $pkRows = Db::query("SHOW KEYS FROM `{$fullTable}` WHERE Key_name='PRIMARY'");
        $pkField = !empty($pkRows) && !empty($pkRows[0]['Column_name']) ? $pkRows[0]['Column_name'] : $autoIncrementField;

        $groupFields = [];
        foreach ($allFields as $f) {
            if ($pkField && $f === $pkField) {
                continue;
            }
            $groupFields[] = $f;
        }

        if (empty($groupFields)) {
            $this->success('无可用于判重的字段(可能只有主键)', [
                'table' => $table,
                'primary_key' => $pkField,
                'groups' => [],
            ]);
        }

        $quotedGroupFields = array_map(function ($f) {
            return '`' . str_replace('`', '``', $f) . '`';
        }, $groupFields);

        $selectFields = implode(', ', $quotedGroupFields);
        $groupBy = $selectFields;

        if (!$pkField) {
            $this->success('未找到主键字段，无法执行删除', [
                'table' => $table,
                'primary_key' => $pkField,
            ]);
        }
        $qpk = '`' . str_replace('`', '``', $pkField) . '`';
        $idsSelect = "GROUP_CONCAT({$qpk} ORDER BY {$qpk}) AS ids";

        $sql = "SELECT {$selectFields}, COUNT(*) AS dup_count, {$idsSelect}
                FROM `{$fullTable}`
                GROUP BY {$groupBy}
                HAVING COUNT(*) > 1
                ORDER BY dup_count DESC";

        $groups = Db::query($sql);
        $totalRowsInGroups = 0;
        foreach ($groups as $g) {
            $totalRowsInGroups += (int)($g['dup_count'] ?? 0);
        }

        if (empty($groups)) {
            $this->success('无重复数据', [
                'table' => $table,
                'primary_key' => $pkField,
                'group_fields' => $groupFields,
                'duplicate_group_count' => 0,
                'duplicate_rows_total' => 0,
                'deleted_rows' => 0,
            ]);
        }

        // 删除重复：每组保留最小主键(id)，其余全部删除
        // 使用 NULL-safe 比较(<=>)确保字段为NULL时也能正确匹配
        $nullSafeOn = [];
        foreach ($groupFields as $f) {
            $qf = '`' . str_replace('`', '``', $f) . '`';
            $nullSafeOn[] = "t.{$qf} <=> d.{$qf}";
        }
        $onClause = implode(' AND ', $nullSafeOn);

        $derived = "SELECT MIN({$qpk}) AS keep_id, {$selectFields}
                    FROM `{$fullTable}`
                    GROUP BY {$groupBy}
                    HAVING COUNT(*) > 1";

        $deleteSql = "DELETE t FROM `{$fullTable}` t
                      INNER JOIN ({$derived}) d
                        ON {$onClause}
                      WHERE t.{$qpk} <> d.keep_id";

        Db::startTrans();
        try {
            $deletedRows = Db::execute($deleteSql);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->success('删除失败', [
                'error' => $e->getMessage(),
            ]);
        }

        // 删除后再查一次，确认是否还有重复
        $groupsAfter = Db::query($sql);
        $totalRowsInGroupsAfter = 0;
        foreach ($groupsAfter as $g) {
            $totalRowsInGroupsAfter += (int)($g['dup_count'] ?? 0);
        }

        $this->success('success', [
            'table' => $table,
            'primary_key' => $pkField,
            'group_fields' => $groupFields,
            'duplicate_group_count_before' => count($groups),
            'duplicate_rows_total_before' => $totalRowsInGroups,
            'deleted_rows' => (int)$deletedRows,
            'duplicate_group_count_after' => count($groupsAfter),
            'duplicate_rows_total_after' => $totalRowsInGroupsAfter,
        ]);
    }


    public function adderss_recognize()
    {
        $address = $this->request->param('address');
        if ($address === '' || $address === null) {
            $this->error('请输入地址');
        }
        $host = "https://addre.market.alicloudapi.com";
        $path = "/format";
        $method = 'GET';
        $appcode = '0a04176d44f74b2c92e45ecc7ccb3f8f';
        $headers = ['Authorization:APPCODE ' . $appcode];
//        $querys = "rawAddress=rawAddress";
        $url = $host . $path . '?' . http_build_query(['text' => $address]);
        $raw = curlget($url, $headers, $method, $host);
//        print_r($raw);die;
        // curlget 开启了 CURLOPT_HEADER，需去掉响应头再解析正文
        $body = $raw;
        if (is_string($raw) && strpos($raw, "\r\n\r\n") !== false) {
            $body = substr($raw, strpos($raw, "\r\n\r\n") + 4);
        }
        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('地址识别服务返回异常', ['raw' => $body]);
        }
        $this->success('ok', $decoded['data']);


    }

    /**
     * 阿里云市场银行卡四要素核验（四川涪擎 bcard3and4）
     * GET https://bcard3and4.market.alicloudapi.com/bankCheck4New
     * 参数：name 姓名；id_no 身份证号；card_no 银行卡号；phone_no 预留手机号
     * 核验通过后写入 fa_user_bankcard_auth，绑定当前登录用户
     */
    public function bankcard4()
    {
        $user = $this->auth->getUser();
        $uid = 0;
        if ($user) {
            $uid = (int)(is_array($user) ? ($user['id'] ?? 0) : ($user->id ?? 0));
        }
        if ($uid <= 0) {
            $this->error('请登录');
        }

        $name = trim((string)$this->request->param('name', ''));
        $idNo = trim((string)$this->request->param('id_no', $this->request->param('idCard', '')));
        $cardNo = trim((string)$this->request->param('card_no', $this->request->param('accountNo', '')));
        $phoneNo = trim((string)$this->request->param('phone_no', $this->request->param('mobile', '')));

        if ($name === '') {
            $this->error('请输入姓名');
        }
        if ($idNo === '') {
            $this->error('请输入身份证号');
        }
        if ($cardNo === '') {
            $this->error('请输入银行卡号');
        }
        if ($phoneNo === '') {
            $this->error('请输入预留手机号');
        }

        $host = 'https://bcard3and4.market.alicloudapi.com';
        $path = '/bankCheck4New';
        $appcode = trim((string)(Env::get('foqing.appcode') ?: '0a04176d44f74b2c92e45ecc7ccb3f8f'));
        $url = $host . $path . '?' . http_build_query([
            'name'      => $name,
            'idCard'    => $idNo,
            'mobile'    => $phoneNo,
            'accountNo' => $cardNo,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER         => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization:APPCODE ' . $appcode,
            ],
        ]);
        $raw = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if ($raw === false) {
            $this->error('银行卡四要素请求失败', ['curl_error' => $curlErr, 'http_code' => $httpCode]);
        }

        $respHeaders = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);
        $gatewayMsg = '';
        if (preg_match('/X-Ca-Error-Message:\s*(.+)/i', $respHeaders, $m)) {
            $gatewayMsg = trim($m[1]);
        }

        if ($httpCode === 401 || $httpCode === 403) {
            $this->error($gatewayMsg !== '' ? $gatewayMsg : '鉴权失败，请检查 AppCode', [
                'http_code'   => $httpCode,
                'gateway_msg' => $gatewayMsg,
            ]);
        }
        if ($body === '' || $body === false) {
            $this->error('银行卡四要素服务无响应', [
                'http_code'   => $httpCode,
                'gateway_msg' => $gatewayMsg,
            ]);
        }
        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            $this->error('银行卡四要素服务返回异常', ['raw' => $body, 'http_code' => $httpCode]);
        }

        // 涪擎返回：status=01 通过；其它不通过（见 msg）
        $status = isset($decoded['status']) ? (string)$decoded['status'] : '';
        $msg = isset($decoded['msg']) ? (string)$decoded['msg'] : '';
        if ($status !== '01') {
            \think\Log::error('银行卡四要素核验失败 uid=' . $uid
                . ' name=' . $name . ' id_no=' . $idNo . ' card_no=' . $cardNo
                . ' mobile=' . $phoneNo . ' msg=' . $msg
                . ' trace=' . (isset($decoded['traceId']) ? $decoded['traceId'] : '')
                . ' raw=' . $body, ['bankcard4']);
            $this->error($msg !== '' ? $msg : '信息不匹配', $decoded);
        }

        $bankName = trim((string)$this->request->param('bank_name', ''));
        $apiBank = isset($decoded['bank']) ? trim((string)$decoded['bank']) : '';
        $now = time();
        $row = [
            'uid'        => $uid,
            'name'       => $name,
            'id_card'    => $idNo,
            'account_no' => $cardNo,
            'mobile'     => $phoneNo,
            'status'     => $status,
            'msg'        => $msg,
            'bank'       => $apiBank !== '' ? $apiBank : $bankName,
            'card_name'  => isset($decoded['cardName']) ? (string)$decoded['cardName'] : '',
            'card_type'  => isset($decoded['cardType']) ? (string)$decoded['cardType'] : '',
            'trace_id'   => isset($decoded['traceId']) ? (string)$decoded['traceId'] : '',
            'updatetime' => $now,
        ];
        $exists = Db::name('user_bankcard_auth')->where('uid', $uid)->find();
        if ($exists) {
            Db::name('user_bankcard_auth')->where('uid', $uid)->update($row);
        } else {
            $row['createtime'] = $now;
            Db::name('user_bankcard_auth')->insert($row);
        }

        $this->success('认证通过', $decoded);
    }

    /**
     * 查询当前用户银行卡四要素实名信息（敏感字段脱敏）
     */
    public function bankcardAuthInfo()
    {
        $user = $this->auth->getUser();
        $uid = 0;
        if ($user) {
            $uid = (int)(is_array($user) ? ($user['id'] ?? 0) : ($user->id ?? 0));
        }
        if ($uid <= 0) {
            $this->error('请登录');
        }

        $row = Db::name('user_bankcard_auth')
            ->where('uid', $uid)
            ->where('status', '01')
            ->find();
        if (!$row) {
            $this->success('未认证', ['is_auth' => 0]);
        }

        $bank = trim((string)($row['bank'] ?? ''));
        if ($bank === '') {
            $bank = trim((string)($row['card_name'] ?? ''));
        }

        $this->success('ok', [
            'is_auth'    => 1,
            'name'       => $this->maskRealName((string)$row['name']),
            'mobile'     => $this->maskMobile((string)$row['mobile']),
            'id_card'    => $this->maskIdCard((string)$row['id_card']),
            'account_no' => $this->maskBankCard((string)$row['account_no']),
            'bank'       => $bank,
            'card_type'  => (string)($row['card_type'] ?? ''),
            'auth_time'  => !empty($row['updatetime']) ? date('Y-m-d H:i', (int)$row['updatetime']) : '',
        ]);
    }

    /** 姓名脱敏：张* / 欧** */
    protected function maskRealName($name)
    {
        $name = trim((string)$name);
        $len = mb_strlen($name, 'UTF-8');
        if ($len <= 0) {
            return '';
        }
        if ($len === 1) {
            return '*';
        }
        if ($len === 2) {
            return mb_substr($name, 0, 1, 'UTF-8') . '*';
        }
        return mb_substr($name, 0, 1, 'UTF-8') . str_repeat('*', $len - 2) . mb_substr($name, -1, 1, 'UTF-8');
    }

    /** 手机号脱敏：138****5678 */
    protected function maskMobile($mobile)
    {
        $mobile = preg_replace('/\D/', '', (string)$mobile);
        $len = strlen($mobile);
        if ($len < 7) {
            return $mobile === '' ? '' : str_repeat('*', $len);
        }
        return substr($mobile, 0, 3) . str_repeat('*', max(0, $len - 7)) . substr($mobile, -4);
    }

    /** 身份证脱敏：前6后4，中间* */
    protected function maskIdCard($idCard)
    {
        $idCard = trim((string)$idCard);
        $len = strlen($idCard);
        if ($len < 8) {
            return $idCard === '' ? '' : str_repeat('*', $len);
        }
        return substr($idCard, 0, 6) . str_repeat('*', $len - 10) . substr($idCard, -4);
    }

    /** 银行卡脱敏：保留后4位 */
    protected function maskBankCard($cardNo)
    {
        $cardNo = preg_replace('/\D/', '', (string)$cardNo);
        $len = strlen($cardNo);
        if ($len <= 4) {
            return $cardNo === '' ? '' : str_repeat('*', $len);
        }
        return str_repeat('*', $len - 4) . substr($cardNo, -4);
    }

    /**
     * 快递鸟物流轨迹查询（快递查询 API，RequestType 8002，正式地址 /api/dist）
     * 参数：logistic_code 运单号（必填）；shipper_code 快递公司编码（可选，主流多家可不传）；order_code、customer_name 可选（顺丰非鸟渠道单号时 customer_name 为手机后四位）
     */
    public function kdniaoTrack()
    {
        $logisticCode = trim((string)$this->request->param('logistic_code', ''));
        if ($logisticCode === '') {
            $this->error('请传入运单号 logistic_code');
        }

        $ebusinessId = $this->kdniaoNormalizeCredential(
            Env::get('kdniao.ebusiness_id') ?: Config::get('site.kdniao_ebusiness_id')
        );
        $appKey = $this->kdniaoNormalizeCredential(
            Env::get('kdniao.app_key') ?: Config::get('site.kdniao_app_key')
        );
        $apiUrl = trim((string)(Env::get('kdniao.api_url') ?: Config::get('site.kdniao_api_url') ?: 'https://api.kdniao.com/api/dist'));

        if ($ebusinessId === '' || $appKey === '') {
            $this->error('未配置快递鸟：在 .env 的 [kdniao] 中填写 ebusiness_id、app_key，或在站点配置中填写 kdniao_ebusiness_id、kdniao_app_key');
        }

        $payload = ['LogisticCode' => $logisticCode];
        $shipperCode = trim((string)$this->request->param('shipper_code', ''));
        if ($shipperCode !== '') {
            $payload['ShipperCode'] = $shipperCode;
        }
        $orderCode = $this->request->param('order_code', '');
        if ($orderCode !== '' && $orderCode !== null) {
            $payload['OrderCode'] = (string)$orderCode;
        }
        $customerName = $this->request->param('customer_name', '');
        if ($customerName !== '' && $customerName !== null) {
            $payload['CustomerName'] = (string)$customerName;
        }

        $requestData = json_encode($payload, JSON_UNESCAPED_UNICODE);
        // 与快递鸟「接口签名验证」一致：MD5(未编码的 RequestData + ApiKey，16 字节) → Base64 → URL 编码
        $dataSign = urlencode(base64_encode(md5($requestData . $appKey, true)));
        $postFields = 'RequestType=8002'
            . '&EBusinessID=' . urlencode($ebusinessId)
            . '&RequestData=' . urlencode($requestData)
            . '&DataSign=' . $dataSign
            . '&DataType=2';

        $fallbackUrl = 'https://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx';
        $tryUrls = [$apiUrl];
        if (strcasecmp(rtrim($apiUrl, '/'), rtrim($fallbackUrl, '/')) !== 0) {
            $tryUrls[] = $fallbackUrl;
        }

        $lastFail = null;
        foreach ($tryUrls as $tryUrl) {
            $raw = $this->kdniaoHttpPost($tryUrl, $postFields);
            if (!is_string($raw) || $raw === '') {
                $lastFail = ['msg' => '快递鸟接口无响应', 'url' => $tryUrl];
                continue;
            }
            $result = json_decode($raw, true);
            if (!is_array($result)) {
                Log::error('kdniaoTrack parse error url=' . $tryUrl . ' body=' . $raw);
                $lastFail = ['msg' => '快递鸟返回数据解析失败', 'raw' => $raw, 'url' => $tryUrl];
                continue;
            }
            if (!empty($result['Success'])) {
                if ($tryUrl !== $apiUrl) {
                    $result['_kdniao_effective_url'] = $tryUrl;
                }
                $this->success('ok', $result);
            }
            $reason = isset($result['Reason']) ? (string)$result['Reason'] : '查询失败';
            $lastFail = ['msg' => $reason, 'data' => $result, 'url' => $tryUrl];
            if (strpos($reason, '非法参数') === false) {
                break;
            }
        }

        if ($lastFail && isset($lastFail['data'])) {
            $this->error($lastFail['msg'], $lastFail['data']);
        }
        $this->error($lastFail['msg'] ?? '快递鸟请求失败', $lastFail ?? []);
    }

    /**
     * 快递鸟 POST（独立 cURL，避免 httpUtils 使用空 User-Agent 等导致网关异常）
     */
    private function kdniaoHttpPost($url, $postBody)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded;charset=utf-8',
                'User-Agent: Mozilla/5.0 (compatible; KdniaoApiClient/1.0)',
            ],
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        return $raw === false ? '' : $raw;
    }

    /**
     * 去除配置里常见的不可见字符（避免后台粘贴带入 BOM/空格导致签名校验失败）
     */
    private function kdniaoNormalizeCredential($value)
    {
        if ($value === null || (is_string($value) && $value === '')) {
            return '';
        }
        $s = trim((string)$value);
        $s = preg_replace('/^\xEF\xBB\xBF/', '', $s);
        $s = trim(str_replace(["\r", "\n", "\t"], '', $s));
        if (strlen($s) >= 2) {
            $a = $s[0];
            $b = substr($s, -1);
            if (($a === '"' && $b === '"') || ($a === "'" && $b === "'")) {
                $s = substr($s, 1, -1);
                $s = trim($s);
            }
        }
        return $s;
    }
  public function company(){
        $data = Db::name('company')->select();
        foreach ($data as $k=>$v){
            $data[$k]['company_image'] = 'https://lzwl.longzhehutong.cn/'.$v['company_image'];
        }
        $this->success('查询成功', $data);
  }
}
