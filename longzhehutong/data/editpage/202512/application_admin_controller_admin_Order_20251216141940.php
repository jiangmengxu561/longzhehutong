<?php

namespace app\admin\controller\admin;

use app\common\controller\Backend;
use app\common\library\Commission;
use app\common\model\User;
use app\common\model\MoneyLog;
use think\Config;
use think\Db;
use think\exception\DbException;
use think\response\Json;

/**
 * 后台抢单记录
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{

    /**
     * Order模型对象
     * @var \app\admin\model\admin\Order
     */
    protected $model = null;
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\admin\Order;

    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            $admin_id = $this->auth->id;
            $admin_group = Db::name('auth_group_access')->where('uid', $admin_id)->value('group_id');
            $groupInfo = Db::name('auth_group')->where('id', $admin_group)->find();
            $group_identity = isset($groupInfo['identity']) ? intval($groupInfo['identity']) : 0;
            // 仅路线/规划身份（identity=2）视为“销售”角色，用于前端展示控制
            $isSales = ($admin_group != 1 && $group_identity === 2);
            $this->view->assign('isSales', $isSales ? 1 : 0);
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        $admin_id = $this->auth->id;
        $admin_group_id = Db::name('auth_group_access')->where('uid',$admin_id)->value('group_id');
        $admin_group = Db::name('auth_group')->where('id', $admin_group_id)->find();
        $admin_group_name = $admin_group['name'] ?? '';
        $admin_group_identity = isset($admin_group['identity']) ? intval($admin_group['identity']) : 0;

        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->model
            ->where($where)
            ->where('admin_id',$admin_id)
            ->order($sort, $order)
            ->paginate($limit);
            foreach ($list as $k =>$v){
                $order_data = Db::name('order')
//                    ->where('pay_status','<>',4)
                    ->where('id',$v['order_id'])
                    ->find();
                if (empty($order_data)){
                    continue;
                }
                    $loading_address = Db::name('user_address')->where('id',$order_data['loading'])->find();
                    $unload_address = Db::name('user_address')->where('id',$order_data['unload'])->find();
                  if($loading_address){
                      $order_data['loading'] = $loading_address['user_name'].'-'.$loading_address['mobile'].'-'.$loading_address['address'].'-'.'-'.$loading_address['detailed_address'].'-';

                  }else{
                      $order_data['loading'] = '无';

                  }
                  if($unload_address){
                      $order_data['unload'] = $unload_address['user_name'].'-'.$unload_address['mobile'].'-'.$unload_address['address'].'-'.'-'.$unload_address['detailed_address'].'-';

                  }else{
                      $order_data['unload'] = '无';
                  }
               $order_data['goods_type_id'] = Db::name('goods_type')->where('id',$order_data['goods_type_id'])->value('name');
                $order_data['packaging_id'] = Db::name('packaging')->where('id',$order_data['packaging_id'])->value('name');
                $order_data['car_type_id'] = Db::name('car_type')->where('id',$order_data['car_type_id'])->value('name');
                $order_data['delivery_type_id'] = Db::name('delivery_type')->where('id',$order_data['delivery_type_id'])->value('name');
                $order_data['receipt_type_id'] = Db::name('receipt_type')->where('id',$order_data['receipt_type_id'])->value('name');
                $order_data['unpack_id'] = Db::name('unpack')->where('id',$order_data['unpack_id'])->value('name');
                $order_data['other_id'] = Db::name('other')->where('id',$order_data['other_id'])->value('name');
                $order_data['username'] = Db::name('user')->where('id',$order_data['userid'])->value('username');
                $order_data['mobile'] = Db::name('user')->where('id',$order_data['userid'])->value('mobile');

                // 前端直接展示中文状态，避免被后续 array_merge 覆盖回数字
                $statusValue = $v['status'];
                if ($admin_group['identity'] == 2) {
                    $statusValue = ($v['status'] == 0) ? '未完成' : '已完成';
                }
                if ($admin_group['identity'] == 3) {
//                    // 使用单个查询并直接在SQL中排序
                    $latestStatus = Db::name('dricerorder')
                        ->where('order_id', $order_data['orderid'])
                        ->order('type desc') // 按type倒序，type越大阶段越靠后
                        ->find();

                    if ($latestStatus) {
                        $statusMap = [
                            1 => ['等待取货', '取货中'],
                            2 => ['等待运输', '运输中'],
                            3 => ['等待送货', '送货中']
                        ];

                        $type = $latestStatus['type'];
                        $status = $latestStatus['status'];

                        $statusValue = $statusMap[$type][$status == 1 ? 1 : 0];
                    } else {
                        $statusValue = '未分配司机';
                    }
                }
                $order_data['status'] = $statusValue;
                $order_data['id'] =$v['id'];
                $order_data['admin_group'] = $admin_group_id;
                $order_data['admin_group_name'] = $admin_group_name ?: '';
                $order_data['admin_identity'] = $admin_group_identity;
                $order_data['is_dirver'] = Db::name('dricerorder')
                    ->where('order_id',$order_data['orderid'])
                    ->where('type',1)
                    ->find();
                $songdirver = Db::name('dricerorder')
                    ->where('order_id',$order_data['orderid'])
                    ->where('type',3)
                    ->find();

                $count = Db::name('admincomm')->where('order_id',$v['order_id'])->where('status',2)->where('admin_id','<>',$admin_id)->count();
                if ($count >0){
                    $order_data['isdu'] = 1;
                }else{
                    $order_data['isdu'] = 0;
                }
                if ( $order_data['is_dirver']){
                    $order_data['is_dirver'] = 1;
                }else{
                    $order_data['is_dirver'] = 0;
                }
                if ($songdirver){
                    $order_data['is_songdirver'] = 1;
                }else{
                    $order_data['is_songdirver'] = 0;
                }
                $order_data['order_id'] = Db::name('order')->where('id',$v['order_id'])->value('orderid');
                $timeout = (time()-$v['createtime']) /60;
                if ($timeout >=15){
                    $list[$k]['timeout'] = '超时';
                }else{
                    $list[$k]['timeout'] = '未超时';
                }
                if ($order_data) {
                    // 将 order_data 合并到当前的 list 项中
                    $list[$k] = array_merge((array)$v, (array)$order_data);
                }
            }

        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }


    /**
     * @return void
     *
     * 确认订单
     */
    public function carfim()
    {
        $data = $this->request->param();
        $adminInfo = $this->auth->getUserInfo();

        $admin_grouop_id = Db::name('auth_group_access')->where('uid',$adminInfo['id'])->value('group_id');
        $admin_grouop = Db::name('auth_group')->where('id',$admin_grouop_id)->find();
        $admin_grouop_name =$admin_grouop['name'];

        $res = Db::name('admin_order')->where('id',$data['ids'])->update(['status'=>1]);
        $order_id = Db::name('admin_order')->where('id',$data['ids'])->value('order_id');
        $orderInfo = Db::name('order')->where('id', $order_id)->find();
        if ($orderInfo['find_car_type'] == "配车"){
//            print_r($order_id);die;
            $ress = Db::name('order')->where('id',$order_id)->update(['backend_status'=>2]);
        }else{
            $payPrice = isset($data['pay_price']) ? round(floatval($data['pay_price']), 2) : null;
            if ($payPrice === null || $payPrice < 0) {
                $this->error('请填写有效的订单金额');
            }
            $ress = Db::name('order')->where('id',$order_id)->update(['backend_status'=>2,'pay_price'=>$payPrice]);
        }
        if ($ress){
            $trackType = '已下单';
            $mobile = $adminInfo['mobile'];
            $admin_grouop_name = '线路电话' . $mobile ;
            $arr = [
                'order_id' => $order_id,
                'admin_name' => $admin_grouop_name, 
                'admin_mobile' => $adminInfo['mobile'],
                'createtime' => time(),
                'type' => $trackType,
            ];
            Db::name('trajectory')->insert($arr);
//            Commission::record($adminInfo['id'], $order_id);
            $this->success('已确认');
        }else{
            $this->error('无需重复确认');
        }
    }
    /**
     * @return void
     *
     * 填写司机成本
     */
    public function carfimprice()
    {
        $data = $this->request->param();
        $adminInfo = $this->auth->getUserInfo();

        $admin_grouop_id = Db::name('auth_group_access')->where('uid',$adminInfo['id'])->value('group_id');
        $admin_grouop = Db::name('auth_group')->where('id',$admin_grouop_id)->find();
        $admin_grouop_name =$admin_grouop['name'];
        $payPrice = isset($data['pay_price']) ? round(floatval($data['pay_price']), 2) : null;
        if ($payPrice === null || $payPrice < 0) {
            $this->error('请填写有效的订单金额');
        }
        $order_id = Db::name('admin_order')->where('id',$data['ids'])->value('order_id');
        $orderInfo = Db::name('order')->where('id', $order_id)->find();
        $ress = Db::name('order')->where('id',$order_id)->update(['special_price'=>$payPrice]);
    }
    
    /**
     * 规划路线并重新计算金额
     */
    public function logistics()
    {
        $data = $this->request->param();

        // 获取admin_order记录，确保是当前用户抢的单

        $admin_order = Db::name('admin_order')->where('id', $data['ids'])->find();
        if (!$admin_order) {
            $this->error('订单不存在或无权操作');
        }
        
        $order_id = $admin_order['order_id'];
        
        // 获取订单信息
        $order = Db::name('order')->where('id', $order_id)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $driverorder = Db::name('dricerorder')
            ->where('order_id',$order['orderid'])
            ->where('type',2)
            ->where('status',2)
            ->find();
        if ($driverorder){
            $this->error('专线已完成，不能修改');
        }
        
        // 获取选中路线的信息
        $logistics = Db::name('logistics')->where('id', $data['selected_row'])->find();
        if (!$logistics) {
            $this->error('路线不存在');
        }
        // 调试：输出路线所有字段
        \think\Log::info('修改路线 - 路线ID: ' . $data['selected_row'] . ', 路线数据: ' . json_encode($logistics, JSON_UNESCAPED_UNICODE));
        
        // 根据Shipping_logistics_mobile判断并创建用户
        // 尝试多种可能的字段名
        $mobile = $logistics['shipping_logistics_mobile'];
        \think\Log::info('修改路线 - 路线ID: ' . $data['selected_row'] . ', 手机号: ' . $mobile);
        
        if (!empty($mobile)) {
            // 查找用户
            $user = Db::name('user')->where('mobile', $mobile)->find();

            if (!$user) {
                \think\Log::info('用户不存在，开始创建用户: ' . $mobile);
                try {
                    // 用户不存在，创建新用户
                    $defaultPassword = \fast\Random::alnum(8);
                    $salt = \fast\Random::alnum();
                    $encryptedPassword = \app\common\library\Auth::instance()->getEncryptPassword($defaultPassword, $salt);
                    
                    $username = $mobile;
                    $nickname = preg_match("/^1[3-9]{1}\d{9}$/", $mobile) ? substr_replace($mobile, '****', 3, 4) : $mobile;
                    
                    $ip = request()->ip();
                    $time = time();
                    
                    $userData = [
                        'username' => $username,
                        'nickname' => $nickname,
                           'identity' => 3,
                        'password' => $encryptedPassword,
                        'salt' => $salt,
                        'mobile' => $mobile,
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
                    
                    $userId = Db::name('user')->insertGetId($userData);
                    if ($userId) {
                        \think\Log::info('创建用户成功: ' . $mobile . ', 用户ID: ' . $userId);
                    } else {
                        \think\Log::error('创建用户失败: ' . $mobile . ', SQL错误: ' . Db::getLastSql());
                    }
                } catch (\Exception $e) {
                    \think\Log::error('创建用户异常: ' . $mobile . ', 错误: ' . $e->getMessage());
                }
            } else {
                \think\Log::info('用户已存在: ' . $mobile . ', 用户ID: ' . $user['id']);
            }
        } else {
            \think\Log::warning('路线手机号为空，无法创建用户，路线ID: ' . $data['selected_row']);
        }
        
        // 更新物流路线ID
        $res = Db::name('order')->where('id', $order_id)->update(['logistics_id' => $data['selected_row']]);
          Db::name('dricerorder')->where('order_id', $order_id)->update(['d_id' =>$userId,'driver_name'=>$nickname,'driver_mobile'=>$mobile]);
        if ($res) {
            // 重新计算金额
            $costResult = $this->recalculateLogisticsCost($order_id, $data['selected_row']);
            if ($costResult) {
                $this->success('路线规划成功，金额已重新计算');
            } else {
                $this->success('路线规划成功，但金额计算失败，请手动填写');
            }
        } else {
            $this->error('路线规划失败');
        }
    }
    
    /**
     * 重新计算物流费用
     * @param int $orderId 订单ID
     * @param int $logisticsId 物流路线ID
     * @return bool
     */
    private function recalculateLogisticsCost($orderId, $logisticsId)
    {
        try {
            // 获取订单信息
            $order = Db::name('order')->where('id', $orderId)->find();
            if (!$order) {
                return false;
            }
            
            // 获取装货和卸货地址
            $loading = Db::name('user_address')->where('id', $order['loading'])->find();
            $unload = Db::name('user_address')->where('id', $order['unload'])->find();
            if (!$loading || !$unload) {
                return false;
            }
            
            // 获取车型信息
            $carType = Db::name('car_type')->where('id', $order['car_type_id'])->find();
            if (!$carType) {
                return false;
            }
            
            // 获取物流路线信息
            $logistics = Db::name('logistics')->where('id', $logisticsId)->find();
            if (!$logistics) {
                return false;
            }
            
            // 计算三段距离
            $distances = $this->calculateSegmentDistances($loading, $unload, $logistics);
            
            // 计算物流费用
            $costResult = $this->calculateLogisticsCost($order, $logistics, $carType, $distances);
            
            // 更新订单金额
            $updateData = [
                'logistics_cost' => $costResult['logistics_cost'],
                'logistics_driver_cost' => $costResult['logistics_driver_cost'],
                'pickup_driver_fee' => $costResult['pickup_driver_fee'],
                'shipment_driver_fee' => $costResult['shipment_driver_fee'],
                'pickup_fee' => $costResult['pickup_fee'],
                'shipment_fee' => $costResult['shipment_fee']
            ];
            
            $result = Db::name('order')->where('id', $orderId)->update($updateData);
            return $result !== false;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 计算三段距离
     */
    private function calculateSegmentDistances($loading, $unload, $logistics)
    {
        // 装货地址到物流起点距离
        $segment1 = 0;
        if (!empty($loading['lat']) && !empty($loading['lng']) && 
            !empty($logistics['shipping_latitude']) && !empty($logistics['shipping_longitude'])) {
            $segment1 = calculateDrivingDistance(
                $loading['lat'], $loading['lng'],
                $logistics['shipping_latitude'], $logistics['shipping_longitude']
            );
        }
        
        // 物流专线距离
        $segment2 = $logistics['distance'] ?? 0;
        
        // 物流终点到卸货地址距离
        $segment3 = 0;
        if (!empty($unload['lat']) && !empty($unload['lng']) && 
            !empty($logistics['arrival_latitude']) && !empty($logistics['arrival_longitude'])) {
            $segment3 = calculateDrivingDistance(
                $unload['lat'], $unload['lng'],
                $logistics['arrival_latitude'], $logistics['arrival_longitude']
            );
        }
        
        return [
            'loading_to_start' => $segment1,
            'logistics_line' => $segment2,
            'end_to_unload' => $segment3
        ];
    }
    
    /**
     * 计算物流费用
     */
    private function calculateLogisticsCost($order, $logistics, $carType, $distances)
    {
        // direction字段存储的是体积（立方米），weight是重量（吨）
        $weight = $order['weight'] ?? 0;
        $volume = $order['direction'] ?? 0; // direction是总方位（体积，单位：立方米）
        
        // 使用calculatePrice函数计算价格
        $priceResult = calculatePrice(
            $weight,
            $volume,
            $logistics['perton'] ?? 0,
            $logistics['side'] ?? 0
        );
        
        // 获取配置
        $PickUpDriverFreight = Config::get('site.PickUpDriverFreight') ?: 0;
        $LogisticsDriverFreight = Config::get('site.LogisticsDriverFreight') ?: 0;
        $DeliveryDriverFreight = Config::get('site.DeliveryDriverFreight') ?: 0;
        
        // 计算司机费用（保持与原代码一致）
        $pickupDriverFee = $distances['end_to_unload'] * ($carType['price'] ?? 0);
        $shipmentDriverFee = $distances['end_to_unload'] * ($carType['price'] ?? 0);
        
        return [
            'logistics_cost' => round($priceResult['price'] * (1 + $LogisticsDriverFreight / 100), 2),
            'logistics_driver_cost' => round($priceResult['price'], 2),
            'pickup_driver_fee' => round($pickupDriverFee, 2),
            'shipment_driver_fee' => round($shipmentDriverFee, 2),
            'pickup_fee' => round($pickupDriverFee * (1 + $PickUpDriverFreight / 100), 2),
            'shipment_fee' => round($shipmentDriverFee * (1 + $DeliveryDriverFreight / 100), 2)
        ];
    }

    /**
     * 退款（微信原路返回）
     * @return void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function refund()
    {
        $id = $this->request->param('ids');
        if (empty($id)) {
            $this->error('订单ID不能为空');
        }

        // 查询admin_order记录
        $adminOrder = Db::name('admin_order')->where('id', $id)->find();
        if (!$adminOrder) {
            $this->error('订单记录不存在');
        }

        // 查询订单信息
        $order = Db::name('order')->where('id', $adminOrder['order_id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        // 检查订单状态，只有已支付（进行中或已完成）的订单才能退款
        if ($order['pay_status'] == 1) {
            $this->error('订单尚未支付，无需退款');
        }
        if ($order['pay_status'] == 4) {
            $this->error('订单已取消，无法退款');
        }

        // 检查是否已支付（需要检查 pay_time 或 pay_type）
        if (empty($order['pay_time']) && empty($order['pay_type'])) {
            $this->error('订单未支付，无法退款');
        }

        $refundAmount = floatval($order['pay_price']);
        if ($refundAmount <= 0) {
            $this->error('订单金额为0，无需退款');
        }

        // 生成退款单号
        $refundNo = 'RF' . date('YmdHis') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // 开始事务
        Db::startTrans();
        try {
            // 调用微信退款接口
            $refundParams = [
                'out_trade_no' => $order['orderid'], // 商户订单号
                'out_refund_no' => $refundNo, // 退款单号
                'total_fee' => intval($refundAmount * 100), // 订单总金额（分）
                'refund_fee' => intval($refundAmount * 100), // 退款金额（分）
                'refund_desc' => '订单退款：' . $order['orderid'], // 退款原因
            ];

            // 调用 epay 退款接口
            $refundResult = \addons\epay\library\Service::refund($refundParams, 'wechat');
            
            if (!$refundResult || (isset($refundResult['return_code']) && $refundResult['return_code'] != 'SUCCESS')) {
                $errorMsg = isset($refundResult['return_msg']) ? $refundResult['return_msg'] : '退款接口调用失败';
                throw new \Exception($errorMsg);
            }

            // 检查退款结果
            if (isset($refundResult['result_code']) && $refundResult['result_code'] != 'SUCCESS') {
                $errorMsg = isset($refundResult['err_code_des']) ? $refundResult['err_code_des'] : '退款失败';
                throw new \Exception($errorMsg);
            }

            // 更新订单状态为已取消
            $updateData = [
                'pay_status' => 4, // 已取消
                'refund_time' => time(), // 退款时间
                'refund_no' => $refundNo, // 退款单号
            ];
            Db::name('order')->where('id', $adminOrder['order_id'])->update($updateData);

            // 记录退款账单
            Db::name('bill')->insert([
                'uid' => $order['userid'],
                'turnover' => 0, // 0=收入（退款）
                'order_name' => '订单退款',
                'order_price' => $refundAmount,
                'createtime' => time()
            ]);

            Db::commit();
            $this->success('退款成功，金额将通过微信原路返回');
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('退款失败：' . $e->getMessage());
        }
    }

    /**
     * 获取订单详细信息（用于取消专线订单弹窗）
     * @return Json
     */
    public function get_order_info()
    {
        $id = $this->request->param('ids');
        // 处理数组情况
        if (is_array($id)) {
            $id = isset($id[0]) ? $id[0] : '';
        }
        $id = intval($id);
        if (empty($id)) {
            $this->error('订单ID不能为空');
        }

        // 查询admin_order记录
        $adminOrder = Db::name('admin_order')->where('id', $id)->find();
        if (!$adminOrder) {
            $this->error('订单记录不存在');
        }

        // 查询订单信息
        $order = Db::name('order')->where('id', $adminOrder['order_id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $result = [
            'orderid' => $order['orderid'] ?? '',
        ];

        // 获取专线信息
        if (!empty($order['logistics_id'])) {
            $logistics = Db::name('logistics')->where('id', $order['logistics_id'])->find();
            if ($logistics) {
                $result['logistics_name'] = $logistics['shipping_logistics_name'] ?? '未分配';
                $result['logistics_start_phone'] = $logistics['shipping_logistics_mobile'] ?? '无';
                $result['logistics_end_phone'] = $logistics['arrival_logistics_mobile'] ?? '无';
            } else {
                $result['logistics_name'] = '未分配';
                $result['logistics_start_phone'] = '无';
                $result['logistics_end_phone'] = '无';
            }
        } else {
            $result['logistics_name'] = '未分配';
            $result['logistics_start_phone'] = '无';
            $result['logistics_end_phone'] = '无';
        }

        // 获取取货司机信息（type=1）
        $pickupDriver = Db::name('dricerorder')
            ->where('order_id', $order['orderid'])
            ->where('type', 1)
            ->find();
        if ($pickupDriver) {
            $result['pickup_driver_name'] = $pickupDriver['driver_name'] ?? '未分配';
            $result['pickup_driver_phone'] = $pickupDriver['driver_mobile'] ?? '无';
        } else {
            $result['pickup_driver_name'] = '未分配';
            $result['pickup_driver_phone'] = '无';
        }

        // 获取送货司机信息（type=3）
        $shipmentDriver = Db::name('dricerorder')
            ->where('order_id', $order['orderid'])
            ->where('type', 3)
            ->find();
        if ($shipmentDriver) {
            $result['shipment_driver_name'] = $shipmentDriver['driver_name'] ?? '未分配';
            $result['shipment_driver_phone'] = $shipmentDriver['driver_mobile'] ?? '无';
        } else {
            $result['shipment_driver_name'] = '未分配';
            $result['shipment_driver_phone'] = '无';
        }
//      print_r($result);die;
        $this->success('获取成功', '', $result);
    }

    /**
     * 取消专线订单
     * @return Json
     */
    public function cancel_logistics_order()
    {
        $id = $this->request->param('ids');
        // 处理数组情况
        if (is_array($id)) {
            $id = isset($id[0]) ? $id[0] : '';
        }
        $id = intval($id);
        if (empty($id)) {
            $this->error('订单ID不能为空');
        }

        // 查询admin_order记录
        $adminOrder = Db::name('admin_order')->where('id', $id)->find();
        if (!$adminOrder) {
            $this->error('订单记录不存在');
        }

        // 查询订单信息
        $order = Db::name('order')->where('id', $adminOrder['order_id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $dricerorder= Db::name('dricerorder')
            ->where('order_id', $order['orderid'])
            ->where('type', 2)
            ->where('status',2)
            ->find();
        if ($dricerorder){
            $this->error('改专线已经完成，不能取消');
        }
            // 清除订单的专线ID
//            Db::name('order')->where('id', $adminOrder['order_id'])->update(['logistics_id' => null]);
            // 删除对应的司机订单记录
            $result = Db::name('dricerorder')
                ->where('order_id', $order['orderid'])
                ->where('type', 2)
                ->update(['d_id'=>'','driver_name'=>'','driver_mobile'=>'']);
           if ($result){
               $this->success('取消专线订单成功');

           }else{
               $this->error('取消专线订单失败');

           }



    }

    /**
     * 取消司机订单
     * @return Json
     */
    public function cancel_driver_order()
    {
        $id = $this->request->param('ids');
        $driverType = $this->request->param('driver_type'); // 1=取货司机, 3=送货司机
        
        // 处理数组情况
        if (is_array($id)) {
            $id = isset($id[0]) ? $id[0] : '';
        }
        $id = intval($id);
        if (empty($id)) {
            $this->error('订单ID不能为空');
        }
        $driverType = intval($driverType);
        if (empty($driverType) || !in_array($driverType, [1, 3])) {
            $this->error('司机类型参数错误');
        }

        // 查询admin_order记录
        $adminOrder = Db::name('admin_order')->where('id', $id)->find();
        if (!$adminOrder) {
            $this->error('订单记录不存在');
        }

        // 查询订单信息
        $order = Db::name('order')->where('id', $adminOrder['order_id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }


            // 删除对应的司机订单记录
            $result = Db::name('dricerorder')
                ->where('order_id', $order['orderid'])
                ->where('type', $driverType)
                ->delete();

            if ($result) {
                Db::commit();
                $driverTypeName = $driverType == 1 ? '取货司机' : '送货司机';
                $this->success('取消' . $driverTypeName . '订单成功');
            } else {
                Db::rollback();
                $driverTypeName = $driverType == 1 ? '取货司机' : '送货司机';
                $this->error('取消' . $driverTypeName . '订单失败，可能该司机订单不存在');
            }

    }

    /**
     * 获取订单电话信息
     * @return Json
     */
    public function get_order_phones()
    {
        $id = $this->request->param('ids');
        // 处理数组情况
        if (is_array($id)) {
            $id = isset($id[0]) ? $id[0] : '';
        }
        $id = intval($id);
        if (empty($id)) {
            $this->error('订单ID不能为空');
        }

        // 查询admin_order记录
        $adminOrder = Db::name('admin_order')->where('id', $id)->find();
        if (!$adminOrder) {
            $this->error('订单记录不存在');
        }

        // 查询订单信息
        $order = Db::name('order')->where('id', $adminOrder['order_id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $result = [
            'orderid' => $order['orderid'] ?? '',
        ];

        // 获取取货司机电话（type=1）
        $pickupDriver = Db::name('dricerorder')
            ->where('order_id', $order['orderid'])
            ->where('type', 1)
            ->find();
        if ($pickupDriver) {
            $result['pickup_driver_phone'] = $pickupDriver['driver_mobile'] ?? '未分配';
        } else {
            $result['pickup_driver_phone'] = '未分配';
        }

        // 获取专线起点电话和终点电话
        if (!empty($order['logistics_id'])) {
            $logistics = Db::name('logistics')->where('id', $order['logistics_id'])->find();
            if ($logistics) {
                $result['logistics_start_phone'] = $logistics['shipping_logistics_mobile'] ?? '未分配';
                $result['logistics_end_phone'] = $logistics['arrival_logistics_mobile'] ?? '未分配';
            } else {
                $result['logistics_start_phone'] = '未分配';
                $result['logistics_end_phone'] = '未分配';
            }
        } else {
            $result['logistics_start_phone'] = '未分配';
            $result['logistics_end_phone'] = '未分配';
        }

        // 获取送货司机电话（type=3）
        $shipmentDriver = Db::name('dricerorder')
            ->where('order_id', $order['orderid'])
            ->where('type', 3)
            ->find();
        if ($shipmentDriver) {
            $result['shipment_driver_phone'] = $shipmentDriver['driver_mobile'] ?? '未分配';
        } else {
            $result['shipment_driver_phone'] = '未分配';
        }

        $this->success('获取成功', '', $result);
    }
}