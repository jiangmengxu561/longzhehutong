<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use app\admin\model\Logistics as LogisticsModel;

/**
 * 专线接口
 */
class Dedicated extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 专线首页
     *
     */
    public function OrderList()
    {
        $user = $this->auth->getUserinfo();
        if (!$user) {
            $this->error('请登录');
        }
        $logistics = Db::name('logistics')->where('uid',$user['id'])->find();
        if (empty($logistics)){
            $this->error('请先入驻');
        }
        $dricerorder  = Db::name('dricerorder')
            ->where('type',2)
            ->where('status',3)
            ->order('id desc')
            ->select();
        $orderList = [];
        foreach ($dricerorder as &$val){
            $info = Db::name('order')
                ->where('orderid',$val['order_id'])
                ->field('id,orderid,logistics_id,earliest_time,latest_time,logistics_driver_cost,createtime,loading,unload,pay_type,pay_status')
                ->order('createtime desc')
                ->where('find_car_type','配车')
                ->where('backend_status',2)
                ->find();
            if (!empty($info['pay_type'])) {
                if ($info['pay_type'] == 2) {
                    if (in_array($info['pay_status'], [2,5], true)) {
                        array_push($orderList, $info);
                    }
                } else if ($info['pay_type'] == 1) {
                    array_push($orderList, $info);
                }
            }
        }
        foreach ($orderList as &$order){
            $origincity = Db::name('user_address')->where('id',$order['loading'])->value('address');
            $province = Db::name('user_address')->where('id',$order['unload'])->value('address');
            $order['origincity'] = extractProvince($origincity);
            $order['province'] = extractProvince($province);

            $earliest_time =  date('H:i', $order['earliest_time']);
            $earliest_day =  date('Y-m-d',$order['earliest_time']);
            $latest_time =  date('H:i', $order['latest_time']);
            $now = new \DateTime();
            $tomorrow = clone $now;
            $tomorrow->modify('+1 day');
            $today = date('Y-m-d');
            if ($earliest_day === $tomorrow->format('Y-m-d')) {
                $formatted_time = "明天 " . $earliest_time . "-" . $latest_time;
            } elseif ($earliest_day === $today) {
                $formatted_time = "今天" . $earliest_time . "-" . $latest_time;
            } else {
                $formatted_time = $earliest_day . " " . $earliest_time . "-" . $latest_time;
            }
            $order['time_range'] = $formatted_time;
            $order['createtime'] = date('Y-m-d H:i',$order['createtime']);
        }
        if ($orderList){
            $this->success('查询成功',$orderList);
        }else{
            $this->error('暂无订单');
        }
    }
    /**
     * @return void
     * 司机首页订单详情
     */
    public  function OrderlistDetail()
    {
        $order_id = $this->request->param();
        if (empty($order_id)){
            $this->error('信息不完整');
        }
        $OrderData = DB::name('order')
            ->where('orderid',$order_id['order_id'])
            ->field('id,orderid,logistics_id,truckstarttime,truckendtime,logistics_driver_cost,createtime,earliest_time,latest_time,goods_type_id,packaging_id,pay_type,delivery,delivery_type_id,receipt_type_id,unpack_id,other_id,text_message,pay_status,car_type_id,direction,weight,long,wide,hige,goods_image,quantity')
            ->find();
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
        $logisticsdata = Db::name('logistics')->where('id',$OrderData['logistics_id'])->find();
        if (!$logisticsdata){
            $this->error('物流信息有误');
        }
        $phone = [];
        //线路电话
        $admin_order = Db::name('admin_order')->where('order_id',$OrderData['id'])->select();
        foreach ($admin_order as &$v){
            $group_id = Db::name('auth_group_access')->where('uid',$v['admin_id'])->value('group_id');
            $identity = Db::name('auth_group')->where('id',$group_id)->value('identity');
            $admin_mobile = Db::name('admin')->where('id',$v['admin_id'])->value('mobile');
            if ($identity == 2){
                $phone['xianlu'] = $admin_mobile;
            }elseif ($identity == 3){
                $phone['diaodu'] = $admin_mobile;
            }
        }
        // 处理 unload 地址
        $OrderData['loading_address']  =$logisticsdata['shipping_logistics_address'];
        $OrderData['loading_mobile']  = $phone['xianlu'];

        // 处理 loading 地址
        $OrderData['unload_address']  = $logisticsdata['arrival_logistics_address'];
        $OrderData['unload_mobile']  =  $phone['diaodu'] ?? '调度待接单';
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
        $OrderData['sizeList'] = Db::name('dimensions')->where('order_id',$OrderData['orderid'])->select();
        $OrderData['packaging_list'] = Db::name('packaging_num')->where('order_id',$OrderData['orderid'])->select();
        if ($OrderData){
            $this->success('订单详情查看',$OrderData);
        }else{
            $this->error('订单详情查看失败');
        }

    }


    /**
     * @return void
     *
     * 专线订单列表
     */
    public  function dricerorder()
    {
        $user = $this->auth->id;
        $type = $this->request->post('type');
        $order_list = Db::name('dricerorder')
            ->order('id desc')
            ->where('d_id',$user);

        if ($type != 0 ){
            $order_list->where('status',$type);
        }
        $order_list->where('type',2);
        $order_list = $order_list->select();

        foreach ($order_list as &$value){
//            $value['createtime'] = date('Y-m-d H:i',$value['createtime']);
            $value['createtime'] = $value['order_id'];
            $orderinfo = Db::name('order')->where('orderid',$value['order_id'])->field('id,unload,logistics_id,loading,logistics_id,shipment_fee,pickup_fee')->find();
            if (!$orderinfo){
                $this->error('订单信息有误');
            }
            $phone = [];
            //线路电话
            $admin_order = Db::name('admin_order')->where('order_id',$orderinfo['id'])->select();
            foreach ($admin_order as &$v){
                $group_id = Db::name('auth_group_access')->where('uid',$v['admin_id'])->value('group_id');
                $identity = Db::name('auth_group')->where('id',$group_id)->value('identity');
                $admin_mobile = Db::name('admin')->where('id',$v['admin_id'])->value('mobile');
                if ($identity == 2){
                    $phone['xianlu'] = $admin_mobile;
                }elseif ($identity == 3){
                    $phone['diaodu'] = $admin_mobile;
                }
            }

            $value['orderinfo'] = $orderinfo;
            $logisticsdata = Db::name('logistics')->where('id',$orderinfo['logistics_id'])->find();
            if (!$logisticsdata){
                $this->error('物流信息有误');
            }
            // 处理 unload 地址
            $value['loading_address']  = $logisticsdata['shipping_logistics_address'];
            $value['loading_mobile']  = $phone['xianlu'];

            // 处理 loading 地址
            $value['unload_address']  = $logisticsdata['arrival_logistics_address'];
            $value['unload_mobile']  =  $phone['diaodu'] ?? '调度待接单';
            if($value['status'] == 1){
                $value['status_name'] = '服务中';
            }elseif ($value['status'] == 2){
                $value['status_name'] = '已完成';
            }elseif ($value['status'] == 3){
                $value['status_name'] = '待接单';
            }elseif ($value['status'] == 4){
                $value['status_name'] = '已取消';
            }

        }
        $this->success('success', $order_list);
    }


    /**
     *
     * 专线订单详情
     */
    public function orderdetail()
    {
        $user = $this->auth->id;
        if (!$user) {
            $this->error('请登录');
        }
        $orderNumber = $this->request->param('order_id');
        $recordId = $this->request->param('id');

        if (empty($orderNumber) && empty($recordId)) {
            $this->error('信息不完整');
        }
        $dricerOrderQuery = Db::name('dricerorder')
            ->where('d_id', $user)
            ->where('type', 2);
        $songdricerOrderQuery = Db::name('dricerorder')
            ->where('type', 1)
            ->where('status',2)
            ->value('unsettime');
        if (!empty($recordId)) {
            $dricerOrderQuery->where('id', $recordId);
        } else {
            $dricerOrderQuery->where('order_id', $orderNumber);
        }

        $dricerOrder = $dricerOrderQuery->find();

        if (!$dricerOrder) {
            $this->error('订单不存在或无权查看');
        }

        $order = Db::name('order')->where('orderid', $dricerOrder['order_id'])->find();
        if (!$order) {
            $this->error('订单信息有误');
        }

        $logistics = Db::name('logistics')->where('id', $order['logistics_id'])->find();

        if (!$logistics) {
            $this->error('物流信息有误');
        }
        if ($songdricerOrderQuery){
            $truckStart = date('Y-m-d H:i',$songdricerOrderQuery);
        }else{
            $truckStart = '司机送货中';
        }
        if ($dricerOrder['unsettime']){
            $truckEnd = date('Y-m-d H:i',$dricerOrder['unsettime']);
        }else {
            $truckEnd = '专线送货中';
        }
        $phone = [];
        //线路电话
        $admin_order = Db::name('admin_order')->where('order_id',$order['id'])->select();
        foreach ($admin_order as &$v){
            $group_id = Db::name('auth_group_access')->where('uid',$v['admin_id'])->value('group_id');
            $identity = Db::name('auth_group')->where('id',$group_id)->value('identity');
            $admin_mobile = Db::name('admin')->where('id',$v['admin_id'])->value('mobile');
            if ($identity == 2){
                $phone['xianlu'] = $admin_mobile;
            }elseif ($identity == 3){
                $phone['diaodu'] = $admin_mobile;
            }
        }

        // 订单状态映射
        $statusMap = [
            1 => '服务中',
            2 => '已完成',
            3 => '待接单',
            4 => '已取消',
        ];

        $payStatusMap = [
            1 => '待付款',
            2 => '进行中',
            3 => '已完成',
            4 => '已取消',
            5 => '待下单',
        ];
        $carType = Db::name('car_type')->where('id', $order['car_type_id'])->value('name');

        // 返回数据整理
        $detail = [
            'order_id' => $dricerOrder['order_id'],
            'dricer_order_id' => $dricerOrder['id'],
            'status' => (int)$dricerOrder['status'],
            'status_name' => $statusMap[$dricerOrder['status']] ?? '未知状态',
            'pay_status' => (int)$order['pay_status'],
            'pay_status_name' => $payStatusMap[$order['pay_status']] ?? '未知状态',
            'price' => isset($dricerOrder['price']) ? (float)$dricerOrder['price'] : (float)$order['logistics_driver_cost'],
            'logistics_cost' => (float)($order['logistics_driver_cost'] ?? 0),
            'truckstarttime' =>$truckStart,
            'truckendtime' => $truckEnd,
            'direction' =>$order['direction'],
            'weight' =>$order['weight'],
            'long' =>$order['long'],
            'wide' =>$order['wide'],
            'hige' =>$order['hige'],
            'goods_type_id' =>Db::name('goods_type')->where('id',$order['goods_type_id'])->value('name'),
            'packaging_id' =>$order['packaging_id'],
            'goods_image' =>$order['goods_image'],
            'quantity' =>$order['quantity'],
//            'time_range' => $timeRange,
//            'createtime' => date('Y-m-d H:i', $dricerOrder['createtime'] ?? $order['createtime']),
            'logistics' => [
                'name' => $logistics['shipping_logistics_name'] ?? '',
                'start_address' => $logistics['shipping_logistics_address'] ?? '',
                'start_mobile' => $phone['xianlu'] ?? '',
                'end_address' => $logistics['arrival_logistics_address'] ?? '',
                'end_mobile' =>  $phone['diaodu'] ?? '调度待接单',
                'distance' => $logistics['distance'] ?? 0,
                'time_limit' => $logistics['time_limit'] ?? 0,
            ],
        ];
        $this->success('订单详情查询成功', $detail);
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
//        $dricerorder = Db::name('dricerorder')->where('id',$id['id'])->find();
//        $ress = Db::name('order')->where('order_id',$dricerorder['order_id'])->update(['mobile'=>'']);
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
        if (empty($data)) {
            $this->error('填写信息不完整');
        }
        $requiredFields = ['username', 'bankcode', 'bankname'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->error('填写信息不完整1');
            }
        }
        if ($data['price'] <= 0) {
            $this->error('提现金额需大于0元');
        }
        if ($data['price'] > $user->money) {
            $this->error('余额不足');
        }
        $data['uid'] = $user->id;
        $data['createtime'] = time();
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
     * 专线入驻（一个起点，多个落货点）
     */
    public function addsettle()
    {
        $user = $this->auth->id;
        if (!$user){
            $this->error('请登录');
        }
        $shipping_province = $this->request->param('shipping_province');
        $origincity = $this->request->param('origincity');
        $shipping_area = $this->request->param('shipping_area');
        $shipping_logistics_park = $this->request->param('shipping_logistics_park');
        $shipping_logistics_name = $this->request->param('shipping_logistics_name', '');
        $shipping_logistics_address = $this->request->param('shipping_logistics_address');
        $shipping_contact_person = $this->request->param('shipping_contact_person');
        $shipping_logistics_mobile = $this->request->param('shipping_logistics_mobile');
        $shipping_longitude = $this->request->param('shipping_longitude', '');
        $shipping_latitude = $this->request->param('shipping_latitude', '');
        $business_license = $this->request->param('business_license', '');
        $doorway_image = $this->request->param('doorway_image', '');
        $edit_shipping_key = $this->request->param('edit_shipping_key', '', null);
        $arrival_list = $this->parseArrivalList();

        if (empty($shipping_province) || empty($origincity) || empty($shipping_area)) {
            $this->error('请选择发货地址');
        }
        if (empty($shipping_logistics_park)) {
            $this->error('请填写发货物流园');
        }
        if (empty($arrival_list) || !is_array($arrival_list)) {
            $this->error('请至少添加一个落货地址');
        }

        $existingList = Db::name('logistics')->where('uid', $user)->select();
        $keepStatus = '1';
        $keepCreatetime = time();
        if ($existingList) {
            $keepStatus = $existingList[0]['status'] ?? '1';
            $keepCreatetime = $existingList[0]['createtime'] ?? time();
        }
        $newShippingKey = $this->buildShippingKey($shipping_province, $origincity, $shipping_area);
        $isEditMode = !empty($edit_shipping_key);
        $isUpdate = $isEditMode;

        Db::startTrans();
        try {
            // 修改：只删被编辑的那个发货地；新增：若同发货地已存在则覆盖该发货地，不影响其他发货地
            $removeKey = $isEditMode ? $edit_shipping_key : $newShippingKey;
            if ($removeKey) {
                $parts = $this->parseShippingKey($removeKey);
                if ($parts) {
                    Db::name('logistics')->where([
                        'uid' => $user,
                        'shipping_province' => $parts['shipping_province'],
                        'origincity' => $parts['origincity'],
                        'shipping_area' => $parts['shipping_area'],
                    ])->delete();
                }
            } 
 
            foreach ($arrival_list as $index => $arrival) {
                if (empty($arrival['province']) || empty($arrival['destination']) || empty($arrival['arrival_area'])) {
                    throw new \Exception('落货地址不完整');
                }
                // 每个落货地各自独立：联系人、发货电话、落货电话
                $itemContact = trim((string)($arrival['shipping_contact_person'] ?? ''));
                $itemShippingMobile = trim((string)($arrival['shipping_logistics_mobile'] ?? ''));
                $itemArrivalMobile = trim((string)($arrival['arrival_logistics_mobile'] ?? ''));
                // 仅单条落货时兼容旧前端顶层字段；多条时禁止用第一条兜底，避免串数据
                if (count($arrival_list) === 1) {
                    if ($itemContact === '') {
                        $itemContact = trim((string)$shipping_contact_person);
                    }
                    if ($itemShippingMobile === '') {
                        $itemShippingMobile = trim((string)$shipping_logistics_mobile);
                    }
                }
                if ($itemContact === '' || $itemShippingMobile === '') {
                    throw new \Exception('请填写落货信息' . ($index + 1) . '的发货联系人与电话');
                }
                if ($itemArrivalMobile === '') {
                    throw new \Exception('请填写落货信息' . ($index + 1) . '的落货物流园电话');
                }
                $data = [
                    'uid' => $user,
                    'shipping_province' => $shipping_province,
                    'origincity' => $origincity,
                    'province' => $arrival['province'],
                    'destination' => $arrival['destination'],
                    'shipping_area' => $shipping_area,
                    'shipping_logistics_park' => $shipping_logistics_park,
                    'shipping_logistics_name' => $shipping_logistics_name ?: $shipping_logistics_park,
                    'shipping_logistics_address' => $shipping_logistics_address,
                    'shipping_logistics_mobile' => $itemShippingMobile,
                    'shipping_longitude' => $shipping_longitude,
                    'shipping_latitude' => $shipping_latitude,
                    'arrival_area' => $arrival['arrival_area'],
                    'arrival_logistics_park' => $arrival['arrival_logistics_park'] ?? '',
                    'arrival_logistics_name' => $arrival['arrival_logistics_name'] ?? ($arrival['arrival_logistics_park'] ?? ''),
                    'arrival_logistics_address' => $arrival['arrival_logistics_address'] ?? '',
                    'arrival_logistics_mobile' => $itemArrivalMobile,
                    'arrival_longitude' => $arrival['arrival_longitude'] ?? '',
                    'arrival_latitude' => $arrival['arrival_latitude'] ?? '',
                    'time_limit' => $arrival['time_limit'] ?? '',
                    'side' => $arrival['side'] ?? '',
                    'perton' => $arrival['perton'] ?? '',
                    'bulky' => $arrival['bulky'] ?? '',
                    'reflux' => $arrival['reflux'] ?? '',
                    'remarks' => $arrival['remarks'] ?? '',
                    'createtime' => $keepCreatetime,
                    'status' => $keepStatus,
                    'shipping_contact_person' => $itemContact,
                ];
                $logisticsModel = new LogisticsModel();
                $res = $logisticsModel->save($data);
                if ($res === false) {
                    throw new \Exception('线路保存失败');
                }
            }

            // 营业执照、门头照写入 settle（按用户一份）
            if ($business_license !== '' || $doorway_image !== '') {
                $settle = Db::name('settle')->where('uid', $user)->find();
                $settleData = [
                    'uid' => $user,
                    'postcard_front_image' => $business_license,
                    'doorway_image' => $doorway_image,
                ];
                if ($settle) {
                    Db::name('settle')->where('id', $settle['id'])->update($settleData);
                } else {
                    $settleData['createtime'] = time();
                    $settleData['status'] = 0;
                    Db::name('settle')->insert($settleData);
                }
            }

            // 同步营业执照到用户表（如有该字段）
            if ($business_license !== '') {
                try {
                    Db::name('user')->where('id', $user)->update(['business_license' => $business_license]);
                } catch (\Exception $e) {
                }
            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error($e->getMessage() ?: '操作失败,请联系管理员处理');
        }

        if ($isEditMode) {
            $this->success('发货地修改成功');
        } elseif (!empty($existingList)) {
            $this->success('发货地添加成功');
        } else {
            $this->success('专线入驻申请提交成功,等待审核');
        }
    }

    /**
     * 解析落货地址列表（兼容数组 / JSON字符串 / 旧版单落货字段）
     */
    protected function parseArrivalList()
    {
        $arrival_list = null;

        // 优先从原始 JSON body 读取，避免 Api 控制器的 htmlspecialchars 过滤器破坏数据
        $rawInput = $this->request->getInput();
        if (!empty($rawInput)) {
            $jsonBody = json_decode($rawInput, true);
            if (is_array($jsonBody) && isset($jsonBody['arrival_list'])) {
                $arrival_list = $jsonBody['arrival_list'];
            }
        }

        if ($arrival_list === null || $arrival_list === '') {
            // 第三个参数传 null，跳过默认过滤器
            $arrival_list = $this->request->param('arrival_list', null, null);
        }

        if (is_string($arrival_list) && $arrival_list !== '') {
            $decoded = json_decode($arrival_list, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decoded = json_decode(htmlspecialchars_decode($arrival_list), true);
            }
            $arrival_list = $decoded;
        }

        // 兼容旧版单落货点提交
        if (empty($arrival_list) || !is_array($arrival_list)) {
            $province = $this->request->param('province');
            $destination = $this->request->param('destination');
            $arrival_area = $this->request->param('arrival_area');
            if ($province || $destination || $arrival_area) {
                $arrival_list = [[
                    'province' => $province,
                    'destination' => $destination,
                    'arrival_area' => $arrival_area,
                    'arrival_logistics_park' => $this->request->param('arrival_logistics_park'),
                    'arrival_logistics_name' => $this->request->param('arrival_logistics_name', ''),
                    'arrival_logistics_address' => $this->request->param('arrival_logistics_address'),
                    'arrival_logistics_mobile' => $this->request->param('arrival_logistics_mobile'),
                    'arrival_longitude' => $this->request->param('arrival_longitude', ''),
                    'arrival_latitude' => $this->request->param('arrival_latitude', ''),
                    'time_limit' => $this->request->param('time_limit'),
                    'side' => $this->request->param('side'),
                    'perton' => $this->request->param('perton'),
                    'bulky' => $this->request->param('bulky'),
                    'reflux' => $this->request->param('reflux'),
                    'remarks' => $this->request->param('remarks', ''),
                ]];
            }
        }

        return is_array($arrival_list) ? array_values($arrival_list) : [];
    }

    /**
     * 生成发货地唯一键
     */
    protected function buildShippingKey($province, $city, $area)
    {
        return trim($province) . '|' . trim($city) . '|' . trim($area);
    }

    /**
     * 解析发货地唯一键
     */
    protected function parseShippingKey($key)
    {
        if (!$key || !is_string($key)) {
            return null;
        }
        $parts = explode('|', $key);
        if (count($parts) < 3) {
            return null;
        }
        return [
            'shipping_province' => $parts[0],
            'origincity' => $parts[1],
            'shipping_area' => $parts[2],
        ];
    }

    /**
     * 删除某个发货地及其全部落货线路
     */
    public function delete_shipping()
    {
        $user = $this->auth->id;
        if (!$user) {
            $this->error('请登录');
        }
        $shipping_key = $this->request->param('shipping_key', '', null);
        $parts = $this->parseShippingKey($shipping_key);
        if (!$parts) {
            $parts = [
                'shipping_province' => $this->request->param('shipping_province'),
                'origincity' => $this->request->param('origincity'),
                'shipping_area' => $this->request->param('shipping_area'),
            ];
        }
        if (empty($parts['shipping_province']) || empty($parts['origincity']) || empty($parts['shipping_area'])) {
            $this->error('发货地参数不完整');
        }
        $res = Db::name('logistics')->where([
            'uid' => $user,
            'shipping_province' => $parts['shipping_province'],
            'origincity' => $parts['origincity'],
            'shipping_area' => $parts['shipping_area'],
        ])->delete();
        if ($res === false) {
            $this->error('删除失败');
        }
        $this->success('删除成功');
    }



    /**
     * @return void
     * 专线抢单
     */
    public function addorder(){
        $user = $this->auth->getUserinfo();
//        print_r($user);die;
        if (isset($user['error'])) {
            $this->error('请登录');
        }
        $order_id = $this->request->param('order_id');
        if (empty($order_id)){
            $this->error('信息不完整');
        }
        $orderinfo  = Db::name('order')->where('orderid',$order_id)->find();
        if (!$orderinfo){
            $this->error('订单不存在，无法抢单');
        }
        $logisticsdata = Db::name('logistics')->where('uid',$user['id'])->find();
        if (!$logisticsdata){
            $this->error('请先进行专线入驻' );
        }
//        if ($logisticsdata['id'] != $orderinfo['logistics_id']){
//            $this->error('该订单不属于您的专线，无法抢单');
//        }
        $existingOrder = Db::name('dricerorder')
            ->where('order_id', $order_id)
            ->where('status','<>',3)
            ->where('type',2)
            ->find();

        if ($existingOrder) {
            $this->error('该订单已被抢单');
        }
        $data = [
            'd_id' => $user['id'],
            'driver_name' => $user['username'],
            'driver_mobile' => $user['mobile'],
            'grabbingtime' => time(),
            'status' => 1,
            'type' =>2
        ];
        $res = Db::name('dricerorder')
            ->where('order_id',$order_id)
            ->update($data);
        if ($res) {
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
        $id = $this->request->param('id');
        if (empty($id)){
            $this->error('信息不完整');
        }
        $content = $this->request->param('content');
        $loading_images = $this->request->param('loading_images');
        $unloading_images = $this->request->param('unloading_images');
//        if (empty($images)){
//            $this->error('图片不能为空');
//        }
        $data = [
            'content' =>$content,
            'loading_images' => $loading_images,
            'unloading_images' => $unloading_images,
            'status' =>2,
            'unsettime'=>time()
        ];
        $dricerorder = Db::name('dricerorder')->where('id',$id)->find();
        $order = Db::name('order')->where('orderid',$dricerorder['order_id'])->find();
        $qudricerorder = Db::name('dricerorder')
            ->where('order_id',$order['orderid'])
            ->where('type',1)
            ->where('status',2)
            ->find();
        if (empty($qudricerorder)){
            $this->error('货物还没送到,不能点确认');
        }
        $res = Db::name('dricerorder')->where('id',$id)->update($data);
        if ($res){
            if ($dricerorder['type'] == 2){
                if ($order) {
                    Db::name('order')->where('orderid',$dricerorder['order_id'])->update(['type'=>3]);
                    $price = $order['logistics_driver_cost'] ?? 0;
                    if ($price > 0) {
                        Db::name('user')->where('id',$dricerorder['d_id'])->setInc('money',$price);
                    }
                    // 专线确认后增加轨迹：（南京转运中心）已到达（专线确认，客服电话）
                    $orderNumericId = $order['id'];
                    $logistics = Db::name('logistics')->where('id', $order['logistics_id'])->find();

                    if ($logistics) {
                        // 到货物流园名称或地址
                        $centerName = $logistics['arrival_logistics_park'] ?? '';
                        if ($centerName === '') {
                            $centerName = $logistics['arrival_logistics_address'] ?? '';
                        }
                        $customer = $this->getAdminContactByRole($orderNumericId, 3);
                        $customerMobile = $customer['mobile'] ?? '';
                        if (empty($customer)) {
                            $customer['name'] = '调度电话';
                        }
                        if ($centerName) {
                            Db::name('trajectory')->insert([
                                'order_id' => $orderNumericId,
                                'admin_name' => $customer['name'],
                                'admin_mobile' => $customerMobile,
                                'createtime' => time(),
                                'type' =>'('.$centerName.')'.'已到达',
                            ]);
                        }
                    }
                }
            }
            $this->success('订单已确认');
        }else{
            $this->error('订单确认失败');
        }
    }

    /**
     * 根据订单ID和角色关键字获取后台管理员联系方式（销售/调度/客服）
     *
     * @param int $orderId  order表主键ID
     * @param string $roleKeyword  角色关键字：销售 / 调度 / 客服
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

    /**
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function logistics_detail()
    {
        $user = $this->auth->id;
        $list = Db::name('logistics')->where('uid', $user)->order('id', 'asc')->select();
        if ($list) {
            $settle = Db::name('settle')->where('uid', $user)->find();
            $userInfo = Db::name('user')->where('id', $user)->field('business_license')->find();
            $business_license = $settle['postcard_front_image'] ?? ($userInfo['business_license'] ?? '');
            $doorway_image = $settle['doorway_image'] ?? '';

            $grouped = [];
            foreach ($list as $row) {
                $key = $this->buildShippingKey($row['shipping_province'], $row['origincity'], $row['shipping_area']);
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'shipping_key' => $key,
                        'address_text' => trim(($row['shipping_province'] ?? '') . ' ' . ($row['origincity'] ?? '') . ' ' . ($row['shipping_area'] ?? '')),
                        'shipping_province' => $row['shipping_province'] ?? '',
                        'origincity' => $row['origincity'] ?? '',
                        'shipping_area' => $row['shipping_area'] ?? '',
                        'shipping_logistics_park' => $row['shipping_logistics_park'] ?? '',
                        'shipping_logistics_name' => $row['shipping_logistics_name'] ?? '',
                        'shipping_logistics_address' => $row['shipping_logistics_address'] ?? '',
                        'shipping_logistics_mobile' => $row['shipping_logistics_mobile'] ?? '',
                        'shipping_longitude' => $row['shipping_longitude'] ?? '',
                        'shipping_latitude' => $row['shipping_latitude'] ?? '',
                        'arrival_list' => [],
                        'arrival_count' => 0,
                    ];
                }
                $grouped[$key]['arrival_list'][] = $row;
                $grouped[$key]['arrival_count'] = count($grouped[$key]['arrival_list']);
            }
            $shipping_list = array_values($grouped);
            $first = $shipping_list[0];

            $data = $first;
            $data['shipping_list'] = $shipping_list;
            $data['arrival_list'] = $first['arrival_list'];
            $data['business_license'] = $business_license;
            $data['postcard_front_image'] = $business_license;
            $data['doorway_image'] = $doorway_image;
            $this->success('查询成功', $data);
        } else {
            $this->error('还未入驻');
        }
    }
}
