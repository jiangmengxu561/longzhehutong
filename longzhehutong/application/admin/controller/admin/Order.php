<?php

namespace app\admin\controller\admin;

use app\admin\library\AdminUserBind;
use app\admin\library\OrderModifyApplier;
use app\admin\library\traits\OrderRejectBlockTrait;
use app\common\controller\Backend;
use app\common\library\OrderModifyLog;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
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
    use OrderRejectBlockTrait;

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
     * 抢单列表主表为 admin_order，表格里绝大部分筛选项对应 order / user / user_address / payment_method 等。
     * 在此统一拆出条件：换算为 order 主键 id 集合（whereIn order_id）、或 admin_order 上可执行的 where（如抢单超时）。
     * 最后仅把 admin_order 真实存在的列留给 buildparams，避免无效字段进 SQL。
     *
     * @return array{0: int[]|null, 1: array, 2: array, 3: array} [orderIds, filterArr, opArr, adminOrderWheres]
     */
    protected function prepareGrabbedOrderListFilters()
    {
        $filterArr = (array) json_decode($this->request->get('filter', ''), true);
        $opArr = (array) json_decode($this->request->get('op', '', 'trim'), true);
        $idSets = [];
        $adminOrderWheres = [];

        // 下单人：username 或 FastAdmin 组合的 username|mobile
        $unameVal = null;
        foreach (['username', 'username|mobile'] as $uk) {
            if (array_key_exists($uk, $filterArr) && trim((string) $filterArr[$uk]) !== '') {
                $unameVal = trim((string) $filterArr[$uk]);
                unset($filterArr['username'], $opArr['username'], $filterArr['username|mobile'], $opArr['username|mobile']);
                break;
            }
        }
        if ($unameVal !== null) {
            $userIds = Db::name('user')->where('username|mobile', 'LIKE', "%{$unameVal}%")->column('id');
            $idSets[] = $userIds === [] ? [] : Db::name('order')->whereIn('userid', $userIds)->column('id');
        }

        if (array_key_exists('mobile', $filterArr) && trim((string) $filterArr['mobile']) !== '') {
            $kw = trim((string) $filterArr['mobile']);
            $sym = strtoupper((string) ($opArr['mobile'] ?? 'LIKE'));
            $likeOp = (strpos($sym, 'NOT') !== false) ? 'NOT LIKE' : 'LIKE';
            $userIds = Db::name('user')->where('mobile', $likeOp, "%{$kw}%")->column('id');
            $idSets[] = $userIds === [] ? [] : Db::name('order')->whereIn('userid', $userIds)->column('id');
            unset($filterArr['mobile'], $opArr['mobile']);
        }

        if (array_key_exists('orderid', $filterArr) && $filterArr['orderid'] !== '' && $filterArr['orderid'] !== null) {
            $v = trim((string) $filterArr['orderid']);
            $sym = strtoupper((string) ($opArr['orderid'] ?? 'LIKE'));
            $q = Db::name('order');
            if (in_array($sym, ['LIKE', 'NOT LIKE', 'LIKE %...%', 'NOT LIKE %...%'], true)) {
                $symClean = trim(str_replace('%...%', '', $sym));
                $q->where('orderid', $symClean, "%{$v}%");
            } else {
                $q->where('orderid', '=', $v);
            }
            $idSets[] = $q->column('id');
            unset($filterArr['orderid'], $opArr['orderid']);
        }

        if (array_key_exists('find_car_type', $filterArr) && trim((string) $filterArr['find_car_type']) !== '') {
            $v = trim((string) $filterArr['find_car_type']);
            $sym = strtoupper(str_replace('(...)', '', (string) ($opArr['find_car_type'] ?? '=')));
            if ($sym === 'IN') {
                $list = is_array($filterArr['find_car_type']) ? $filterArr['find_car_type'] : explode(',', $v);
                $list = array_values(array_filter(array_map('trim', $list)));
                $idSets[] = $list === [] ? [] : Db::name('order')->whereIn('find_car_type', $list)->column('id');
            } else {
                $q = Db::name('order');
                if (in_array($sym, ['LIKE', 'LIKE %...%', 'NOT LIKE', 'NOT LIKE %...%'], true)) {
                    $symClean = trim(str_replace('%...%', '', $sym));
                    $q->where('find_car_type', $symClean, "%{$v}%");
                } else {
                    $q->where('find_car_type', '=', $v);
                }
                $idSets[] = $q->column('id');
            }
            unset($filterArr['find_car_type'], $opArr['find_car_type']);
        }

        foreach (['pay_type', 'pay_status', 'is_pay_salary', 'logistics_status'] as $f) {
            if (!array_key_exists($f, $filterArr)) {
                continue;
            }
            $fv = $filterArr[$f];
            if ($fv === '' || $fv === null) {
                continue;
            }
            $sym = strtoupper(str_replace('(...)', '', (string) ($opArr[$f] ?? '=')));
            $q = Db::name('order');
            if ($sym === 'IN' || $sym === 'NOT IN') {
                $list = is_array($fv) ? $fv : explode(',', (string) $fv);
                $list = array_values(array_filter(array_map('trim', $list), function ($x) {
                    return $x !== '' && $x !== null;
                }));
                if ($list === []) {
                    unset($filterArr[$f], $opArr[$f]);
                    continue;
                }
                if ($sym === 'IN') {
                    $q->whereIn($f, $list);
                } else {
                    $q->whereNotIn($f, $list);
                }
            } elseif (in_array($sym, ['LIKE', 'NOT LIKE', 'LIKE %...%', 'NOT LIKE %...%'], true)) {
                $symClean = trim(str_replace('%...%', '', $sym));
                $q->where($f, $symClean, '%' . trim((string) $fv) . '%');
            } else {
                $q->where($f, '=', $fv);
            }
            $idSets[] = $q->column('id');
            unset($filterArr[$f], $opArr[$f]);
        }

        if (array_key_exists('createtime', $filterArr) && $filterArr['createtime'] !== '' && $filterArr['createtime'] !== null) {
            $v = str_replace(' - ', ',', (string) $filterArr['createtime']);
            $arr = array_slice(explode(',', $v), 0, 2);
            if (array_filter($arr, function ($x) {
                return $x !== '' && $x !== false && $x !== null;
            })) {
                $q = Db::name('order');
                $applied = false;
                if ($arr[0] === '' || $arr[0] === null) {
                    $t1 = strtotime($arr[1]);
                    if ($t1 !== false) {
                        $q->where('createtime', '<=', $t1);
                        $applied = true;
                    }
                } elseif ($arr[1] === '' || $arr[1] === null) {
                    $t0 = strtotime($arr[0]);
                    if ($t0 !== false) {
                        $q->where('createtime', '>=', $t0);
                        $applied = true;
                    }
                } else {
                    $t0 = strtotime($arr[0]);
                    $t1 = strtotime($arr[1]);
                    if ($t0 !== false && $t1 !== false) {
                        $q->whereBetween('createtime', [$t0, $t1]);
                        $applied = true;
                    }
                }
                if ($applied) {
                    $idSets[] = $q->column('id');
                }
            }
            unset($filterArr['createtime'], $opArr['createtime']);
        }

        if (array_key_exists('address_contact', $filterArr) && trim((string) $filterArr['address_contact']) !== '') {
            $keyword = trim((string) $filterArr['address_contact']);
            $addrIds = Db::name('user_address')
                ->where(function ($q) use ($keyword) {
                    $q->where('user_name', 'LIKE', "%{$keyword}%")
                        ->whereOr('company_name', 'LIKE', "%{$keyword}%");
                })
                ->column('id');
            if ($addrIds === []) {
                $idSets[] = [];
            } else {
                $idSets[] = Db::name('order')
                    ->where(function ($q) use ($addrIds) {
                        $q->whereIn('loading', $addrIds)->whereOr('unload', 'in', $addrIds);
                    })
                    ->column('id');
            }
            unset($filterArr['address_contact'], $opArr['address_contact']);
        }

        if (array_key_exists('order_address', $filterArr) && trim((string) $filterArr['order_address']) !== '') {
            $keyword = trim((string) $filterArr['order_address']);
            $addrIds = Db::name('user_address')
                ->where(function ($q) use ($keyword) {
                    $q->where('detailed_address', 'LIKE', "%{$keyword}%")
                        ->whereOr('address', 'LIKE', "%{$keyword}%")
                        ->whereOr('user_name', 'LIKE', "%{$keyword}%")
                        ->whereOr('mobile', 'LIKE', "%{$keyword}%")
                        ->whereOr('company_name', 'LIKE', "%{$keyword}%");
                })
                ->column('id');
            if ($addrIds === []) {
                $idSets[] = [];
            } else {
                $idSets[] = Db::name('order')
                    ->where(function ($q) use ($addrIds) {
                        $q->whereIn('loading', $addrIds)->whereOr('unload', 'in', $addrIds);
                    })
                    ->column('id');
            }
            unset($filterArr['order_address'], $opArr['order_address']);
        }

        if (array_key_exists('payment_method', $filterArr) && trim((string) $filterArr['payment_method']) !== '') {
            $kw = trim((string) $filterArr['payment_method']);
            $pmIds = Db::name('payment_method')->where('payment_method', 'LIKE', "%{$kw}%")->column('id');
            $idSets[] = $pmIds === [] ? [] : Db::name('order')->whereIn('payment_method_id', $pmIds)->column('id');
            unset($filterArr['payment_method'], $opArr['payment_method']);
        }

        if (array_key_exists('information_deposit', $filterArr) && trim((string) $filterArr['information_deposit']) !== '') {
            $kw = trim((string) $filterArr['information_deposit']);
            $idSets[] = Db::name('order')
                ->where(function ($q) use ($kw) {
                    $q->where('information', 'LIKE', "%{$kw}%")->whereOr('deposit', 'LIKE', "%{$kw}%");
                })
                ->column('id');
            unset($filterArr['information_deposit'], $opArr['information_deposit']);
        }

        if (array_key_exists('information_image', $filterArr) && trim((string) $filterArr['information_image']) !== '') {
            $kw = trim((string) $filterArr['information_image']);
            $idSets[] = Db::name('order')->where('information_image', 'LIKE', "%{$kw}%")->column('id');
            unset($filterArr['information_image'], $opArr['information_image']);
        }

        if (array_key_exists('profit', $filterArr) && trim((string) $filterArr['profit']) !== '') {
            $raw = trim((string) $filterArr['profit']);
            $rawNum = str_replace(['¥', ',', ' ', '元'], '', $raw);
            $sym = strtoupper((string) ($opArr['profit'] ?? '='));
            $q = Db::name('order')->where('pay_price', '>', 0)->where('cost_cont', '>', 0);
            if (in_array($sym, ['LIKE', 'LIKE %...%', 'NOT LIKE', 'NOT LIKE %...%'], true)) {
                $q->whereRaw('CAST(ROUND(pay_price - cost_cont, 2) AS CHAR) LIKE ?', ['%' . $raw . '%']);
            } elseif (is_numeric($rawNum)) {
                $q->whereRaw('ROUND(pay_price - cost_cont, 2) = ?', [round((float) $rawNum, 2)]);
            } else {
                $q->whereRaw('CAST(ROUND(pay_price - cost_cont, 2) AS CHAR) LIKE ?', ['%' . $raw . '%']);
            }
            $idSets[] = $q->column('id');
            unset($filterArr['profit'], $opArr['profit']);
        }

        // 列表「超时」依据 admin_order.createtime（抢单时间），与订单 createtime 不同
        if (array_key_exists('timeout', $filterArr) && $filterArr['timeout'] !== '' && $filterArr['timeout'] !== null) {
            $t = trim((string) $filterArr['timeout']);
            unset($filterArr['timeout'], $opArr['timeout']);
            $deadline = time() - 15 * 60;
            if (strpos($t, '未超时') !== false || $t === '0') {
                $adminOrderWheres[] = ['createtime', '>', $deadline];
            } elseif (strpos($t, '超时') !== false || $t === '1') {
                $adminOrderWheres[] = ['createtime', '<=', $deadline];
            }
        }

        // 仅允许进入 buildparams 的 admin_order 列（其它一律剥离，避免 SQL 未知列）
        $adminColumnWhitelist = ['id', 'order_id', 'status', 'group_id'];
        foreach (array_keys($filterArr) as $fk) {
            if (!in_array($fk, $adminColumnWhitelist, true)) {
                unset($filterArr[$fk], $opArr[$fk]);
            }
        }

        if ($idSets === []) {
            return [null, $filterArr, $opArr, $adminOrderWheres];
        }
        $merged = array_values(array_unique($idSets[0]));
        for ($i = 1, $n = count($idSets); $i < $n; $i++) {
            $merged = array_values(array_intersect($merged, $idSets[$i]));
        }

        return [$merged, $filterArr, $opArr, $adminOrderWheres];
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
            $admin_group = AdminUserBind::getPrimaryBusinessGroupIdForAdmin($admin_id);
            $groupInfo = Db::name('auth_group')->where('id', $admin_group)->find();
            $group_identity = AdminUserBind::resolveEffectiveOrderRoleIdentity((int)$admin_group);
            // 仅路线/规划身份（identity=2）视为“销售”角色，用于前端展示控制
            $isSales = ($group_identity === 2);
            $this->view->assign('isSales', $isSales ? 1 : 0);
            $this->view->assign('groupIdentity', $group_identity);
            $this->assignconfig('admin', ['id' => $admin_id, 'group_id' => $admin_group, 'identity' => $group_identity]);
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        $admin_id = $this->auth->id;
        $admin_group_id = AdminUserBind::getPrimaryBusinessGroupIdForAdmin($admin_id);
        $admin_group = Db::name('auth_group')->where('id', $admin_group_id)->find();
        $admin_group_name = $admin_group['name'] ?? '';
        $admin_group_identity = AdminUserBind::resolveEffectiveOrderRoleIdentity((int)$admin_group_id);

        [$foreignOrderIds, $filterArr, $opArr, $adminOrderWheres] = $this->prepareGrabbedOrderListFilters();
        $this->request->get(['filter' => json_encode($filterArr), 'op' => json_encode($opArr)]);

        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $listQuery = $this->model->where($where);
        // 非代理/线路/调度（如总后台组 identity 未设 1/2/3）看全部抢单；代理看下属线路/调度；线路/调度只看自己
        if (!AdminUserBind::orderListUsesBindRegionalScope($admin_group_identity)) {
            // 不追加 admin_id 条件
        } elseif ($admin_group_identity === AdminUserBind::AUTH_GROUP_IDENTITY_AGENT) {
            $staffAdminIds = AdminUserBind::getLineDispatchStaffAdminIdsUnderAgent((int)$admin_group_id);
            if ($staffAdminIds === []) {
                $listQuery->where('admin_id', -1);
            } else {
                $listQuery->whereIn('admin_id', $staffAdminIds);
            }
        } else {
            $listQuery->where('admin_id', $admin_id);
        }
        foreach ($adminOrderWheres as $w) {
            if (count($w) >= 3) {
                $listQuery->where($w[0], $w[1], $w[2]);
            }
        }
        if ($foreignOrderIds !== null) {
            if ($foreignOrderIds === []) {
                $listQuery->where('order_id', -1);
            } else {
                $listQuery->whereIn('order_id', $foreignOrderIds);
            }
        }
        // 与下方组装 $rows 时 order 表条件一致，避免分页 total 含已排除订单（如 pay_status=4）而实际行数变少
        $listQuery->whereIn('order_id', function ($query) {
            $query->name('order')->where('pay_status', '<>', 4)->field('id');
        });
        $paginator = $listQuery->order($sort, $order)->paginate($limit);
        $rows = [];
        foreach ($paginator->items() as $item) {
            $v = is_array($item) ? $item : (is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : (array) $item);
                $order_data = Db::name('order')
                    ->where('pay_status','<>',4)
                    ->where('id',$v['order_id'])
                    ->find();
                if (empty($order_data)){
                    continue;
                }
                    $loading_address = Db::name('user_address')->where('id',$order_data['loading'])->find();
//                print_r($loading_address);die;
                    $unload_address = Db::name('user_address')->where('id',$order_data['unload'])->find();// 处理装货地址

                if ($loading_address) {
                    $order_data['loading'] = sprintf(
                        '%s-%s-%s-%s-',
                        $loading_address['user_name'] ?? '',
                        $loading_address['mobile'] ?? '',
                        $loading_address['detailed_address'] ?? '',
                        $loading_address['address'] ?? ''
                    );
                } else {
                    $order_data['loading'] = '---';
                }
//                print_r(extractProvinceCityEnhanced($loading_address['detailed_address']));die;
                if (empty($loading_address['detailed_address'])){
                    $loading_address['detailed_address'] = $loading_address['address'] ?? '';
                }
                if (empty($unload_address['detailed_address'])){
                    $unload_address['detailed_address'] = $unload_address['address'] ?? '';
                }
                $order_data['order_address'] = extractProvinceCity($loading_address['detailed_address']).'---'.extractProvinceCity($unload_address['detailed_address']);
// 处理卸货地址
                if ($unload_address) {
                    $order_data['unload'] = sprintf(
                        '%s-%s-%s-%s-',
                        $unload_address['user_name'] ?? '',
                        $unload_address['mobile'] ?? '',
                        $unload_address['detailed_address'] ?? '',
                        $unload_address['address'] ?? ''
                    );
                } else {
                    $order_data['unload'] = '---';
                }
                $payment_method = Db::name('payment_method')->where('id',$order_data['payment_method_id'])->find();
                if ($payment_method&& $payment_method['p_id'] >0 ) {
                    $p_name = Db::name('payment_method')->where('id',$payment_method['p_id'])->value('payment_method');
                    $order_data['payment_method'] = $p_name .'---'.$payment_method['payment_method'];
                }else{
                    if ($payment_method){
                        $order_data['payment_method'] = $payment_method['payment_method'];
                    }
                }
                $order_data['goods_type_id'] = Db::name('goods_type')->where('id',$order_data['goods_type_id'])->value('name');
                $order_data['packaging_id'] = Db::name('packaging_num')->where('order_id',$order_data['orderid'])->select();
                $order_data['car_type_id'] = Db::name('car_type')->where('id',$order_data['car_type_id'])->value('name');
                $order_data['delivery_type_id'] = Db::name('delivery_type')->where('id',$order_data['delivery_type_id'])->value('name');
                 $delivery_receipt = Db::name('delivery_receipt')->where('order_id', $order_data['orderid'])->find();
//                print_r($delivery_receipt);die;
                if ($delivery_receipt) {
                    $order_data['receipt_type_id_value'] = 3;
                }else{
                    $order_data['receipt_type_id_value'] = $order_data['receipt_type_id'];
                }
                $order_data['receipt_type_id'] = Db::name('receipt_type')->where('id',$order_data['receipt_type_id'])->value('name');
                $order_data['unpack_id'] = Db::name('unpack')->where('id',$order_data['unpack_id'])->value('name');
                $order_data['other_id'] = Db::name('other')->where('id',$order_data['other_id'])->value('name');
                $order_data['username'] = Db::name('user')->where('id',$order_data['userid'])->value('username');
                $order_data['mobile'] = Db::name('user')->where('id',$order_data['userid'])->value('mobile');
                $order_data['pickup_tax_point'] = Db::name('dricerorder')->where('order_id',$order_data['orderid'])->where('type',1)->value('tax_point');
                $order_data['shipment_tax_point'] = Db::name('dricerorder')->where('order_id',$order_data['orderid'])->where('type',3)->value('tax_point');



                // 保留原始的status数字值用于前端判断，使用status_text存储转换后的文本用于显示
                $statusValue = $v['status'];
                $statusText = '';
                if ($admin_group['identity'] == 2) {
                    $statusText = ($v['status'] == 0) ? '未完成' : '已完成';
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

                        $statusText = $statusMap[$type][$status == 1 ? 1 : 0];
                    } else {
                        $statusText = '未分配司机';
                    }
                }
                // 保留原始的status数字值（从$v['status']获取），用于前端按钮显示判断
                $order_data['status'] = $v['status'];
                // 添加status_text字段用于前端显示
                if ($statusText) {
                    $order_data['status_text'] = $statusText;
                }
                $order_data['id'] =$v['id'];
                $order_data['admin_group'] = $admin_group_id;
                $order_data['admin_group_name'] = $admin_group_name ?: '';
                $order_data['admin_identity'] = $admin_group_identity;
                $order_data['is_dirver'] = Db::name('dricerorder')
                    ->where('order_id', $order_data['orderid'])
                    ->where('type', 1)
                    ->find();
                $songdirver = Db::name('dricerorder')
                    ->where('order_id', $order_data['orderid'])
                    ->where('type', 3)
                    ->find();
                // 专线司机订单（type=2），用于前端判断专线是否已完成
                $lineOrder = Db::name('dricerorder')
                    ->where('order_id', $order_data['orderid'])
                    ->where('type', 2)
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
                // 专线状态：0/空=未完成，1=进行中，2=已完成
                if ($lineOrder) {
                $order_data['line_status'] = intval($lineOrder['status']);
                } else {
                    $order_data['line_status'] = 0;
                }
                // 保留主订单表的主键ID，前端可通过该字段直接打开订单编辑页
                $order_data['main_order_id'] = $v['order_id'];
                // 覆盖为可读的订单编号（字符串），用于列表展示
                $order_data['order_id'] = Db::name('order')->where('id',$v['order_id'])->value('orderid');

                // 计算预计到货时间（根据物流表中的 time_limit 时效字段 ）
                $estimatedArrivalTime = null;
                $timeLimit = null;
                if (!empty($order_data['logistics_id'])) {
                    $logisticsInfo = Db::name('logistics')->where('id', $order_data['logistics_id'])->find();
                    if ($logisticsInfo) {
                        $timeLimit = isset($logisticsInfo['time_limit']) ? $logisticsInfo['time_limit'] : null;
                        if (!empty($timeLimit) && !empty($order_data['createtime'])) {
                            // 这里假设 time_limit 为“小时”时效；如果为天，可按天 * 24 转换
                            $hours = (int)$timeLimit;
                            $estimatedArrivalTime = $order_data['createtime'] + $hours * 3600* 24;
                        }
                    }
                }
                if ($timeLimit !== null) {
                    $order_data['time_limit'] = $timeLimit;
                }
                // 当天订单物流未发车、点出发时已将预计到货时间推迟一天（持久化到 order.estimated_arrival_time），优先使用持久化值
                if (!empty($order_data['estimated_arrival_time']) && (int)$order_data['estimated_arrival_time'] > 0) {
                    $estimatedArrivalTime = (int)$order_data['estimated_arrival_time'];
                }
                if (!empty($estimatedArrivalTime)) {
                    // 给前端一个时间戳字段，方便直接用 datetime 格式化
                    $order_data['estimated_arrival_time'] = $estimatedArrivalTime;
                }
                // 下单时间、预计到货时间仅展示年月日
                if (!empty($order_data['createtime'])) {
                    $order_data['createtime'] = date('Y-m-d', (int)$order_data['createtime']);
                }
                if (!empty($order_data['estimated_arrival_time'])) {
                    $order_data['estimated_arrival_time'] = date('Y-m-d', (int)$order_data['estimated_arrival_time']);
                }
                // 计算利润：收入 - 成本（物流+取货司机+送货司机）
                $payPrice = isset($order_data['pay_price']) ? floatval($order_data['pay_price']) : 0;
                $totalCost = (isset($order_data['logistics_driver_cost']) ? floatval($order_data['logistics_driver_cost']) : 0)
                    + (isset($order_data['pickup_driver_fee']) ? floatval($order_data['pickup_driver_fee']) : 0)
                    + (isset($order_data['shipment_driver_fee']) ? floatval($order_data['shipment_driver_fee']) : 0);
                $pickup_tax_point = Db::name('dricerorder')->where('order_id',$order_data['orderid'])->where('type',3)->value('tax_point');
                $shipment_tax_point = Db::name('dricerorder')->where('order_id',$order_data['orderid'])->where('type',1)->value('tax_point');
                if ($payPrice > 0 && $order_data['cost_cont'] >0 ){
                   $order_data['profit'] = round($payPrice - $order_data['cost_cont'], 2);
                }
                // 信息费确认打款：合计信息费与回单/拆包/定金，并读取调度备用金余额
                // 注：订单额外价格/订单其他价格(成本补充费)已在额外费用落库时直接从备用金扣除，此处不再重复计入
                $orderNumber = (string)($order_data['orderid'] ?? '');
                $order_data['info_fee_total'] = round(
                    (float)($order_data['information'] ?? 0)
                    + (float)($order_data['receipt_type_price'] ?? 0)
                    + (float)($order_data['unpack_price'] ?? 0)
                    + (float)($order_data['deposit'] ?? 0),
                    2
                );
                $order_data['reserve_balance'] = round((float)Db::name('dispatch_reserve_fund')->where('admin_id', (int)$v['admin_id'])->value('balance'), 2);
//               print_r($order_data);die;
                if ($order_data) {
                    $row = array_merge((array) $v, (array) $order_data);
                    $grabMinutes = (time() - (int) $v['createtime']) / 60;
                    $row['timeout'] = $grabMinutes >= 15 ? '超时' : '未超时';
                    $rows[] = $row;
                }
            }
//        print_r($rows);die;
 
        // 所有管理员都返回统计数据
        $statistics = null;
        $statQuery = $this->model->where($where);
        if ((int)$admin_group_id === 1) {
            // 全部
        } elseif ($admin_group_identity === AdminUserBind::AUTH_GROUP_IDENTITY_AGENT) {
            $staffAdminIdsForStat = AdminUserBind::getLineDispatchStaffAdminIdsUnderAgent((int)$admin_group_id);
            if ($staffAdminIdsForStat === []) {
                $statQuery->where('admin_id', -1);
            } else {
                $statQuery->whereIn('admin_id', $staffAdminIdsForStat);
            }
        } else {
            $statQuery->where('admin_id', $admin_id);
        }
        foreach ($adminOrderWheres as $w) {
            if (count($w) >= 3) {
                $statQuery->where($w[0], $w[1], $w[2]);
            }
        }
        if ($foreignOrderIds !== null) {
            if ($foreignOrderIds === []) {
                $statQuery->where('order_id', -1);
            } else {
                $statQuery->whereIn('order_id', $foreignOrderIds);
            }
        }
        $orderIds = $statQuery->column('order_id');
        $orderIds = array_unique(array_filter($orderIds));
        if (!empty($orderIds)) {
            $allOrders = Db::name('order')
                ->whereIn('id', $orderIds)
                ->where('pay_status', '<>', 4)
                ->where('pay_status', '<>', 5)
                ->where('pay_status', '<>', 8)
                ->select();
            $totalIncome = 0;   // 总收入（仅利润>=0的订单计入）
            $totalWeight = 0;
            $totalVolume = 0;
            $totalOrders = 0;
            foreach ($allOrders as $order) {
                $totalOrders++;
                $totalWeight += isset($order['weight']) ? floatval($order['weight']) : 0;
                $totalVolume += isset($order['direction']) ? floatval($order['direction']) : 0;
                $payPrice = isset($order['pay_price']) ? floatval($order['pay_price']) : 0;
                $totalCost = (isset($order['logistics_driver_cost']) ? floatval($order['logistics_driver_cost']) : 0)
                    + (isset($order['pickup_driver_fee']) ? floatval($order['pickup_driver_fee']) : 0)
                    + (isset($order['shipment_driver_fee']) ? floatval($order['shipment_driver_fee']) : 0);
                $profit = $payPrice - $totalCost;
                if ($profit > 0) {
                    $totalIncome += $payPrice;
                }
            }
            $statistics = [
                'total_income' => round($totalIncome, 2),
                'total_weight' => round($totalWeight, 2),  // weight 单位已为吨
                'total_volume' => round($totalVolume, 2),
                'total_orders' => $totalOrders,
            ];
        } else {
            $statistics = [
                'total_income' => 0,
                'total_weight' => 0,
                'total_volume' => 0,
                'total_orders' => 0,
            ];
        }

        $result = ['total' => $paginator->total(), 'rows' => $rows, 'statistics' => $statistics];
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
        if (!empty($data['ids'])) {
            $this->errorIfOrderRejectedByAdminOrderId($data['ids']);
        }
        $adminInfo = $this->auth->getUserInfo();

        $admin_grouop_id = Db::name('auth_group_access')->where('uid',$adminInfo['id'])->value('group_id');
        $admin_grouop = Db::name('auth_group')->where('id',$admin_grouop_id)->find();
        $admin_grouop_name =$admin_grouop['name'];

        $res = Db::name('admin_order')->where('id',$data['ids'])->update(['status'=>1]);
        $order_id = Db::name('admin_order')->where('id',$data['ids'])->value('order_id');
        $orderInfo = Db::name('order')->where('id', $order_id)->find();
        if ($orderInfo && isset($orderInfo['logistics_status']) && (int)$orderInfo['logistics_status'] === 7) {
            $this->error('已收货后不能重新报价');
        }
        if ($orderInfo['find_car_type'] == "配车"){
//            print_r($order_id);die;
            $ress = Db::name('order')->where('id',$order_id)->update(['backend_status'=>2]);
        }else{
            $payPrice = isset($data['pay_price']) ? round(floatval($data['pay_price']), 2) : null;
            if ($payPrice === null || $payPrice < 0) {
                $this->error('请填写有效的订单金额');
            }
            $ress = Db::name('order')->where('id',$order_id)->update(['pay_status'=>7,'pay_price'=>$payPrice]);
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
     * 月结送货完成时扣除对应调度备用金
     */
    private function deductMonthlyDeliveryReserve($order, $driverOrder)
    {
        if ((int)($order['pay_type'] ?? 0) !== 2 || (int)($driverOrder['type'] ?? 0) !== 3) {
            return;
        }
        $dispatchId = (int)($this->model->where('id', (int)$order['id'])->value('admin_id'));
        if ($dispatchId <= 0) {
            return;
        }
        $profit = max(0, round((float)($order['pay_price'] ?? 0) - (float)($order['pickup_driver_fee'] ?? 0) - (float)($order['logistics_driver_cost'] ?? 0) - (float)($order['shipment_driver_fee'] ?? 0), 2));
        // 司机部分备用金：按 dricerorder.price（填入原始价，不含税）
        $deliveryDriver = Db::name('dricerorder')->where('order_id', (string)$order['orderid'])->where('type', 3)->find();
        $deliveryFeeNoTax = $deliveryDriver
            ? (float)($deliveryDriver['price'] ?? 0)
            : (float)($order['shipment_driver_fee'] ?? 0);
        $amount = round((float)($order['logistics_driver_cost'] ?? 0) + $deliveryFeeNoTax + $profit, 2);
        if ($amount <= 0 || Db::name('dispatch_reserve_fund_log')->where('admin_id', $dispatchId)->where('order_id', (int)$order['id'])->where('direction', 'deduct')->where('remark', 'like', '月结送货扣除%')->find()) {
            return;
        }
        Db::startTrans();
        try {
            $fund = Db::name('dispatch_reserve_fund')->lock(true)->where('admin_id', $dispatchId)->find();
            $before = round((float)($fund['balance'] ?? 0), 2);
            $after = round($before - $amount, 2);
            Db::name('dispatch_reserve_fund')->where('admin_id', $dispatchId)->update(['balance' => $after, 'total_deduct' => Db::raw('total_deduct+' . $amount), 'updatetime' => time()]);
            Db::name('dispatch_reserve_fund_log')->insert(['admin_id' => $dispatchId, 'order_id' => (int)$order['id'], 'order_number' => (string)$order['orderid'], 'driver_order_id' => (int)$driverOrder['id'], 'type' => 0, 'amount' => $amount, 'direction' => 'deduct', 'balance_before' => $before, 'balance_after' => $after, 'remark' => '月结送货扣除（干线费+送货费+利润）', 'admin_name' => '系统', 'createtime' => time()]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            \think\Log::error('后台月结送货备用金扣除失败：' . $e->getMessage());
        }
    }

    /**
     * 标记订单为送货
     */
    public function urgent()
    {
        $data = $this->request->param();
        if (empty($data['ids'])) {
            $this->error('参数错误');
        }

        // 根据后台订单ID找到对应的前台订单
        $adminOrder = Db::name('admin_order')->where('id', $data['ids'])->find();
        if (!$adminOrder) {
            $this->error('订单不存在或无权操作');
        }
        $this->errorIfOrderRejectedByAdminOrderId($data['ids']);

        $orderId = $adminOrder['order_id'];
        // 只在订单表中做标记，前端仅用于展示
        $res = Db::name('order')->where('id', $orderId)->update(['is_urgent' => 1]);

        if ($res !== false) {
            $this->success('已标记为送货');
        } else {
            $this->error('标记失败');
        }
    }

    /**
     * 取消订单送货标记
     */
    public function cancelUrgent()
    {
        $data = $this->request->param();
        if (empty($data['ids'])) {
            $this->error('参数错误');
        }

        // 根据后台订单ID找到对应的前台订单
        $adminOrder = Db::name('admin_order')->where('id', $data['ids'])->find();
        if (!$adminOrder) {
            $this->error('订单不存在或无权操作');
        }
        $this->errorIfOrderRejectedByAdminOrderId($data['ids']);

        $orderId = $adminOrder['order_id'];
        $res = Db::name('order')->where('id', $orderId)->update(['is_urgent' => 0]);

        if ($res !== false) {
            $this->success('已取消送货');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 驳回订单：填写驳回原因，可选选择驳回哪一项；将 pay_status 改为 8，并保存到 reject、reject_field 字段。
     * 前端修改订单时若存在 reject_field，则只允许修改该项。
     */
    public function reject()
    {
        $data = $this->request->param();
        if (empty($data['ids'])) {
            $this->error('参数错误');
        }
        $reject = isset($data['reject']) ? trim($data['reject']) : '';
        if ($reject === '') {
            $this->error('请填写驳回原因');
        }

        $rejectField = isset($data['reject_field']) ? trim($data['reject_field']) : '';

        $adminOrder = Db::name('admin_order')->where('id', $data['ids'])->find();
        if (!$adminOrder) {
            $this->error('订单不存在或无权操作');
        }

        $orderId = $adminOrder['order_id'];
        $curPay = (int) Db::name('order')->where('id', $orderId)->value('pay_status');
        if ($curPay === 8) {
            $this->error('该订单已驳回');
        }

        $update = [
            'pay_status'   => 8,
            'reject'       => $reject,
            'reject_field' => $rejectField === '' ? null : $rejectField,
        ];
        $res = Db::name('order')->where('id', $orderId)->update($update);

        if ($res !== false) {
            $this->success('驳回成功');
        } else {
            $this->error('驳回失败');
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
        $this->errorIfOrderRejectedByAdminOrderId($data['ids']);
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
//        if ($admin_order['status']  == 1) {
//            $this->error('线路已确认，不能更改');
//        }
        $order_id = $admin_order['order_id'];

        // 获取订单信息
        $order = Db::name('order')->where('id', $order_id)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $this->errorIfOrderRejectedByAdminOrderId($data['ids']);
//        $driverorder = Db::name('dricerorder')
//            ->where('order_id',$order['orderid'])
//            ->where('type',2)
//            ->where('status',2)
//            ->find();
        if ($order['logistics_status'] >= 4) {
            $this->error('专线运输中，不能修改');
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
                /*
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
                */
            } else {
                \think\Log::info('用户已存在: ' . $mobile . ', 用户ID: ' . $user['id']);
            }
        } else {
            \think\Log::warning('路线手机号为空，无法创建用户，路线ID: ' . $data['selected_row']);
        }
        
        // 更新物流路线ID
        $res = Db::name('order')->where('id', $order_id)->update(['logistics_id' => $data['selected_row']]);
        if ($res) {
            // 重新计算金额
            $costResult = $this->recalculateLogisticsCost($order_id, $data['selected_row']);
            // 线路改路线后，只给「抢了同一个单子的调度」写入提醒（查该订单下角色为调度的 admin_order）
            $dispatchGroupId = Db::name('auth_group')->where('name', '调度')->where('status', 'normal')->value('id');
            if ($dispatchGroupId) {
                $dispatchRow = Db::name('admin_order')->where('order_id', $order_id)->where('group_id', $dispatchGroupId)->find();
                if ($dispatchRow) {
                    Db::name('admin_route_change_alert')->insert([
                        'admin_id' => $dispatchRow['admin_id'],
                        'order_id' => $order_id,
                        'admin_order_id' => $dispatchRow['id'],
                        'orderid' => $order['orderid'] ?? '',
                        'createtime' => time(),
                        'is_read' => 0,
                    ]);
                }
            }
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
            
            // 计算物流费用（传递地址信息避免重复查询）
            $costResult = $this->calculateLogisticsCost($order, $logistics, $carType, $distances, $loading, $unload);
//            print_r($costResult);die;
            // 若调度已填写取货/送货司机及费用，改路线重算价格时不覆盖已填的司机价格
            $pickupDriverFilled = Db::name('dricerorder')
                ->where('order_id', $order['orderid'] ?? '')
                ->where('type', 1)
                ->where('d_id', '>', 0)
                ->find();
            $shipmentDriverFilled = Db::name('dricerorder')
                ->where('order_id', $order['orderid'] ?? '')
                ->where('type', 3)
                ->where('d_id', '>', 0)
                ->find();
            // 更新订单金额和距离
            $updateData = [
                'logistics_driver_cost' => $costResult['logistics_driver_cost'],
                'pickup_driver_fee' => $pickupDriverFilled ? (float)$order['pickup_driver_fee'] : $costResult['pickup_driver_fee'],
                'shipment_driver_fee' => $shipmentDriverFilled ? (float)$order['shipment_driver_fee'] : $costResult['shipment_driver_fee'],
                'pickup_distance' => round($distances['loading_to_start'], 2), // 取货地到专线起点的距离
                'logistics_distance' => round($distances['logistics_line'], 2), // 专线距离
                'shipmenty_distance' => round($distances['end_to_unload'], 2) // 专线终点到卸货地的距离
            ];
            
            $result = Db::name('order')->where('id', $orderId)->update($updateData);
            if ($result !== false) {
                $this->updateOrderCostCont($orderId);
            }
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
    private function calculateLogisticsCost($order, $logistics, $carType, $distances, $loading = null, $unload = null)
    {
        // direction字段存储的是体积（立方米），weight是重量（吨）
        $weight = $order['weight'] ?? 0;
        $volume = $order['direction'] ?? 0; // direction是总方位（体积，单位：立方米）
        
        // 使用calculatePrice函数计算价格
        $priceResult = calculatePrice(
            $weight,
            $volume,
            $logistics['perton'] ?? 0,
            $logistics['side'] ?? 0,
            $logistics['reflux'] ?? 0,
            $logistics['bulky'] ?? 0,
        );
        
        // 获取配置
        $PickUpDriverFreight = Config::get('site.PickUpDriverFreight') ?: 0;
        $LogisticsDriverFreight = Config::get('site.LogisticsDriverFreight') ?: 0;
        $DeliveryDriverFreight = Config::get('site.DeliveryDriverFreight') ?: 0;
        
        // 获取车型的type值
        $carTypeType = $carType['type'] ?? 0;
        
        // 获取装货和卸货地址信息（如果未传递则重新查询）
        if (!$loading) {
            $loading = Db::name('user_address')->where('id', $order['loading'])->find();
        }
        if (!$unload) {
            $unload = Db::name('user_address')->where('id', $order['unload'])->find();
        }
        
        // 从地址中提取省份
        $loadingProvince = '';
        $unloadProvince = '';
        if ($loading && !empty($loading['address'])) {
            $loadingProvince = $this->extractProvinceFromAddress($loading['address']);
        }
        if ($unload && !empty($unload['address'])) {
            $unloadProvince = $this->extractProvinceFromAddress($unload['address']);
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
        return [
//            'logistics_cost' => round($logisticsCost, 2),
            'logistics_driver_cost' => round($priceResult['price'], 2),
            'pickup_driver_fee' => round($pickupDriverFee, 2),
            'shipment_driver_fee' => round($shipmentDriverFee, 2),
//            'pickup_fee' => round($pickupFee, 2),
//            'shipment_fee' => round($shipmentFee, 2)
        ];
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
     * 根据车型type和省份查询provinceprice表获取价格
     * @param int $carTypeType 车型的type值
     * @param string $province 省份名称
     * @return array|false 返回包含startingfare和price的数组，如果未找到返回false
     */
    private function getProvincePrice($carTypeType, $province)
    {
        if (empty($carTypeType) || empty($province)) {
            return false;
        }

        $provincePrice = Db::name('provinceprice')
            ->where('car_type', $carTypeType)
            ->where('City', $province)
            ->find();

        if ($provincePrice) {
            // 处理字段名大小写问题，兼容不同的命名方式
            $startingfare = $provincePrice['Startingfare'] ?? $provincePrice['startingfare'] ?? 0;
            $price = $provincePrice['Price'] ?? $provincePrice['price'] ?? 0;

            return [
                'startingfare' => floatval($startingfare),
                'price' => floatval($price)
            ];
        }

        return false;
    }
    /**
     * 从地址中提取省份
     * @param string $address 完整地址
     * @return string 省份名称
     */
    private function extractProvinceFromAddress($address)
    {
        if (empty($address)) {
            return '';
        }

        // 如果地址中包含"省"，提取省名
        if (strpos($address, '省') !== false) {
            $part = explode('省', $address);
            return trim($part[0]) . '省';
        }

        // 如果是直辖市（包含"市"但不包含"省"）
        if (strpos($address, '市') !== false) {
            $part = explode('市', $address);
            $province = trim($part[0]);
            // 检查是否是直辖市，直辖市返回不带"市"的名称
            $municipalities = ['北京', '上海', '天津', '重庆'];
            if (in_array($province, $municipalities)) {
                return $province;
            }
        }

        return '';
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
        if ((int) $order['pay_status'] === 8) {
            $this->error('订单已驳回，请待用户修改订单后再操作');
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
     * 查看订单详情
     *
     * @return string
     * @throws \think\Exception
     * @throws DbException
     */
    public function view($ids = null)
    {
        // 查询admin_order记录
        $adminOrder = $this->model->get($ids);
        if (!$adminOrder) {
            $this->error(__('No Results were found'));
        }
        
        // 查询订单信息
        $row = Db::name('order')->where('id', $adminOrder['order_id'])->find();

        if (!$row) {
            $this->error('订单不存在');
        }
        // 初始化订单模型以获取列表数据
        $orderModel = new \app\admin\model\Order;
        $this->view->assign("findCarTypeList", $orderModel->getFindCarTypeList());
        $this->view->assign("isinvoiceList", $orderModel->getIsinvoiceList());
        $this->view->assign("payTypeList", $orderModel->getPayTypeList());
        $this->view->assign("deliveryList", $orderModel->getDeliveryList());
        $this->view->assign("isrequirementsList", $orderModel->getIsrequirementsList());
        $this->view->assign("serviceList", $orderModel->getServiceList());
        $this->view->assign("controlList", $orderModel->getControlList());
        $this->view->assign("textMessageList", $orderModel->getTextMessageList());
        $this->view->assign("payStatusList", $orderModel->getPayStatusList());
        $this->view->assign("logisticsStatusList", $orderModel->getLogisticsStatusList());
        $this->view->assign("getPayPartyList", $orderModel->getPaypartyList());
        
        $loading_address = Db::name('user_address')->where('id',$row['loading'])->find();
        $unload_address = Db::name('user_address')->where('id',$row['unload'])->find();
        $logistics = Db::name('logistics')->where('id',$row['logistics_id'])->find();
        if ($loading_address){
            $row['loading'] = $loading_address['user_name'].'-'.$loading_address['mobile'].'-'.$loading_address['address'].'-'.'-'.$loading_address['detailed_address'].'-';
        }
        if ($unload_address){
            $row['unload'] = $unload_address['user_name'].'-'.$unload_address['mobile'].'-'.$unload_address['address'].'-'.'-'.$unload_address['detailed_address'].'-';
        }
        $row['car_type_id'] = Db::name('car_type')->where('id',$row['car_type_id'])->value('name');
        $row['goods_type_id'] = Db::name('goods_type')->where('id',$row['goods_type_id'])->value('name');
        $row['dimensions'] = Db::name('dimensions')->where('order_id',$row['orderid'])->select();
        $logisticsInfo = [];
        if (empty($logistics)){
            $row['shipping_logistics_address'] ='';
            $row['shipping_logistics_mobile'] = '';
            $row['arrival_logistics_address'] = '';
            $row['arrival_logistics_mobile'] = '';
            $row['shipping_logistics_name'] = '';
        }else{
            $logisticsInfo = $logistics;
            $row['shipping_logistics_address'] = $logistics['shipping_logistics_address'];
            $row['shipping_logistics_name'] = $logistics['shipping_logistics_name'];
            $row['shipping_logistics_mobile'] = $logistics['shipping_logistics_mobile'];
            $row['arrival_logistics_address'] = $logistics['arrival_logistics_address'];
            $row['arrival_logistics_mobile'] = $logistics['arrival_logistics_mobile'];
        }
        $this->view->assign('logisticsInfo', $logisticsInfo);
//        print_r($row['receipt_type_id']);die;
        $receipt_type = Db::name('receipt_type')->where('id',$row['receipt_type_id'])->find();
        $row['delivery_type_id'] = Db::name('delivery_type')->where('id',$row['delivery_type_id'])->value('name');
        if ($receipt_type){
            $row['receipt_type_id'] = $receipt_type['name'].'-'.$receipt_type['type'];
        }
        $row['unpack_id'] = Db::name('unpack')->where('id',$row['unpack_id'])->value('name') .'X'.$row['unpack_num'];
        // other_id 可能为单个 id 或逗号分隔的多个 id（如 3,4,5）
        $other_ids = $row['other_id'];
        if (empty($other_ids)) {
            $row['other_id'] = '';
        } elseif (strpos((string)$other_ids, ',') !== false) {
            $ids = array_filter(array_map('intval', explode(',', $other_ids)));
            $row['other_id'] = $ids ? implode(',', Db::name('other')->where('id', 'in', $ids)->column('name')) : '';
        } else {
            $row['other_id'] = Db::name('other')->where('id', $other_ids)->value('name');
        }
        // 提货要求 deliveryrequirements_id 可能为单个 id 或逗号分隔的多个 id
        $dr_ids = isset($row['deliveryrequirements_id']) ? $row['deliveryrequirements_id'] : '';
        if (empty($dr_ids)) {
            $row['deliveryrequirements_id'] = '';
        } elseif (strpos((string)$dr_ids, ',') !== false) {
            $ids = array_filter(array_map('intval', explode(',', $dr_ids)));
            $row['deliveryrequirements_id'] = $ids ? implode(',', Db::name('deliveryrequirements')->where('id', 'in', $ids)->column('name')) : '';
        } else {
            $row['deliveryrequirements_id'] = Db::name('deliveryrequirements')->where('id', $dr_ids)->value('name');
        }
        // 专线装货要求 loadingrequirements_id 可能为单个 id 或逗号分隔的多个 id
        $lr_ids = isset($row['loadingrequirements_id']) ? $row['loadingrequirements_id'] : '';
        if (empty($lr_ids)) {
            $row['loadingrequirements_id'] = '';
        } elseif (strpos((string)$lr_ids, ',') !== false) {
            $ids = array_filter(array_map('intval', explode(',', $lr_ids)));
            $row['loadingrequirements_id'] = $ids ? implode(',', Db::name('loadingrequirements')->where('id', 'in', $ids)->column('name')) : '';
        } else {
            $row['loadingrequirements_id'] = Db::name('loadingrequirements')->where('id', $lr_ids)->value('name');
        }
        $row['packaging_id'] = Db::name('packaging_num')->where('order_id',$row['orderid'])->field('name,quantity')->select();
        // 货物照片：拆分多张并补全完整 URL，供详情页展示
        $goodsImages = [];
        if (!empty($row['goods_image'])) {
            $imageList = is_array($row['goods_image']) ? $row['goods_image'] : explode(',', (string)$row['goods_image']);
            foreach ($imageList as $img) {
                $img = trim($img);
                if ($img !== '') {
                    $goodsImages[] = cdnurl($img, true);
                }
            }
        }
        $row['goods_images'] = $goodsImages;
        // 货物照片原始相对路径列表（编辑回显用）
        $row['goods_image_raw_list'] = isset($imageList) ? $imageList : [];
        $row['pickup_tax_point'] = Db::name('dricerorder')->where('order_id',$row['orderid'])->where('type',1)->value('tax_point');
        /** @var TYPE_NAME $row */
        $row['shipment_tax_point'] = Db::name('dricerorder')->where('order_id',$row['orderid'])->where('type',3)->value('tax_point');
        // 加载代收货款信息
        $charge = Db::name('charge')->where('orderid', $row['orderid'])->find();
        $this->view->assign('charge', $charge ?: []);
        $this->view->assign('row', $row);
        // 后台订单记录（含备注字段）
        $this->view->assign('adminOrder', $adminOrder->toArray());
        // 备注写入权限：线路(identity=2)负责填写，调度(identity=3)只读，代理/总后台可写
        $groupIdentity = 0;
        if ($this->auth) {
            $businessGroupId = AdminUserBind::getPrimaryBusinessGroupIdForAdmin((int)$this->auth->id);
            $groupIdentity = AdminUserBind::resolveEffectiveOrderRoleIdentity($businessGroupId);
        }
        $this->view->assign('canEditBreak', $groupIdentity === 3 ? 0 : 1);
        // 货物信息编辑权限：仅线路(identity=2)可修改，且修改不重算价格
        $this->view->assign('canEditCargo', $groupIdentity === 2 ? 1 : 0);
        return $this->view->fetch();
    }

    /**
     * 保存后台订单备注（admin_order.break）
     * @return Json
     */
    public function save_break()
    {
        $id = (int)$this->request->post('ids');
        $break = $this->request->post('break', '', 'trim');
        if ($id <= 0) {
            $this->error('订单ID不能为空');
        }
        // 调度只读，禁止修改备注
        $groupIdentity = 0;
        if ($this->auth) {
            $businessGroupId = AdminUserBind::getPrimaryBusinessGroupIdForAdmin((int)$this->auth->id);
            $groupIdentity = AdminUserBind::resolveEffectiveOrderRoleIdentity($businessGroupId);
        }
        if ($groupIdentity === 3) {
            $this->error('调度角色仅可查看，不能修改备注');
        }
        if (mb_strlen($break) > 500) {
            $this->error('备注内容过长');
        }
        $updated = Db::name('admin_order')->where('id', $id)->update(['break' => $break]);
        if ($updated === false) {
            $this->error('保存失败');
        }
        $this->success('保存成功');
    }

    /**
     * 保存订单货物信息（数量/货物名称/长宽高/货物照片）
     * 仅线路(identity=2)可操作，且不重新计算价格
     * @return Json
     */
    public function save_cargo()
    {
        $adminOrderId = (int)$this->request->post('ids');
        if ($adminOrderId <= 0) {
            $this->error('订单ID不能为空');
        }
        $groupIdentity = 0;
        if ($this->auth) {
            $businessGroupId = AdminUserBind::getPrimaryBusinessGroupIdForAdmin((int)$this->auth->id);
            $groupIdentity = AdminUserBind::resolveEffectiveOrderRoleIdentity($businessGroupId);
        }
        if ($groupIdentity !== 2) {
            $this->error('仅线路角色可修改货物信息');
        }

        $adminOrder = Db::name('admin_order')->where('id', $adminOrderId)->find();
        if (!$adminOrder) {
            $this->error('订单记录不存在');
        }
        $order = Db::name('order')->where('id', (int)($adminOrder['order_id'] ?? 0))->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        // 仅更新货物字段，不触碰任何价格字段（pay_price/cost_cont/logistics_cost 等不重算）
        $update = [];
        $quantity = trim((string)$this->request->post('quantity', ''));
        if ($this->request->has('quantity') && $quantity !== '') {
            $update['quantity'] = $quantity;
        }
        if ($this->request->has('goods_name')) {
            $update['goods_name'] = trim((string)$this->request->post('goods_name', ''));
        }
        if ($this->request->has('goods_image')) {
            $update['goods_image'] = trim((string)$this->request->post('goods_image', ''));
        }

        // 长宽高：支持多行，兼容 JSON 和 size_list[index][field] 两种提交格式
        $sizeList = $this->request->post('size_list/a', []);
        if (is_string($sizeList)) {
            $decodedSizeList = json_decode($sizeList, true);
            $sizeList = is_array($decodedSizeList) ? $decodedSizeList : [];
        }
        $dimRows = [];
        if (is_array($sizeList)) {
            foreach ($sizeList as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $l = trim((string)($item['long'] ?? ''));
                $w = trim((string)($item['wide'] ?? ''));
                $h = trim((string)($item['hige'] ?? ''));
                if ($l === '' && $w === '' && $h === '') {
                    continue;
                }
                // orderid 形如 LZ66xxxxxxxxxx，必须原样（字符串）保存，不能 (int) 强转，否则会写成 0
                $dimRows[] = ['order_id' => $order['orderid'], 'long' => $l, 'wide' => $w, 'hige' => $h];
            }
        }

        Db::transaction(function () use ($order, $update, $dimRows) {
            if (!empty($update)) {
                if (Db::name('order')->where('id', (int)$order['id'])->update($update) === false) {
                    throw new \Exception('订单货物信息更新失败');
                }
            }
            Db::name('dimensions')->where('order_id', $order['orderid'])->delete();
            if (!empty($dimRows)) {
                if (Db::name('dimensions')->insertAll($dimRows) === false) {
                    throw new \Exception('尺寸信息保存失败');
                }
            }
        });

        $this->success('保存成功');
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
        $this->errorIfOrderRejectedByAdminOrderId($id);

        $dricerorder= Db::name('dricerorder') 
            ->where('order_id', $order['orderid'])
            ->where('type', 2)
            ->find();
        // 运输中/已完成禁止取消（专线开始运输时 status=5，完成时 status=2）
        if ($dricerorder && in_array(intval($dricerorder['status']), [2, 5], true)) {
            $this->error('物流状态运输中不能取消');
        }
        // 兜底：订单物流状态为运输中也禁止取消
        if (isset($order['logistics_status']) && intval($order['logistics_status']) === 4) {
            $this->error('物流状态运输中不能取消');
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

        $this->errorIfOrderRejectedByAdminOrderId($id);

        if (isset($order['logistics_status']) && intval($order['logistics_status']) >= 7) {
            $this->error('已收货订单不能取消司机订单');
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

    /**
     * @return void
     * @throws DbException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\PDOException
     * 保存专车信息
     */
    public function save_order_info(){
        $data = $this->request->param();
        if (!empty($data['order_id'])) {
            $this->errorIfOrderRejectedByAdminOrderId($data['order_id']);
        }
        $realOrderId = Db::name('admin_order')->where('id', isset($data['order_id']) ? $data['order_id'] : 0)->value('order_id');
        if ($realOrderId) {
            $order = Db::name('order')->where('id', $realOrderId)->find();
            if ($order && isset($order['logistics_status']) && (int)$order['logistics_status'] === 7) {
                $this->error('已收货后不能更改订单信息');
            }
        }
        $find_car_type = isset($data['find_car_type']) ? $data['find_car_type'] : '';
        $driver_price = isset($data['driver_price']) ? $data['driver_price'] : null;
        unset($data['ids']);
        unset($data['temp_url_path']);
        unset($data['find_car_type']);
        unset($data['driver_price']); // 司机价格单独写入 order 表，不写入 deliveryinfo
        $data['order_id'] = $realOrderId ?: Db::name('admin_order')->where('id', $data['order_id'])->value('order_id');
        $is_set = Db::name('deliveryinfo')->where('order_id', $data['order_id'])->find();
        if ($is_set){
            $res = Db::name('deliveryinfo')->where('order_id', $data['order_id'])->update($data);
            if ($res !== false){
                if ($find_car_type === '专车') {
                    $this->createDedicatedDriverOrder(
                        $data['order_id'],
                        isset($data['driver_name']) ? trim($data['driver_name']) : '',
                        isset($data['driver_num']) ? trim($data['driver_num']) : '',
                        $driver_price
                    );
                } elseif ($find_car_type === '小票快运' && $driver_price !== null && $driver_price !== '') {
                    // 小票快运：将填写的价格作为专线司机成本写入 order.logistics_driver_cost，并刷新总成本
                    $lineCost = round(floatval($driver_price), 2);
                    Db::name('order')->where('id', $data['order_id'])->update(['logistics_driver_cost' => $lineCost,'logistics_status'=>2]);
                    $this->updateOrderCostCont($data['order_id']);
                }
                $this->success('修改成功');
            }
        } else {
            $res = Db::name('deliveryinfo')->insert($data);
            if ($res){
                if ($find_car_type === '专车') {
                    $this->createDedicatedDriverOrder(
                        $data['order_id'],
                        isset($data['driver_name']) ? trim($data['driver_name']) : '',
                        isset($data['driver_num']) ? trim($data['driver_num']) : '',
                        $driver_price
                    );
                } elseif ($find_car_type === '小票快运' && $driver_price !== null && $driver_price !== '') {
                    // 小票快运：将填写的价格作为专线司机成本写入 order.logistics_driver_cost，并刷新总成本
                    $lineCost = round(floatval($driver_price), 2);
                    Db::name('order')->where('id', $data['order_id'])->update(['logistics_driver_cost' => $lineCost,'logistics_status'=>2]);
                    $this->updateOrderCostCont($data['order_id']);
                }
                $this->success('添加成功');
            }
        }
    }

    /**
     * 专车：在 save_order_info 内完成 add_driver 逻辑（创建/更新司机订单、轨迹、物流状态、总成本）
     * @param int $orderId order 表主键 id
     * @param string $driverName 司机姓名
     * @param string $driverPhone 司机电话
     * @param string|float|null $driverPrice 司机价格
     */
    private function createDedicatedDriverOrder($orderId, $driverName, $driverPhone, $driverPrice)
    {
        $orderId = intval($orderId);
        if ($orderId <= 0) {
            return;
        }
        $order = Db::name('order')->where('id', $orderId)->find();
        if (!$order || empty($order['orderid'])) {
            return;
        }
        $orderNumber = $order['orderid'];
        $pickupDriverCost = 0;
        if ($driverPrice !== null && $driverPrice !== '') {
            $pickupDriverCost = round(floatval($driverPrice), 2);
            Db::name('order')->where('id', $orderId)->update(['pickup_driver_fee' => $pickupDriverCost]);
        } else {
            $pickupDriverCost = isset($order['pickup_driver_fee']) ? floatval($order['pickup_driver_fee']) : 0;
        }
        $pickupUserId = 0;
        if (!empty($driverPhone)) {
            $pickupUser = Db::name('user')->where('mobile', $driverPhone)->where('identity', 2)->find();
            if (!$pickupUser) {
                /*
                $auth = \app\common\library\Auth::instance();
                $registerResult = $auth->register($driverPhone, md5(rand(9999999, 100000000000)), '', $driverPhone, ['identity' => 2]);
                if (!$registerResult) {
                    $this->error('自动注册取货司机失败：' . $auth->getError());
                }
                $pickupUser = Db::name('user')->where('mobile', $driverPhone)->where('identity', 2)->find();
                */
            }
            $pickupUserId = $pickupUser ? (int)$pickupUser['id'] : 0;
        }
        if ($pickupUserId && $orderNumber) {
            $existingPickupOrder = Db::name('dricerorder')
                ->where('order_id', $orderNumber)
                ->where('type', 1)
                ->find();
            $pickupData = [
                'd_id'          => $pickupUserId,
                'driver_name'   => $driverName,
                'driver_mobile' => $driverPhone,
                'order_id'     => $orderNumber,
                'type'         => 1,
                'price'        => $pickupDriverCost,
                'createtime'   => time(),
                'grabbingtime' => time(),
                'status'       => 1,
            ];
            if ($existingPickupOrder) {
                Db::name('dricerorder')->where('id', $existingPickupOrder['id'])->update([
                    'd_id' => $pickupData['d_id'],
                    'driver_name' => $pickupData['driver_name'],
                    'driver_mobile' => $pickupData['driver_mobile'],
                    'price' => $pickupData['price'],
                    'grabbingtime' => time(),
                ]);
            } else {
                Db::name('dricerorder')->insert($pickupData);
            }
        }

        $this->writeOrderTrajectoryAndStatus($orderId);
        $this->updateOrderCostCont($orderId);
    }

    /**
     * 专车填写订单信息后：写入物流轨迹（已发货），并把物流状态改为 2
     * @param int $orderId order 表主键 id
     */
    private function writeOrderTrajectoryAndStatus($orderId)
    {
        $orderId = intval($orderId);
        if ($orderId <= 0) {
            return;
        }
        $dispatch = $this->getDispatchContactByOrder($orderId);
        $adminName = $dispatch ? ($dispatch['name'] ?? '调度') : '调度';
        $adminMobile = $dispatch ? ($dispatch['mobile'] ?? '') : '';
        Db::name('trajectory')->insert([
            'order_id'     => $orderId,
            'admin_name'   => $adminName.$adminMobile,
            'admin_mobile' => $adminMobile,
            'createtime'   => time(),
            'type'         => '运输中',
        ]);
        Db::name('order')->where('id', $orderId)->update(['logistics_status' => 2]);
    }

    /**
     * 重新计算 cost_cont（与物流端一致，见 OrderModifyApplier::recalcCostCont）
     * @param int $orderId order 表主键 id
     */
    private function updateOrderCostCont($orderId)
    {
        $orderId = intval($orderId);
        if ($orderId <= 0) {
            return;
        }
        OrderModifyApplier::recalcCostCont($orderId);
    }

    /**
     * 根据订单 id 获取调度角色（identity=2）管理员联系方式
     * @param int $orderId order 表主键 id
     * @return array|null ['name'=>'','mobile'=>'']
     */
    private function getDispatchContactByOrder($orderId)
    {
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
            if (!$group || (isset($group['identity']) ? intval($group['identity']) : 0) !== 2) {
                continue;
            }
            $mobile = Db::name('admin')->where('id', $adminId)->value('mobile');
            return [
                'name'   => $group['name'] ?? '调度',
                'mobile' => $mobile ?: '',
            ];
        }
        return null;
    }

    /**
     * 确认专车到达：仅专车订单可用，更新物流状态并记录物流轨迹
     */
    public function confirm_dedicated_arrival()
    {
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('缺少订单ID');
        }
        $ids = is_array($ids) ? (isset($ids[0]) ? $ids[0] : '') : $ids;
        $this->errorIfOrderRejectedByAdminOrderId($ids);
        $orderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (!$orderId) {
            $this->error('订单不存在');
        }
        $order = Db::name('order')->where('id', $orderId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        if (isset($order['logistics_status']) && (int)$order['logistics_status'] === 7) {
            $this->error('订单已收货，无需重复确认');
        }
        $findCarType = isset($order['find_car_type']) ? $order['find_car_type'] : '';
        if ($findCarType !== '专车') {
            $this->error('仅专车订单可确认到达');
        }
        // ===== 加盟商订单钱包扣费：整体确认收货（专车到达）时，按总运费×比例扣加盟商钱包 =====
        $settle = \app\admin\library\FranchiseService::settleOrderFranchiseFee((int)$orderId);
        if (!$settle['success']) {
            $this->error($settle['msg']);
        }
        $dispatch = $this->getDispatchContactByOrder($orderId);
        $adminName = $dispatch ? ($dispatch['name'] ?? '调度') : '调度';
        $adminMobile = $dispatch ? ($dispatch['mobile'] ?? '') : '';
        Db::name('trajectory')->insert([
            'order_id'     => $orderId,
            'admin_name'   => $adminName,
            'admin_mobile' => $adminMobile,
            'createtime'   => time(),
            'type'         => '已收货',
        ]);
        Db::name('order')->where('id', $orderId)->update(['logistics_status' => 7]);
        $this->success('已确认专车到达');
    }

    /**
     * 获取已填写的额外订单信息（专车/小票快运）
     * 支持传入 order_id（前端传 admin_order 的 id）或真实 order_id
     */
    public function get_order_info_extra()
    {
        $orderId = $this->request->param('order_id');
        if (empty($orderId)) {
            $this->error('缺少订单ID');
        }
        // 前端传的是 admin_order.id，需转为 order 表的主键 id
        $realOrderId = Db::name('admin_order')->where('id', $orderId)->value('order_id');
        if ($realOrderId !== null && $realOrderId !== '') {
            $orderId = $realOrderId;
        }
        $info = Db::name('deliveryinfo')->where('order_id', $orderId)->find();
        if ($info) {
            // 专车/小票快运的价格从 order 表回显
            $orderRow = Db::name('order')->where('id', $orderId)->find();
            if ($orderRow) {
                $findCarType = isset($orderRow['find_car_type']) ? $orderRow['find_car_type'] : '';
                if ($findCarType === '专车' && isset($orderRow['pickup_driver_fee'])) {
                    // 专车：回显取货司机成本
                    $info['driver_price'] = $orderRow['pickup_driver_fee'];
                } elseif ($findCarType === '小票快运' && isset($orderRow['logistics_driver_cost'])) {
                    // 小票快运：回显专线司机成本
                    $info['driver_price'] = $orderRow['logistics_driver_cost'];
                }
            }
        }
        $this->success('获取成功', '', $info ?: []);
    }

    /**
     * 展示订单收款码（与 api/Wechatpay/payQrcode 逻辑一致，支持传入 admin_order.id）
     * @return Json
     */
    public function payQrcode()
    {
        $ids = $this->request->param('ids');
        if (is_array($ids)) {
            $ids = isset($ids[0]) ? $ids[0] : '';
        }
        $ids = intval($ids);
        $order_id = $this->request->param('order_id');
        if (empty($order_id) && empty($ids)) {
            $this->error('订单号不能为空');
        }
        if ($ids) {
            $this->errorIfOrderRejectedByAdminOrderId($ids);
            $realOrderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
            if ($realOrderId !== null && $realOrderId !== '') {
                $order_id = $realOrderId;
            }
        }
        $orderInfo = Db::name('order')->where('id', $order_id)->find();
        if (!$orderInfo) {
            $orderInfo = Db::name('order')->where('orderid', $order_id)->find();
        }
        if (!$orderInfo) {
            $this->error('订单不存在');
        }
        if (isset($orderInfo['pay_status']) && $orderInfo['pay_status'] == 3) {
            $this->error('订单已支付');
        }
        // 每次生成使用唯一 out_trade_no，避免“请求重入时参数与首次请求不一致”
        $outTradeNo = $orderInfo['orderid'] . '::' . time();
        $paramss = [
            'amount'    => $orderInfo['pay_price'],
            'orderid'   => $outTradeNo,
            'title'     => '订单支付-' . ($orderInfo['orderid'] ?? ''),
            'notifyurl' => 'https://' . $_SERVER['SERVER_NAME'] . '/api/Wechatpay/giftCartNotify',
            'method'    => 'scan',
            'type'      => 'wechat',
        ];
        try {
            $data = \addons\epay\library\Service::submitOrder($paramss);
        } catch (\Exception $e) {
            $this->error('生成支付二维码失败：' . $e->getMessage());
        }
        $codeUrl = null;
        if (is_object($data) && method_exists($data, 'get')) {
            $codeUrl = $data->get('code_url');
        } elseif (is_array($data)) {
            $codeUrl = $data['code_url'] ?? null;
        }
        if (empty($codeUrl)) {
            $this->error('生成支付二维码失败，未获取到 code_url');
        }
        $qrcodeUrl = '';
        try {
            $size = (int) $this->request->param('size', 280);
            $size = $size < 100 ? 280 : ($size > 600 ? 600 : $size);
            $qrCode = new QrCode($codeUrl);
            $qrCode->setSize($size);
            $qrCode->setMargin(10);
            $qrCode->setForegroundColor(new Color(0, 0, 0));
            $qrCode->setBackgroundColor(new Color(255, 255, 255));
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            $saveDir = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'qrcode' . DS . 'pay' . DS;
            if (!is_dir($saveDir) && !mkdir($saveDir, 0755, true)) {
                $this->error('二维码保存目录创建失败');
            }
            $filename = 'pay_' . $orderInfo['orderid'] . '_' . time() . '.png';
            $savePath = $saveDir . $filename;
            $result->saveToFile($savePath);
            $baseUrl = $this->request->domain();
            $qrcodeUrl = $baseUrl . '/uploads/qrcode/pay/' . $filename;
        } catch (\Exception $e) {
            $this->error('生成二维码图片失败：' . $e->getMessage());
        }
        $this->success('请求成功', null, [
            'code_url'   => $codeUrl,
            'qrcode_url' => $qrcodeUrl,
            'order_id'   => $orderInfo['id'],
            'orderid'    => $orderInfo['orderid'],
            'pay_price'  => $orderInfo['pay_price'],
        ]);
    }

    /**
     * 获取收款方式列表（来源于 payment_method 表）
     * @return Json
     */
    public function getPaymentMethodList()
    {
        $monthlyId = Db::name('payment_method')->where('payment_method', '月结')->value('id');
        if (empty($monthlyId)) {
            Db::name('payment_method')->insert([
                'payment_method' => '月结',
                'p_id'           => 0,
            ]);
        }
        $list = Db::name('payment_method')->order('p_id asc, id asc')->select();
        $this->success('请求成功', null, $list ?: []);
    }

    /**
     * 选择非「订单二维码」时：将订单状态改为已完成，并更新 payment_method_id
     * 传入 admin_order.id 与 payment_method_id
     * @return Json
     */
    public function setOrderPaymentComplete()
    {
        $ids = $this->request->param('ids');
        if (is_array($ids)) {
            $ids = isset($ids[0]) ? $ids[0] : '';
        }
        $ids = intval($ids);
        $payment_method_id = intval($this->request->param('payment_method_id'));
        if (empty($ids)) {
            $this->error('订单不能为空');
        }
        if (empty($payment_method_id)) {
            $this->error('请选择收款方式');
        }
        $this->errorIfOrderRejectedByAdminOrderId($ids);
        // 订单二维码(id=6) 不应走此接口，应走 payQrcode 展示二维码
        if ($payment_method_id == 6) {
            $this->error('请使用展示收款码选择「订单二维码」');
        }
        $order_id = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (empty($order_id)) {
            $this->error('订单不存在');
        }
        $order = Db::name('order')->where('id', $order_id)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $res = Db::name('order')->where('id', $order_id)->update([
            'pay_status'          => 3,
            'payment_method_id'   => $payment_method_id,
        ]);
        if ($res === false) {
            $this->error('更新失败');
        }
        $this->success('付款方式已更新');
    }

    /**
     * 撤销后台误确认的线下收款，恢复为待付款状态。
     * @return Json
     */
    public function cancelOrderPaymentComplete()
    {
        $ids = $this->request->param('ids');
        if (is_array($ids)) {
            $ids = isset($ids[0]) ? $ids[0] : '';
        }
        $ids = intval($ids);
        if (empty($ids)) {
            $this->error('订单不能为空');
        }
        $this->errorIfOrderRejectedByAdminOrderId($ids);
        $orderId = Db::name('admin_order')->where('id', $ids)->value('order_id');
        if (empty($orderId)) {
            $this->error('订单不存在');
        }
        $order = Db::name('order')->where('id', $orderId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $res = Db::name('order')->where('id', $orderId)->update([
            'pay_status'        => 7,
            'payment_method_id' => null,
        ]);
        if ($res === false) {
            $this->error('取消失败');
        }
        $this->success('已取消收款方式，订单已恢复为待付款');
    }

    /**
     * 信息费确认打款：查询信息费与额外费用明细及调度备用金余额
     * @return Json
     */
    public function get_info_fee_pay_info()
    {
        $id = $this->request->param('ids');
        if (is_array($id)) {
            $id = isset($id[0]) ? $id[0] : '';
        }
        $id = intval($id);
        if (empty($id)) {
            $this->error('订单不能为空');
        }
        $this->errorIfOrderRejectedByAdminOrderId($id);

        $adminOrder = Db::name('admin_order')->where('id', $id)->find();
        if (!$adminOrder) {
            $this->error('抢单记录不存在');
        }
        $order = Db::name('order')->where('id', (int)$adminOrder['order_id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $orderNumber = (string)($order['orderid'] ?? '');
        $infoFee = round((float)($order['information'] ?? 0), 2);
        // 注：订单额外价格/订单其他价格(成本补充费)已在额外费用落库时直接从备用金扣除，此处仅统计回单/拆包/定金
        $extraFee = round(
            (float)($order['receipt_type_price'] ?? 0)
            + (float)($order['unpack_price'] ?? 0)
            + (float)($order['deposit'] ?? 0),
            2
        );
        $balance = round((float)Db::name('dispatch_reserve_fund')->where('admin_id', (int)$adminOrder['admin_id'])->value('balance'), 2);

        $this->success('获取成功', '', [
            'order_number'     => $orderNumber,
            'info_fee'         => $infoFee,
            'extra_fee'        => $extraFee,
            'total'            => round($infoFee + $extraFee, 2),
            'balance'          => $balance,
            'info_fee_paid_at' => (int)($order['info_fee_paid_at'] ?? 0),
        ]);
    }

    /**
     * 信息费确认打款：从调度备用金扣除信息费及额外费用，并标记为已付款
     * @return Json
     */
    public function confirm_info_fee_pay()
    {
        $id = $this->request->param('ids');
        if (is_array($id)) {
            $id = isset($id[0]) ? $id[0] : '';
        }
        $id = intval($id);
        if (empty($id)) {
            $this->error('订单不能为空');
        }
        $this->errorIfOrderRejectedByAdminOrderId($id);

        $adminOrder = Db::name('admin_order')->where('id', $id)->find();
        if (!$adminOrder) {
            $this->error('抢单记录不存在');
        }
        $orderId = (int)$adminOrder['order_id'];
        $dispatchId = (int)$adminOrder['admin_id'];
        if ($dispatchId <= 0) {
            $this->error('该订单未分配调度，无法从备用金扣除');
        }
        $order = Db::name('order')->where('id', $orderId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        if ((int)($order['info_fee_paid_at'] ?? 0) > 0) {
            $this->error('该订单信息费已确认打款，请勿重复操作');
        }

        $orderNumber = (string)($order['orderid'] ?? '');
        $infoFee = round((float)($order['information'] ?? 0), 2);
        // 注：订单额外价格/订单其他价格(成本补充费)已在额外费用落库时直接从备用金扣除，此处仅统计回单/拆包/定金
        $extraFee = round(
            (float)($order['receipt_type_price'] ?? 0)
            + (float)($order['unpack_price'] ?? 0)
            + (float)($order['deposit'] ?? 0),
            2
        );
        $total = round($infoFee + $extraFee, 2);
        if ($total <= 0) {
            $this->error('该订单没有需要打款的信息费或额外费用');
        }

        Db::startTrans();
        try {
            $fund = Db::name('dispatch_reserve_fund')->lock(true)->where('admin_id', $dispatchId)->find();
            $currentBalance = $fund ? round((float)$fund['balance'], 2) : 0;
            if ($currentBalance < $total) {
                Db::rollback();
                $this->error('调度备用金余额不足：当前余额 ¥' . number_format($currentBalance, 2, '.', '') . '，需支付 ¥' . number_format($total, 2, '.', ''));
            }
            $afterBalance = round($currentBalance - $total, 2);

            if ($fund) {
                Db::name('dispatch_reserve_fund')->where('admin_id', $dispatchId)->update([
                    'balance'       => $afterBalance,
                    'total_deduct'  => Db::raw('total_deduct+' . $total),
                    'updatetime'    => time(),
                ]);
            } else {
                Db::name('dispatch_reserve_fund')->insert([
                    'admin_id'       => $dispatchId,
                    'balance'        => 0,
                    'total_recharge' => 0,
                    'total_deduct'   => $total,
                    'createtime'     => time(),
                    'updatetime'     => time(),
                ]);
            }

            Db::name('dispatch_reserve_fund_log')->insert([
                'admin_id'       => $dispatchId,
                'order_id'       => $orderId,
                'order_number'   => $orderNumber,
                'driver_order_id' => 0,
                'type'           => 1,
                'amount'         => $total,
                'direction'      => 'deduct',
                'balance_before' => $currentBalance,
                'balance_after'  => $afterBalance,
                'remark'         => '信息费确认打款（含回单/拆包/定金）',
                'admin_name'     => $this->auth->nickname ?: $this->auth->username,
                'createtime'     => time(),
            ]);

            Db::name('order')->where('id', $orderId)->update([
                'info_fee_paid_at' => time(),
            ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('信息费确认打款成功，已从备用金扣除 ¥' . number_format($total, 2, '.', ''));
    }

    /**
     * 查询线路成本
     * @return Json
     */
    public function get_logistics_cost()
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
            'logistics_cost' => $order['logistics_driver_cost'] ?? 0,
            'logistics_name' => '未分配'
        ];

        // 获取专线信息
        if (!empty($order['logistics_id'])) {
            $logistics = Db::name('logistics')->where('id', $order['logistics_id'])->find();
            if ($logistics) {
                $result['logistics_name'] = $logistics['shipping_logistics_name'] ?? '未分配';
            }
        }

        $this->success('获取成功', '', $result);
    }

    /**
     * 修改线路成本
     * @return Json
     */
    public function update_logistics_cost()
    {
        $id = $this->request->param('ids');
        $logisticsCost = $this->request->param('logistics_cost');
        $remark = $this->request->param('remark', '', 'trim');

        // 处理数组情况
        if (is_array($id)) {
            $id = isset($id[0]) ? $id[0] : '';
        }
        $id = intval($id);
        if (empty($id)) {
            $this->error('订单ID不能为空');
        }
        $operatorAdminId = (int)($this->auth->id ?? 0);
        $this->errorIfOrderRejectedByAdminOrderId($id);
 
        // 验证成本金额
        $logisticsCost = floatval($logisticsCost);
        if ($logisticsCost < 0) {
            $this->error('线路成本不能为负数');
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
        $dircerorder = Db::name('dricerorder')
                ->where('order_id', $order['orderid'])
                ->where('type', 2)
                ->find();
        // 检查是否已规划路线
        if (empty($order['logistics_id']) || $order['logistics_id'] == 0) {
            $this->error('请先规划路线');
        }

        // 修改前金额（用于记录）
        $oldAmount = isset($order['logistics_driver_cost']) ? floatval($order['logistics_driver_cost']) : 0;
        $newAmount = round($logisticsCost, 2);

        // 专线确认开始之后：子后台提交总后台审核，总后台直接落库
        $isLineConfirmed = isset($dircerorder['status']) && intval($dircerorder['status']) != 3;
        if ($isLineConfirmed) {
            $adminGroupId = (int) Db::name('auth_group_access')->where('uid', $operatorAdminId)->value('group_id');
            if ($adminGroupId === 1) {
                try {
                    OrderModifyApplier::apply('edit_order', (int)$adminOrder['order_id'], (int)$id, $order['orderid'] ?? '', ['logistics_driver_cost' => $newAmount]);
                    $this->recalcOrderCostCont((int)$adminOrder['order_id']);
                    $this->success('修改线路成本成功');
                } catch (\Exception $e) {
                    $this->error('修改失败：' . $e->getMessage());
                }
            }
            $adminInfo = $this->auth->getUserInfo();
            $adminName = isset($adminInfo['nickname']) ? $adminInfo['nickname'] : '';
            OrderModifyLog::add([
                'admin_order_id'    => $id,
                'order_id'         => intval($adminOrder['order_id']),
                'orderid'          => $order['orderid'] ?? '',
                'modify_type'      => 'edit_order',
                'title'            => '修改线路成本',
                'old_data'         => ['logistics_driver_cost' => $oldAmount],
                'new_data'         => ['logistics_driver_cost' => $newAmount],
                'admin_id'         => $operatorAdminId,
                'admin_name'       => $adminName,
                'logistics_status' => isset($order['logistics_status']) ? (int)$order['logistics_status'] : 0,
                'remark'           => mb_substr($remark, 0, 500, 'utf-8'),
                'audit_status'     => OrderModifyLog::AUDIT_PENDING,
            ]);
            $this->success('已提交，等待总后台审核');
        }

        // 专线确认开始之前：直接修改，无需审核
        Db::name('logistics_cost_log')->insert([
            'admin_order_id' => $id,
            'order_id'       => $adminOrder['order_id'],
            'old_amount'     => $oldAmount,
            'new_amount'     => $newAmount,
            'remark'         => mb_substr($remark, 0, 500, 'utf-8'),
            'admin_id'       => $operatorAdminId,
            'createtime'     => time()
        ]);

        // 更新线路成本
        $result = Db::name('order')->where('id', $adminOrder['order_id'])->update([
            'logistics_driver_cost' => $newAmount
        ]);

        if ($result !== false) {
            // 同步刷新总成本 cost_cont（避免利润等统计展示不一致）
            $this->recalcOrderCostCont((int)$adminOrder['order_id']);
            $this->success('修改线路成本成功');
        } else {
            $this->error('修改线路成本失败');
        }
    }

    /**
     * 上传装卸货图片页面
     *
     * @param int $ids admin_order 表主键ID
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function upload_images($ids = null)
    {
        $adminOrderId = intval($ids);
        if (empty($adminOrderId)) {
            $this->error('订单ID不能为空');
        }

        // 查询 admin_order 记录，拿到真实订单ID
        $adminOrder = Db::name('admin_order')->where('id', $adminOrderId)->find();
        if (!$adminOrder) {
            $this->error('订单记录不存在');
        }
        $this->errorIfOrderRejectedByAdminOrderId($adminOrderId);
        $orderId = intval($adminOrder['order_id']);

        // 查询订单，获取业务订单号（orderid），用于在 dricerorder 中关联
        $order = Db::name('order')->where('id', $orderId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $orderNumber = $order['orderid'] ?? '';

        // 从 dricerorder 表按 type 读取图片：
        // type: 1=取货司机,2=专线,3=送货司机
        $pickupRecord   = $orderNumber ? Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 1)->find() : null;
        $lineRecord     = $orderNumber ? Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 2)->find() : null;
        $deliveryRecord = $orderNumber ? Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 3)->find() : null;
        $orderid = Db::name('order')->where('id', $orderId)->value('orderid');
        $images = [
            'pickup_load_image'      => $pickupRecord['loading_images']   ?? '',
            'pickup_unload_image'    => $pickupRecord['unloading_images'] ?? '',
            'line_load_image'        => $lineRecord['loading_images']     ?? '',
            'line_unload_image'      => $lineRecord['unloading_images']   ?? '',
            'delivery_load_image'    => $deliveryRecord['loading_images'] ?? '',
            'delivery_unload_image'  => $deliveryRecord['unloading_images'] ?? '',
            // 送货司机记录里的回单图片
            'receipt_images'         => $deliveryRecord['receipt_images'] ?? '',
            // 物流单（运单）图片
            'monad_image'            => Db::name('monad')->where('order_id', $orderid)->value('image') ?? '',
         ];
//        print_r($images);die;
        $this->view->assign('admin_order_id', $adminOrderId);
        $this->view->assign('images', $images);
        return $this->view->fetch();
    }

    /**
     * 保存装卸货图片
     *
     * 保存到 dricerorder 表的 loading_images / unloading_images 字段
     * type: 1=取货司机,2=专线,3=送货司机
     *
     * @return void
     */
    public function save_images()
    {
        $data = $this->request->post();
//        print_r($data);die;
        $adminOrderId = isset($data['order_id']) ? intval($data['order_id']) : 0;
        if (empty($adminOrderId)) {
            $this->error('订单ID不能为空');
        }

        $adminOrder = Db::name('admin_order')->where('id', $adminOrderId)->find();
        if (!$adminOrder) {
            $this->error('订单记录不存在');
        }
        $this->errorIfOrderRejectedByAdminOrderId($adminOrderId);
        $orderId = intval($adminOrder['order_id']);

        // 查询订单，获取业务订单号（orderid），用于在 dricerorder 中关联
        $order = Db::name('order')->where('id', $orderId)->find();
        if (!$order) {
            $this->error('订单不存在');
        }
        $orderNumber = $order['orderid'] ?? '';
        if ($orderNumber === '') {
            $this->error('订单号缺失');
        }
        $logisticsStatus = isset($order['logistics_status']) ? (int)$order['logistics_status'] : 0;
        $adminInfo = $this->auth->getUserInfo();
        $adminName = $adminInfo['nickname'];

        // 表单中的 6 个字段
        $pickupLoad    = isset($data['pickup_load_image']) ? trim($data['pickup_load_image']) : '';
        $pickupUnload  = isset($data['pickup_unload_image']) ? trim($data['pickup_unload_image']) : '';
        $lineLoad      = isset($data['line_load_image']) ? trim($data['line_load_image']) : '';
        $lineUnload    = isset($data['line_unload_image']) ? trim($data['line_unload_image']) : '';
        $deliveryLoad   = isset($data['delivery_load_image']) ? trim($data['delivery_load_image']) : '';
        $deliveryUnload = isset($data['delivery_unload_image']) ? trim($data['delivery_unload_image']) : '';
        $receipt_images = isset($data['receipt_images']) ? trim($data['receipt_images']) : '';
        $monad_images   = isset($data['monad_images']) ? trim($data['monad_images']) : '';

        // 没有填写对应司机前，不能上传对应的照片
        if ($pickupLoad !== '' || $pickupUnload !== '') {
            $pickupDriver = Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 1)->find();
            if (!$pickupDriver || empty($pickupDriver['d_id'])) {
                $this->error('没有填写取货司机前不能上传取货照片');
            }
        }
        if ($lineLoad !== '' || $lineUnload !== '') {
            $lineDriver = Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 2)->find();
            if (!$lineDriver) {
                $this->error('没有填写专线前不能上传专线照片');
            }
        }
        if ($deliveryLoad !== '' || $deliveryUnload !== '' || $receipt_images !== '') {
            $deliveryDriver = Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 3)->find();
            if (!$deliveryDriver || empty($deliveryDriver['d_id'])) {
                $this->error('没有填写送货司机前不能上传送货/回单照片');
            }
        }
        $newData = [
            'pickup_load_image'   => $pickupLoad,
            'pickup_unload_image' => $pickupUnload,
            'line_load_image'     => $lineLoad,
            'line_unload_image'   => $lineUnload,
            'delivery_load_image' => $deliveryLoad,
            'delivery_unload_image' => $deliveryUnload,
            'receipt_images'      => $receipt_images,
            'monad_images'        => $monad_images,
        ];
        // 总后台直接应用，不走审核；按 dricerorder 的 status 判断：本次上传涉及到的段若都未完成(status!=2)，则不走审核
        $adminGroupId = (int) Db::name('auth_group_access')->where('uid', $this->auth->id)->value('group_id');
        $anySegmentCompleted = false;
        if (($pickupLoad !== '' || $pickupUnload !== '') && isset($pickupDriver) && (int)($pickupDriver['status'] ?? 0) === 2) {
            $anySegmentCompleted = true;
        }
        if (($lineLoad !== '' || $lineUnload !== '') && isset($lineDriver) && (int)($lineDriver['status'] ?? 0) === 2) {
            $anySegmentCompleted = true;
        }
        if (($deliveryLoad !== '' || $deliveryUnload !== '' || $receipt_images !== '') && isset($deliveryDriver) && (int)($deliveryDriver['status'] ?? 0) === 2) {
            $anySegmentCompleted = true;
        }
        $directApply = ($adminGroupId === 1) || !$anySegmentCompleted;
        if ($directApply) {
                OrderModifyApplier::apply('upload_images', $orderId, $adminOrderId, $orderNumber, $newData);
                $this->success('保存成功');
        }
        $logData = [
            'admin_order_id'    => $adminOrderId,
            'order_id'         => $orderId,
            'orderid'          => $orderNumber,
            'modify_type'      => 'upload_images',
            'title'            => '上传装卸货图片',
            'old_data'         => [],
            'new_data'         => $newData,
            'admin_id'         => $this->auth->id,
            'admin_name'       => $adminName,
            'logistics_status' => $logisticsStatus,
            'remark'           => '',
            'audit_status'     => OrderModifyLog::AUDIT_PENDING,
        ];
        OrderModifyLog::add($logData);
        $this->success('已提交，等待总后台审核');
    }
    /**
     * 重新计算订单总成本 cost_cont（与 OrderModifyApplier::recalcCostCont 一致）
     * @param int $orderId order 表主键 id
     */
    private function recalcOrderCostCont($orderId)
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            return;
        }
        OrderModifyApplier::recalcCostCont($orderId);
    }

    /**
     * 路线变更提醒：获取当前管理员未读列表（供调度端轮询，实现实时提醒）
     * @return Json
     */
    public function route_change_alerts()
    {
        $adminId = $this->auth->id;
        $list = Db::name('admin_route_change_alert')
            ->where('admin_id', $adminId)
            ->where('is_read', 0)
            ->order('createtime', 'desc')
            ->select();
        return json(['code' => 1, 'msg' => '', 'data' => ['list' => $list ?: []]]);
    }

    /**
     * 路线变更提醒：标记为已读（播报/点击后调用）
     * @return Json
     */
    public function mark_route_change_read()
    {
        $ids = $this->request->param('ids');
        if (is_string($ids)) {
            $ids = array_filter(array_map('intval', explode(',', $ids)));
        }
        if (!is_array($ids) || empty($ids)) {
            $this->error('参数错误');
        }
        $adminId = $this->auth->id;
        Db::name('admin_route_change_alert')
            ->where('admin_id', $adminId)
            ->where('id', 'in', $ids)
            ->update(['is_read' => 1]);
        $this->success('已读');
    }

    /**
     * 新单语音提醒：线路端有新的可抢订单 / 调度端有线路确认后的待接单，则返回 has_new 供前端播报
     * @return Json
     */
    public function new_order_voice_alert()
    {
        $admin_id = $this->auth->id;
        $admin_group = AdminUserBind::getPrimaryBusinessGroupIdForAdmin($admin_id);
        $group_identity = AdminUserBind::resolveEffectiveOrderRoleIdentity((int)$admin_group);

        if (!in_array($group_identity, [2, 3], true)) {
            return json(['code' => 1, 'data' => ['has_new' => 0, 'latest_id' => 0, 'message' => '', 'voice_on' => 0]]);
        }

        $scopeBindRegional = AdminUserBind::orderListUsesBindRegionalScope($group_identity);
        if (AdminUserBind::lineDispatchParentIsSuperAdminGroup((int)$admin_group, $group_identity)) {
            $scopeBindRegional = false;
        }

        $last_id = intval($this->request->get('last_id', 0));
        $init = (int)$this->request->get('init', 0);

        $has_new = 0;
        $latest_id = 0;
        $message = '';

        $grabbedOrderIds = [];
        if ($scopeBindRegional || AdminUserBind::lineDispatchParentIsSuperAdminGroup((int)$admin_group, $group_identity)) {
            $grabbedOrderIds = ((int)$group_identity === 3)
                ? AdminUserBind::getOrderIdsGrabbedByDispatchRole()
                : AdminUserBind::getAllGrabbedOrderIds();
        }

        if ($group_identity == 2) {
            $query = Db::name('order')
                ->where('pay_status', '<>', 5)
                ->whereNotIn('pay_status', [4, 8])
                ->where('backend_status', 1);
            $alertMessage = '有新的可抢订单，请及时处理';
        } else {
            $query = Db::name('order')
                ->where('pay_status', '<>', 5)
                ->whereNotIn('pay_status', [4, 8])
                ->where('backend_status', 2)
                ->where('find_car_type', '配车');
            $alertMessage = '有线路确认的订单待接单，请及时处理';
        }

        if (!empty($grabbedOrderIds)) {
            $query->whereNotIn('id', $grabbedOrderIds);
        }

        if ($scopeBindRegional) {
            $scopeAgentGroupId = AdminUserBind::getOrderScopeAgentGroupId((int)$admin_group, $group_identity);
            $frFranchise = \app\admin\library\FranchiseService::getFranchiseByGroupId($scopeAgentGroupId);
            if ($group_identity == 2) {
                $restrictFindCarPeiChe = true;
                if ($frFranchise) {
                    $restrictFindCarPeiChe = false; // 加盟商的线路：不限找车类型
                } else {
                    $scopeGroupCity = Db::name('auth_group')->where('id', $scopeAgentGroupId)->value('city');
                    $scopeAreaSegs = AdminUserBind::parseAuthGroupSlashCityNames($scopeGroupCity);
                    if ($scopeAreaSegs !== null && count($scopeAreaSegs) === 3) {
                        $restrictFindCarPeiChe = false;
                    }
                }
                if ($restrictFindCarPeiChe) {
                    $query->where('find_car_type', '配车');
                }
            }
            $staffBindUserIds = AdminUserBind::getBoundUserIdsForLineDispatchRole((int)$admin_group, $group_identity, (int)$admin_id);
            if ($staffBindUserIds !== null) {
                if ($frFranchise) {
                    // 加盟商的调度/线路：按加盟商范围（绑定会员 + 多区域未绑定普通用户）
                    \app\admin\library\FranchiseService::applyFranchiseOrderScope($query, (int)$admin_id);
                } else {
                    $regionalAdmins = AdminUserBind::getRegionalSourceAdminIdsForOrderScope((int)$admin_group, $group_identity, (int)$admin_id);
                    AdminUserBind::applyBoundOrRegionalScopeToOrderModelQuery($query, $staffBindUserIds, $regionalAdmins, $scopeAgentGroupId);
                }
            }
        }

        $row = $query->order('id', 'desc')->field('id')->find();
        if ($row) {
            $latest_id = intval($row['id']);
            // init=1：前端首次轮询仅同步基线，公海已有历史单不播报；之后仅 id 变大才播报
            if ($init !== 1 && $latest_id > $last_id) {
                $has_new = 1;
                $message = $alertMessage;
            }
        }

        // 执行时直接开启语音提醒：随每次轮询返回 voice_on=1，前端据此自动解锁播报，无需手动点击
        return json(['code' => 1, 'data' => ['has_new' => $has_new, 'latest_id' => $latest_id, 'message' => $message, 'voice_on' => 1]]);
    }
}
