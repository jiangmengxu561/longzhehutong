<?php

namespace app\admin\controller;

use app\admin\library\AdminUserBind;
use app\admin\library\FranchiseService;
use app\admin\library\OrderModifyApplier;
use app\admin\library\traits\OrderRejectBlockTrait;
use app\common\controller\Backend;
use app\common\model\User;
use app\common\model\MoneyLog;
use think\Cache;
use think\Config;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\response\Json;

/**
 * 订单管理
 *
 * @icon fa fa-circle-o
 */
class Order extends Backend
{
    use OrderRejectBlockTrait;
 
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    /**
     * Order模型对象
     * @var \app\admin\model\Order
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Order;
        $this->view->assign("findCarTypeList", $this->model->getFindCarTypeList());
        $this->view->assign("isinvoiceList", $this->model->getIsinvoiceList());
        $this->view->assign("payTypeList", $this->model->getPayTypeList());
        $this->view->assign("deliveryList", $this->model->getDeliveryList());
        $this->view->assign("isrequirementsList", $this->model->getIsrequirementsList());
        $this->view->assign("serviceList", $this->model->getServiceList());
        $this->view->assign("controlList", $this->model->getControlList());
        $this->view->assign("textMessageList", $this->model->getTextMessageList());
        $this->view->assign("payStatusList", $this->model->getPayStatusList());
        $this->view->assign("logisticsStatusList", $this->model->getLogisticsStatusList());
        $this->view->assign("getPayPartyList", $this->model->getPaypartyList());
    }

    /**
     * 总后台 / 财务 / 超级管理员：订单列表不按绑定与区域裁剪（与本页 statistics 区块权限一致）
     */
    protected function orderListBypassBindRegionalScope(): bool
    {
        return $this->auth->isSuperAdmin()
            || in_array(1, $this->auth->getGroupIds(), true)
            || in_array(30, $this->auth->getGroupIds(), true);
    }

    /**
     * 公司利润计算：
     * 兼职(membertype=2)：公司只赚总运费的5%抽佣(platform_commission)
     * 正式(membertype=1)：公司拿订单毛利(总运费-总成本)的40%
     */
    protected function calcCompanyProfit($memberType, $payPrice, $costCont, $platformCommission)
    {
        $memberType = (int)$memberType;
        $payPrice = (float)$payPrice;
        $costCont = (float)$costCont;
        if ($memberType === 2) {
            return round((float)$platformCommission, 2);
        }
        return round(($payPrice - $costCont) * 0.4, 2);
    }

    /**
     * 订单列表/统计/导出等与管理员同范围：含「角色组祖先链含超级管理组的线路/调度」（全国单、不按区域）；抢单等仍仅用 orderListBypassBindRegionalScope()
     */
    protected function orderListTreatAsUnscopedAdminView(int $adminGroup, int $groupIdentity): bool
    {
        if ($this->orderListBypassBindRegionalScope()) {
            return true;
        }

        return AdminUserBind::lineDispatchParentIsSuperAdminGroup($adminGroup, $groupIdentity);
    }

    /**
     * 抢单归属：批量查这批订单被哪些「线路(identity=2)」「调度(identity=3)」账号抢过。
     * 数据来源 fa_admin_order（一个订单可能被多个角色组分别抢过），返回
     * [订单id => ['line' => '张三(13800000000)', 'dispatch' => '李四(13900000000)']]
     *
     * @param int[] $orderIds
     * @return array<int,array{line:string,dispatch:string}>
     */
    protected function buildOrderGrabberMap(array $orderIds): array
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', (array)$orderIds))));
        if ($orderIds === []) {
            return [];
        }
        $rows = Db::name('admin_order')
            ->where('order_id', 'in', $orderIds)
            ->field('order_id,group_id,admin_id,createtime')
            ->order('id asc')
            ->select();
        if (!$rows) {
            return [];
        }
        $adminIds = array_values(array_unique(array_map('intval', array_column($rows, 'admin_id'))));
        $adminMap = [];
        if ($adminIds !== []) {
            foreach (Db::name('admin')->where('id', 'in', $adminIds)->field('id,nickname,username,mobile')->select() as $a) {
                $adminMap[(int)$a['id']] = $a;
            }
        }
        $identityCache = [];
        $collected = [];
        foreach ($rows as $r) {
            $identity = $this->resolveGrabberRoleIdentity((int)($r['group_id'] ?? 0), $identityCache);
            if (!in_array($identity, [2, 3], true)) {
                continue; // 只展示线路/调度，代理(1)等其它身份不显示
            }
            $key = $identity === 2 ? 'line' : 'dispatch';
            $admin = $adminMap[(int)$r['admin_id']] ?? [];
            $name = trim((string)($admin['nickname'] ?? ''));
            if ($name === '') {
                $name = trim((string)($admin['username'] ?? ''));
            }
            $mobile = trim((string)($admin['mobile'] ?? ''));
            if ($name === '' && $mobile === '') {
                $name = 'ID:' . (int)$r['admin_id'];
            }
            $label = $mobile !== '' ? $name . '(' . $mobile . ')' : $name;
            $collected[(int)$r['order_id']][$key][] = $label;
        }
        $map = [];
        foreach ($collected as $oid => $g) {
            $map[$oid] = [
                'line'     => implode('、', array_values(array_unique($g['line'] ?? []))),
                'dispatch' => implode('、', array_values(array_unique($g['dispatch'] ?? []))),
            ];
        }

        return $map;
    }

    /**
     * 解析抢单记录 group_id 对应的业务身份（子组未填 identity 时沿 pid 继承），带本地缓存避免重复查库
     *
     * @param array<int,int> $cache
     */
    protected function resolveGrabberRoleIdentity(int $groupId, array &$cache): int
    {
        if ($groupId <= 0) {
            return 0;
        }
        if (!array_key_exists($groupId, $cache)) {
            $cache[$groupId] = (int)AdminUserBind::resolveEffectiveOrderRoleIdentity($groupId);
        }

        return $cache[$groupId];
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
//        print_r($this->request->filter(['strip_tags', 'trim']));die;
        if (false === $this->request->isAjax()) {
            // 获取当前用户的组ID与身份，用于前端判断按钮显示及语音提醒
            $admin_id = $this->auth->id;
            $admin_group_id = AdminUserBind::getPrimaryBusinessGroupIdForAdmin($admin_id);
            $groupInfo = Db::name('auth_group')->where('id', $admin_group_id)->find();
            $group_identity = AdminUserBind::resolveEffectiveOrderRoleIdentity((int)$admin_group_id);
            $voice_identity = $group_identity;
            if ($this->orderListTreatAsUnscopedAdminView((int)$admin_group_id, $group_identity)) {
                $group_identity = 0;
                if (in_array(1, $this->auth->getGroupIds(), true)) {
                    $admin_group_id = 1;
                }
            }
            $this->assignconfig("admin", [
                'id' => $this->auth->id,
                'group_id' => $admin_group_id,
                'identity' => $group_identity,
                'voice_identity' => $voice_identity,
            ]);
            // 订单管理：加盟商筛选下拉数据
            $franchiseOptions = [];
            foreach (Db::name('franchise')->where('status', 'normal')->field('id,name,level,parent_id')->select() as $f) {
                $nm = trim((string)($f['name'] ?? ''));
                if ((int)$f['level'] === 1) {
                    $franchiseOptions[$f['id']] = $nm . '（一级）';
                } elseif ((int)$f['level'] === 2) {
                    $parent = '';
                    if ((int)$f['parent_id'] > 0) {
                        $parent = (string)Db::name('franchise')->where('id', (int)$f['parent_id'])->value('name');
                    }
                    $franchiseOptions[$f['id']] = $nm . '（二级' . ($parent !== '' ? '，一级：' . $parent : '') . '）';
                }
            }
            $this->view->assign('franchiseList', $franchiseOptions);
            // 将加盟商筛选选项注入前端 Config，供 JS 动态生成下拉框
            $this->assignconfig('franchiseList', $franchiseOptions);
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) { 

            return $this->selectpage();
        }
        // 所属加盟商是动态派生列，不在 order 表：在 buildparams 前从 filter/op 中摘除，避免 SQL 未知列
        $_frFilter = json_decode((string)$this->request->get('filter', ''), true) ?: [];
        $frDetailKw = (isset($_frFilter['franchise_detail']) && trim((string)$_frFilter['franchise_detail']) !== '')
            ? trim((string)$_frFilter['franchise_detail']) : null;
        if (isset($_frFilter['franchise_detail'])) {
            unset($_frFilter['franchise_detail']);
            $this->request->get(['filter' => json_encode($_frFilter)]);
            $_frOp = json_decode((string)$this->request->get('op', ''), true) ?: [];
            if (isset($_frOp['franchise_detail'])) {
                unset($_frOp['franchise_detail']);
                $this->request->get(['op' => json_encode($_frOp)]);
            }
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $admin_id = $this->auth->id;
        $admin_group = AdminUserBind::getPrimaryBusinessGroupIdForAdmin($admin_id);

        // 获取角色组信息并通过身份ID判断 backend_status（子组未填 identity 时沿 pid 继承）
        $groupInfo = Db::name('auth_group')->where('id', $admin_group)->find();
        $group_identity = AdminUserBind::resolveEffectiveOrderRoleIdentity((int)$admin_group);
        $scopeBindRegional = AdminUserBind::orderListUsesBindRegionalScope($group_identity);
        if ($this->orderListTreatAsUnscopedAdminView((int)$admin_group, $group_identity)) {
            $scopeBindRegional = false;
        }
        $backend_status = '';
        if ($scopeBindRegional) {
            if ($group_identity == 2) {
                $backend_status = 1; // 路线/规划身份
            } elseif ($group_identity == 3) {
                $backend_status = 2; // 调度身份
            }
        }

        /**
         * 处理按“下单人姓名/手机号”搜索的情况
         *
         * 思路：
         * 1. 从请求的 filter(JSON) 里单独取出 username 或 username|mobile 的搜索值
         * 2. 在 user 表上按 username|mobile 做 LIKE 查询，拿到所有匹配的 user.id 列表
         * 3. 从 $where 中移除 username 相关条件，防止在 order 表上出现未知字段
         * 4. 在订单查询中追加 whereIn('userid', $userIds)
         */
        $userIds = null;  // null 表示没有按下单人筛选
  
        // 1. 读取前端传来的筛选条件
        $filterRaw = $this->request->get("filter", '');
        $filterArr = (array)json_decode($filterRaw, true);
        $keyword   = null;
        if (isset($filterArr['username']) && $filterArr['username'] !== '') { 
            $keyword = $filterArr['username'];
        } elseif (isset($filterArr['username|mobile']) && $filterArr['username|mobile'] !== '') {
            $keyword = $filterArr['username|mobile']; 
        }
 
        if ($keyword !== null) {
            // 2. 在 user 表中查出所有匹配的用户ID（用户名或手机号包含关键字）
            $userIds = Db::name('user')
                ->where('username|mobile', 'LIKE', "%{$keyword}%")
                ->column('id');

            // 3. 从 $where 中移除 username / username|mobile 相关条件
            if (is_array($where)) { 
                $cleanWhere = [];
                foreach ($where as $condition) {
                    if (is_array($condition) && !empty($condition[0]) && is_string($condition[0])) {
                        $field = $condition[0];
                        $fieldName = preg_replace('/^[a-zA-Z_]+\./', '', $field);
                        if ($fieldName === 'username' || $fieldName === 'username|mobile') {
                            continue;
                        }
                    }
                    $cleanWhere[] = $condition;
                }
                $where = $cleanWhere;
            }
        }

        /**
         * 处理按“收货地址姓名/公司名”搜索的情况（装货或卸货地址的 user_name 或 company_name 包含关键字）
         */
        $addressContactIds = null;
        $addressContactKeyword = isset($filterArr['address_contact']) && trim($filterArr['address_contact']) !== ''
            ? trim($filterArr['address_contact']) : null;


        if ($addressContactKeyword !== null) {
            $addressContactIds = Db::name('user_address')
                ->where(function ($q) use ($addressContactKeyword) {
                    $q->where('user_name', 'LIKE', "%{$addressContactKeyword}%")
                        ->whereOr('company_name', 'LIKE', "%{$addressContactKeyword}%");
                })
                ->column('id');
            if (is_array($where)) {
                $cleanWhere = [];
                foreach ($where as $condition) {
                    if (is_array($condition) && !empty($condition[0]) && is_string($condition[0])) {
                        $fieldName = preg_replace('/^[a-zA-Z_]+\./', '', $condition[0]);
                        if ($fieldName === 'address_contact') {
                            continue;
                        }
                    }
                    $cleanWhere[] = $condition;
                }
                $where = $cleanWhere;
            }
        }

        $franchiseDetailKeyword = $frDetailKw;
        // 所属加盟商是动态派生字段：已在上方 buildparams 前从 filter/op 中摘除，故此处无需再改 $where
        // 线路/代理等：任意 admin_order 即不出公海；调度：仅「被调度身份抢过」不出公海；调度可见性还须 backend_status=2（无区域裁剪分支已统一限制）
        $grabbedOrderIds = [];
        if ($scopeBindRegional || AdminUserBind::lineDispatchParentIsSuperAdminGroup((int)$admin_group, $group_identity)) {
            $grabbedOrderIds = ((int)$group_identity === 3)
                ? AdminUserBind::getOrderIdsGrabbedByDispatchRole()
                : AdminUserBind::getAllGrabbedOrderIds();
        }
        // 加盟商管理员：像总后台一样看“自己+下级+区域”全部订单（含已抢），不排除已抢单
        if (FranchiseService::getFranchiseByAdminId((int)$admin_id)) {
            $grabbedOrderIds = [];
        }

       if (!$scopeBindRegional){
           // 总后台等非业务身份：不按绑定/区域限制；不看待下单(5)、已取消(4)、驳回(8)
           $query = $this->model->where($where)->where('pay_status', '<>', 5)->whereNotIn('pay_status', [4, 8]);
           if ($userIds !== null) {
               // 如果没有找到任何匹配的用户ID，直接返回空结果
               if (empty($userIds)) {
                   $query->where('id', -1);
               } else {
                   $query->whereIn('userid', $userIds);
               }
           }
           if ($addressContactIds !== null) {
               if (empty($addressContactIds)) {
                   $query->where('id', -1);
               } else {
                   $query->where(function ($q) use ($addressContactIds) {
                       $q->whereIn('loading', $addressContactIds)->whereOr('unload', 'in', $addressContactIds);
                   });
               }
           }
           if (!empty($grabbedOrderIds)) {
               $query->whereNotIn('id', $grabbedOrderIds);
           }
           // 全国视角等无区域裁剪时，调度仍只看配车；且须线路已确认(backend_status=2)，与公海一致
           if ((int)$group_identity === 3) {
               $query->where('find_car_type', '配车')->where('backend_status', 2);
           }
           // 订单管理：按加盟商筛选
           $filterFranchiseId = (int)$this->request->get('franchise_id', 0);
             if ($filterFranchiseId > 0) {
                 FranchiseService::applyFranchiseOrderScopeById($query, $filterFranchiseId);
             }
             // 按「所属加盟商」名称搜索：仅显示命中加盟商（含其二级）范围内的订单
             if ($franchiseDetailKeyword !== null) {
                 FranchiseService::applyFranchiseOrderScopeByKeyword($query, $franchiseDetailKeyword);
             }
             $list = $query->order($sort, $order)->paginate($limit);
       }else{
            // 代理/线路/调度：不看状态为4或8的订单；应用搜索条件 + 绑定/区域权限
            $scopeAgentGroupId = AdminUserBind::getOrderScopeAgentGroupId((int)$admin_group, $group_identity);
            $query = $this->model->where($where)->where('pay_status', '<>', 5)->whereNotIn('pay_status', [4, 8]);
            // 省/市代理组仍限配车；区/县（city 三段）下代理与线路可看专车、小票快运、配车；调度始终仅配车
            $isFranchiseAccount = FranchiseService::resolveFranchiseForAdmin((int)$admin_id) !== null;
            $isHqLineDispatch = FranchiseService::isHqLineDispatch((int)$admin_id);
            $restrictFindCarPeiChe = true;
            if ($isFranchiseAccount) {
                $restrictFindCarPeiChe = false; // 加盟商体系账号（含线路/调度/财务）不限制找车类型，按总后台口径看全量
            } elseif ($isHqLineDispatch) {
                $restrictFindCarPeiChe = false; // 总部直属线路不限制找车类型，按总后台口径看全量
            }
            $scopeGroupCity = Db::name('auth_group')->where('id', $scopeAgentGroupId)->value('city');
            $scopeAreaSegs = AdminUserBind::parseAuthGroupSlashCityNames($scopeGroupCity);
            if ($scopeAreaSegs !== null && count($scopeAreaSegs) === 3) {
                $restrictFindCarPeiChe = false;
            }
            if ((int)$group_identity === 3) {
                $query->where('find_car_type', '配车');
            } elseif ($restrictFindCarPeiChe) {
                $query->where('find_car_type', '配车');
            }
            if (!empty($backend_status)) {
                $query->where('backend_status', $backend_status);
            }
            if (!empty($grabbedOrderIds)) {
                $query->whereNotIn('id', $grabbedOrderIds);
            }
             if ($isFranchiseAccount) {
                 // 加盟商体系账号（含线路/调度/财务）：按加盟商范围（绑定会员 或 区域内未绑定普通用户）
                 FranchiseService::applyFranchiseOrderScope($query, (int)$admin_id);
             } elseif ($isHqLineDispatch) {
                 // 总部直属线路/调度：总部绑定用户 + 无加盟商区域内的未绑定普通用户
                 FranchiseService::applyHqOrderScope($query, (int)$admin_id);
             } else {
                // 线路/调度：绑定会员订单 OR 上级代理组 auth_group.city 区域（仅装货）
                $staffBindUserIds = AdminUserBind::getBoundUserIdsForLineDispatchRole((int)$admin_group, $group_identity, (int)$admin_id);
                if ($staffBindUserIds !== null) {
                    $regionalAdmins = AdminUserBind::getRegionalSourceAdminIdsForOrderScope((int)$admin_group, $group_identity, (int)$admin_id);
                    AdminUserBind::applyBoundOrRegionalScopeToOrderModelQuery($query, $staffBindUserIds, $regionalAdmins, $scopeAgentGroupId);
                }
                // 代理：本代理组绑定会员 OR auth_group.city 区域（区/县用市+区匹配；区域公海须未绑定或绑定在本代理子树）
                if ($group_identity === AdminUserBind::AUTH_GROUP_IDENTITY_AGENT) {
                    $agentBindUserIds = AdminUserBind::getBoundUserIdsForAgentGroup($scopeAgentGroupId);
                    $regionalAdmins = AdminUserBind::getRegionalSourceAdminIdsForOrderScope((int)$admin_group, $group_identity, (int)$admin_id);
                    AdminUserBind::applyBoundOrRegionalScopeToOrderModelQuery($query, $agentBindUserIds, $regionalAdmins, $scopeAgentGroupId);
                }
            }
           if ($userIds !== null) {
               if (empty($userIds)) {
                   $query->where('id', -1);
               } else {
                   $query->whereIn('userid', $userIds);
               }
           }
           if ($addressContactIds !== null) {
               if (empty($addressContactIds)) {
                   $query->where('id', -1);
               } else {
                   $query->where(function ($q) use ($addressContactIds) {
                       $q->whereIn('loading', $addressContactIds)->whereOr('unload', 'in', $addressContactIds);
                   });
               }
           }
           // 订单管理：按加盟商筛选
           $filterFranchiseId = (int)$this->request->get('franchise_id', 0);
           if ($filterFranchiseId > 0) {
               FranchiseService::applyFranchiseOrderScopeById($query, $filterFranchiseId);
           }
           // 按「所属加盟商」名称搜索：仅显示命中加盟商（含其二级）范围内的订单
           if ($franchiseDetailKeyword !== null) {
               FranchiseService::applyFranchiseOrderScopeByKeyword($query, $franchiseDetailKeyword);
           }
           $list = $query->order($sort, $order)->paginate($limit);
       }
         // 本页订单的抢单归属（哪个线路、哪个调度抢的），用于总后台订单列表展示
         $pageOrderIds = [];
         foreach ($list as $v) {
             $pageOrderIds[] = (int)$v['id'];
         }
         $grabberMap = $this->buildOrderGrabberMap($pageOrderIds);

         // 批量预取本页订单的关联数据（地址、下单人、付款方式、各字典表、取送货运费税点），
         // 避免在下面循环里逐行查库（原实现每行约 14 次查询，是列表加载慢的主因之一）
         $dictFieldTableMap = [
             'goods_type_id'    => 'goods_type',
             'packaging_id'     => 'packaging',
             'car_type_id'      => 'car_type',
             'delivery_type_id' => 'delivery_type',
             'receipt_type_id'  => 'receipt_type',
             'unpack_id'        => 'unpack',
             'other_id'         => 'other',
         ];
         $pageAddressIds = [];
         $pageUserIds = [];
         $pagePaymentIds = [];
         $pageOrderNos = [];
         $dictIds = [];
         foreach ($dictFieldTableMap as $dictTable) {
             $dictIds[$dictTable] = [];
         }
         foreach ($list as $v) {
             foreach (['loading', 'unload'] as $addrField) {
                 $addrId = (int)($v[$addrField] ?? 0);
                 if ($addrId > 0) {
                     $pageAddressIds[$addrId] = $addrId;
                 }
             }
             $userId = (int)($v['userid'] ?? 0);
             if ($userId > 0) {
                 $pageUserIds[$userId] = $userId;
             }
             $paymentId = (int)($v['payment_method_id'] ?? 0);
             if ($paymentId > 0) {
                 $pagePaymentIds[$paymentId] = $paymentId;
             }
             $orderNo = trim((string)($v['orderid'] ?? ''));
             if ($orderNo !== '') {
                 $pageOrderNos[$orderNo] = $orderNo;
             }
             foreach ($dictFieldTableMap as $dictField => $dictTable) {
                 $dictId = (int)($v[$dictField] ?? 0);
                 if ($dictId > 0) {
                     $dictIds[$dictTable][$dictId] = $dictId;
                 }
             }
         }

         $addressMap = [];
         if ($pageAddressIds !== []) {
             foreach (Db::name('user_address')->whereIn('id', array_values($pageAddressIds))->select() as $addrRow) {
                 $addressMap[(int)$addrRow['id']] = $addrRow;
             }
         }
         $userDisplayMap = [];
         if ($pageUserIds !== []) {
             foreach (Db::name('user')->whereIn('id', array_values($pageUserIds))->field('id,username,mobile')->select() as $userRow) {
                 $userDisplayMap[(int)$userRow['id']] = $userRow;
             }
         }
         // 付款方式：主分类 + 上级分类名称（前端展示为「上级---本级」）
         $paymentMap = [];
         if ($pagePaymentIds !== []) {
             $parentPaymentIds = [];
             foreach (Db::name('payment_method')->whereIn('id', array_values($pagePaymentIds))->select() as $pmRow) {
                 $paymentMap[(int)$pmRow['id']] = $pmRow;
                 $pmParentId = (int)($pmRow['p_id'] ?? 0);
                 if ($pmParentId > 0) {
                     $parentPaymentIds[$pmParentId] = $pmParentId;
                 }
             }
             if ($parentPaymentIds !== []) {
                 foreach (Db::name('payment_method')->whereIn('id', array_values($parentPaymentIds))->select() as $pmRow) {
                     $paymentMap[(int)$pmRow['id']] = $pmRow;
                 }
             }
         }
         $dictNameMap = [];
         foreach ($dictFieldTableMap as $dictTable) {
             $dictNameMap[$dictTable] = [];
             if ($dictIds[$dictTable] === []) {
                 continue;
             }
             foreach (Db::name($dictTable)->whereIn('id', array_values($dictIds[$dictTable]))->field('id,name')->select() as $dictRow) {
                 $dictNameMap[$dictTable][(int)$dictRow['id']] = $dictRow['name'];
             }
         }
         // 下单人 -> 所属加盟商：整页一次预取（未绑定的订单仍按装货区域在下面逐行判断）
         FranchiseService::prefetchBoundUserFranchise(array_values($pageUserIds));
         // 取货(type=1)/送货(type=3)税点：按本页订单号一次取回
         $taxPointMap = [];
         if ($pageOrderNos !== []) {
             foreach (Db::name('dricerorder')
                 ->whereIn('order_id', array_values($pageOrderNos))
                 ->whereIn('type', [1, 3])
                 ->field('id,order_id,type,tax_point')
                 ->order('id asc')
                 ->select() as $taxRow) {
                 $taxKey = (string)$taxRow['order_id'];
                 $taxType = (int)$taxRow['type'];
                 if (!isset($taxPointMap[$taxKey][$taxType])) {
                     $taxPointMap[$taxKey][$taxType] = $taxRow['tax_point'];
                 }
             }
         }

         foreach ($list as $k=>$v){
             // 所属加盟商（订单管理展示 + 搜索）
             $orderFrInfo = FranchiseService::resolveOrderFranchiseInfo(is_object($v) ? $v->toArray() : (array)$v);
             $list[$k]['franchise_id'] = $orderFrInfo['franchise_id'];
             $list[$k]['franchise_name'] = $orderFrInfo['franchise_name'];
             $list[$k]['franchise_level'] = $orderFrInfo['franchise_level'];
             $list[$k]['franchise_detail'] = $orderFrInfo['franchise_detail'];
             // 抢单归属：线路 / 调度
             $grabber = $grabberMap[(int)$v['id']] ?? [];
             $list[$k]['grab_line'] = (string)($grabber['line'] ?? '');
             $list[$k]['grab_dispatch'] = (string)($grabber['dispatch'] ?? '');
             $loading_address = $addressMap[(int)($v['loading'] ?? 0)] ?? [];
             $unload_address = $addressMap[(int)($v['unload'] ?? 0)] ?? [];
             if ($loading_address){
                 $list[$k]['loading'] = $loading_address['user_name'].'-'.$loading_address['mobile'].'-'.$loading_address['address'].'-'.'-'.$loading_address['detailed_address'].'-';
             }
             if ($unload_address){
                 $list[$k]['unload'] = $unload_address['user_name'].'-'.$unload_address['mobile'].'-'.$unload_address['address'].'-'.'-'.$unload_address['detailed_address'].'-';
             }
             if (empty($loading_address['detailed_address'])){
                 $loading_address['detailed_address'] = $loading_address['address'] ?? '';
             }
             if (empty($unload_address['detailed_address'])){
                 $unload_address['detailed_address'] = $unload_address['address'] ?? '';
             }
             $list[$k]['order_address'] = extractProvinceCityEnhanced($loading_address['detailed_address']).'---'.extractProvinceCityEnhanced($unload_address['detailed_address']);
             $ln = ($loading_address && isset($loading_address['user_name'])) ? $loading_address['user_name'] : '';
             $lnCompany = ($loading_address && !empty($loading_address['company_name'])) ? $loading_address['company_name'] : '';
             $un = ($unload_address && isset($unload_address['user_name'])) ? $unload_address['user_name'] : '';
             $unCompany = ($unload_address && !empty($unload_address['company_name'])) ? $unload_address['company_name'] : '';
             $list[$k]['address_contact'] = trim(implode(' ', array_filter([$ln, $lnCompany, $un, $unCompany])));
             $payment_method = $paymentMap[(int)($v['payment_method_id'] ?? 0)] ?? null;
             if ($payment_method && (int)($payment_method['p_id'] ?? 0) > 0 ) {
                 $p_name = $paymentMap[(int)$payment_method['p_id']]['payment_method'] ?? '';
                 $list[$k]['payment_method'] = $p_name .'---'.$payment_method['payment_method'];
             }else{
                 if ($payment_method){
                     $list[$k]['payment_method'] = $payment_method['payment_method'];
                 } 
             }
             $list[$k]['goods_type_id']    =   $dictNameMap['goods_type'][(int)($v['goods_type_id'] ?? 0)] ?? null;
             $list[$k]['packaging_id']     =   $dictNameMap['packaging'][(int)($v['packaging_id'] ?? 0)] ?? null;
             $list[$k]['car_type_id']      =   $dictNameMap['car_type'][(int)($v['car_type_id'] ?? 0)] ?? null;
             $list[$k]['delivery_type_id'] =   $dictNameMap['delivery_type'][(int)($v['delivery_type_id'] ?? 0)] ?? null;
             $list[$k]['receipt_type_id_value'] = $v['receipt_type_id']; // 保留原始 id，供前端“填写寄回单号”等判断用
             $list[$k]['receipt_type_id']  =   $dictNameMap['receipt_type'][(int)($v['receipt_type_id'] ?? 0)] ?? null;
             $list[$k]['unpack_id']        =   $dictNameMap['unpack'][(int)($v['unpack_id'] ?? 0)] ?? null;
             $list[$k]['other_id']         =   $dictNameMap['other'][(int)($v['other_id'] ?? 0)] ?? null;
             // 通过订单表的 userid 关联到 user 表，获取下单人姓名和手机号
             $list[$k]['username']         =   $userDisplayMap[(int)($v['userid'] ?? 0)]['username'] ?? null;
             $list[$k]['mobile']           =   $userDisplayMap[(int)($v['userid'] ?? 0)]['mobile'] ?? null;
             $orderNoKey = trim((string)($v['orderid'] ?? ''));
             $list[$k]['pickup_tax_point'] = $taxPointMap[$orderNoKey][1] ?? null;
             $list[$k]['shipment_tax_point'] = $taxPointMap[$orderNoKey][3] ?? null;
             $payPrice = isset($v['pay_price']) ? floatval($v['pay_price']) : 0;

            // 订单利润：总运费(pay_price) - 总成本(cost_cont)
            // 仅总后台/财务(超管/组1/组30)可见
            if ($this->orderListBypassBindRegionalScope()) {
                $list[$k]['profit'] = round($payPrice - (float)$v['cost_cont'], 2);
            }
            $timeout = (time()-$v['createtime']) /60;
            if ($timeout >=15){
                $list[$k]['timeout'] = '超时'; 
            }else{
                $list[$k]['timeout'] = '未超时';
            } 
        }
        $listArray = $list->toArray(); // 转换分页对象为数组

        // 超级管理员/财务/与管理员同列表范围的线路调度（见 orderListTreatAsUnscopedAdminView）可见六项统计
        $statistics = null;
        // 这里假设财务角色的 group_id 为 30，如有变更请同步修改
        if ($this->orderListTreatAsUnscopedAdminView((int)$admin_group, $group_identity)) {
            // 计算筛选后的所有订单统计信息（不限制分页）
            $statisticsQuery = null;
            // 超级管理员/财务：直接在订单表上做统计，如有按下单人筛选则同样通过 userid IN (...) 过滤
            $statisticsQuery = $this->model->where($where)
                ->where('pay_status', 3);
            if ($userIds !== null) {
                if (empty($userIds)) {
                    $statisticsQuery->where('id', -1);
                } else {
                    $statisticsQuery->whereIn('userid', $userIds);
                }
            }
            if ($addressContactIds !== null) {
                if (empty($addressContactIds)) {
                    $statisticsQuery->where('id', -1);
                } else {
                    $statisticsQuery->where(function ($q) use ($addressContactIds) {
                        $q->whereIn('loading', $addressContactIds)->whereOr('unload', 'in', $addressContactIds);
                    });
                }
            }
            if (!empty($grabbedOrderIds)) {
                $statisticsQuery->whereNotIn('id', $grabbedOrderIds);
            }
            if ((int)$group_identity === 3) {
                $statisticsQuery->where('find_car_type', '配车')->where('backend_status', 2);
            }
            // 获取所有符合条件的订单用于统计
            $allOrders = $statisticsQuery->select();

            // 计算统计信息（公司利润口径：兼职取平台抽佣，正式取毛利40%）
            $totalIncome = 0;      // 总收入（总运费 pay_price）
            $totalExpense = 0;     // 总成本（cost_cont）
            $totalProfit = 0;      // 公司利润
            $totalWeight = 0;      // 总吨数
            $totalVolume = 0;      // 总方数
            $totalOrders = 0;      // 总单量
            foreach ($allOrders as $order) {
                $payPrice = isset($order['pay_price']) ? floatval($order['pay_price']) : 0;
                $costCont = isset($order['cost_cont']) ? floatval($order['cost_cont']) : 0;

                // 统计口径：订单已完成(pay_status=3) 且 利润(pay_price - cost_cont)不为负
                // 利润为负的订单整单不统计（金额、吨数、方数、单量均不计入）
                if (round($payPrice - $costCont, 2) < 0) {
                    continue;
                }

                $totalOrders++;
                $totalWeight += isset($order['weight']) ? floatval($order['weight']) : 0;
                $totalVolume += isset($order['direction']) ? floatval($order['direction']) : 0;
                $totalIncome += $payPrice;
                $totalExpense += $costCont;
                // 订单总利润 = 总运费(pay_price) - 总成本(cost_cont)
                $totalProfit += ($payPrice - $costCont);
            }

            $totalProfit = round($totalProfit, 2);

            $statistics = [ 
                'total_income' => round($totalIncome, 2),    // 总收入
                'total_expense' => round($totalExpense, 2),  // 总支出
                'total_profit' => round($totalProfit, 2),    // 利润
                'total_weight' => round($totalWeight, 2),    // 总吨数（weight 单位已为吨）
                'total_volume' => round($totalVolume, 2),    // 总方数
                'total_orders' => $totalOrders               // 总单量
            ];
        }
        $result = [
            'total' => $listArray['total'], // 总记录数不变
            'rows' => $listArray['data'], // 当前页数据（与分页一致）
            'statistics' => $statistics,
        ];
        return json($result);
    }

    /**
     * 查询某个用户的下单情况（页面 + 列表接口）
     * @param int|null $ids 用户ID
     * @return string|Json
     * @throws DbException
     * @throws \think\Exception
     */
    public function user_orders($ids = null)
    {
        $userId = $ids ?: $this->request->param('ids');

//        print_r($userId);die;
        if (empty($userId)) {
            $this->error('请指定用户');
        }
        $user = Db::name('user')->where('id', $userId)->find();
        if (!$user) {
            $this->error('用户不存在');
        }
        if (!$this->auth->isSuperAdmin() && !AdminUserBind::canManageMember($this->auth, $userId)) {
            $this->error(__('You have no permission'));
        }

        if (false === $this->request->isAjax()) {
            $this->view->assign('user_id', $userId);
            $this->view->assign('user', $user);
            return $this->view->fetch('user_orders');
        }

        // Ajax：按该用户筛选订单，复用 index 的列表逻辑但强制 userid
        $this->request->filter(['strip_tags', 'trim']);
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $admin_id = $this->auth->id;
        $admin_group = AdminUserBind::getPrimaryBusinessGroupIdForAdmin($admin_id);
        $groupInfo = Db::name('auth_group')->where('id', $admin_group)->find();
        $query = $this->model->where($where)->where('userid', $userId);
        $list = $query->order($sort, $order)->paginate($limit);
        foreach ($list as $k => $v) {
            $list[$k]['lirun'] = round(($v['logistics_cost'] + $v['pickup_fee'] + $v['shipment_fee']) - ($v['logistics_driver_cost'] + $v['pickup_driver_fee'] + $v['shipment_driver_fee']));
            $loading_address = Db::name('user_address')->where('id', $v['loading'])->find();
            $unload_address = Db::name('user_address')->where('id', $v['unload'])->find();
            if ($loading_address) {
                $list[$k]['loading'] = $loading_address['user_name'] . '-' . $loading_address['mobile'] . '-' . $loading_address['address'] . '-' . '-' . $loading_address['detailed_address'] . '-';
            }
            if ($unload_address) {
                $list[$k]['unload'] = $unload_address['user_name'] . '-' . $unload_address['mobile'] . '-' . $unload_address['address'] . '-' . '-' . $unload_address['detailed_address'] . '-';
            }
            $list[$k]['goods_type_id'] = Db::name('goods_type')->where('id', $v['goods_type_id'])->value('name');
            $list[$k]['packaging_id'] = Db::name('packaging')->where('id', $v['packaging_id'])->value('name');
            $list[$k]['car_type_id'] = Db::name('car_type')->where('id', $v['car_type_id'])->value('name');
            $list[$k]['delivery_type_id'] = Db::name('delivery_type')->where('id', $v['delivery_type_id'])->value('name');
            $list[$k]['receipt_type_id'] = Db::name('receipt_type')->where('id', $v['receipt_type_id'])->value('name');
            $list[$k]['unpack_id'] = Db::name('unpack')->where('id', $v['unpack_id'])->value('name');
            $list[$k]['other_id'] = Db::name('other')->where('id', $v['other_id'])->value('name');
            $list[$k]['username'] = Db::name('user')->where('id', $v['userid'])->value('username');
            $list[$k]['mobile'] = Db::name('user')->where('id', $v['userid'])->value('mobile');
            $payTypeList = $this->model->getPayTypeList();
            $list[$k]['pay_type_text'] = isset($payTypeList[$v['pay_type']]) ? $payTypeList[$v['pay_type']] : '';
            $timeout = (time() - $v['createtime']) / 60;
        }
        $listArray = $list->toArray();
        $result = ['total' => $listArray['total'], 'rows' => $listArray['data']];
        return json($result);
    }

    /**
     * @return void
     * @throws DbException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\PDOException
     *
     * 抢单
     */
    public  function order_grabbing()
    {
        $admin_id = $this->auth->id;
        if ($this->orderListBypassBindRegionalScope()) {
            $this->error('当前账号不可抢单');
        }
        $data = $this->request->param('ids');
        $admin_group = AdminUserBind::getPrimaryBusinessGroupIdForAdmin($admin_id);
            // 获取角色组的完整信息（按身份ID判断backend_status）
            $groupInfo = Db::name('auth_group')->where('id', $admin_group)->find();
            $group_identity = AdminUserBind::resolveEffectiveOrderRoleIdentity((int)$admin_group);
            if ($group_identity != 2 && $group_identity != 3) {
                $this->error('仅线路与调度可以抢单');
            }

            // 当前订单信息
            $currentOrder = Db::name('order')->where('id', $data)->find();
            if (!$currentOrder) {
                throw new \Exception('订单不存在');
            }
            if ((int)$group_identity === 3 && (isset($currentOrder['find_car_type']) ? (string)$currentOrder['find_car_type'] : '') !== '配车') {
                $this->error('调度仅可抢配车订单');
            }
            $curPs = (int)($currentOrder['pay_status'] ?? 0);
            if (in_array($curPs, [4, 8], true)) {
                $this->error('该订单已取消或已驳回，不可抢单');
            }

            $staffBindUserIds = null;
            $regionalAdmins = [];
            $scopeAgentGroupId = 0;
            $fr = FranchiseService::resolveFranchiseForAdmin((int)$admin_id);
            if ($fr) {
                // 加盟商体系账号（线路/调度）：只能抢“本加盟商绑定会员订单 或 区域内未被任何加盟商绑定的订单”
                $isManager = ((int)$fr['admin_id'] === (int)$admin_id);
                if (!FranchiseService::canSeeOrder((int)$fr['id'], $currentOrder, $isManager)) {
                    $this->error('无权操作');
                }
            } else {
                $staffBindUserIds = null;
                $regionalAdmins = [];
                $scopeAgentGroupId = 0;
                if (FranchiseService::isHqLineDispatch((int)$admin_id)) {
                    // 总部直属线路/调度：按总部范围（总部绑定会员 + 未绑定且区域未被加盟商占用）
                    if (!FranchiseService::orderInHqScope($currentOrder, (int)$admin_id)) {
                        $this->error('无权操作');
                    }
                } else {
                    $staffBindUserIds = AdminUserBind::getBoundUserIdsForLineDispatchRole((int)$admin_group, $group_identity, (int)$admin_id);
                    $regionalAdmins = AdminUserBind::getRegionalSourceAdminIdsForOrderScope((int)$admin_group, $group_identity, (int)$admin_id);
                    $scopeAgentGroupId = AdminUserBind::getOrderScopeAgentGroupId((int)$admin_group, $group_identity);
                    $okBind = $staffBindUserIds !== null && $staffBindUserIds !== []
                        && in_array((int)$currentOrder['userid'], $staffBindUserIds, true);
                    $okRegion = AdminUserBind::orderMatchesAnyAgentRegionalScopes($currentOrder, $regionalAdmins, $scopeAgentGroupId);
                    $okNationalLineDispatch = AdminUserBind::lineDispatchParentIsSuperAdminGroup((int)$admin_group, $group_identity);
                    if (!$okBind && !$okRegion && !$okNationalLineDispatch) {
                        $this->error('无权操作');
                    }
                }
            }

            // 通过身份ID映射backend_status（与index方法保持一致）
            if ($group_identity == 2) {
                $backend_status = 1; // 路线/规划身份
            } elseif ($group_identity == 3) {
                $backend_status = 2; // 调度身份
            } else {
                $backend_status = 0;
            }
            $province = isset($groupInfo['province']) ? trim($groupInfo['province']) : '';
            $city = isset($groupInfo['city']) ? trim($groupInfo['city']) : '';
            $district = isset($groupInfo['district']) ? trim($groupInfo['district']) : '';

            // 与 index 公海一致：线路用「任意 admin_order」；调度用「仅调度身份抢单」
            $grabbedOrderIds = ((int)$group_identity === 3)
                ? AdminUserBind::getOrderIdsGrabbedByDispatchRole()
                : array_values(array_unique(array_map('intval', Db::name('admin_order')->column('order_id') ?: [])));

            // 构建当前子后台“可见范围”内、与公海排除规则一致的订单查询
            $earliestQuery = Db::name('order')
                ->alias('o')
                ->where('o.backend_status', $backend_status);

//            // 地区权限过滤（与index保持一致逻辑）
//            if ($admin_group != 1 && ($province || $city || $district)) {
//                $earliestQuery->join('user_address ua', 'o.loading = ua.id');
//                if ($district) {
//                    $earliestQuery->where('ua.detailed_address', 'like', '%' . $province . '%')
//                        ->where('ua.detailed_address', 'like', '%' . $city . '%')
//                        ->where('ua.detailed_address', 'like', '%' . $district . '%');
//                } elseif ($city) {
//                    $earliestQuery->where('ua.detailed_address', 'like', '%' . $province . '%')
//                        ->where('ua.detailed_address', 'like', '%' . $city . '%');
//                } elseif ($province) {
//                    $earliestQuery->where('ua.detailed_address', 'like', '%' . $province . '%');
//                }
//            }

            if (!empty($grabbedOrderIds)) {
                $earliestQuery->whereNotIn('o.id', $grabbedOrderIds);
            }
            $earliestQuery->where('o.pay_status', '<>', 5)->whereNotIn('o.pay_status', [4, 8]);
            if ((int)$group_identity === 3) {
                $earliestQuery->where('o.find_car_type', '配车');
            }
            if (FranchiseService::resolveFranchiseForAdmin((int)$admin_id) !== null) {
                // 加盟商体系账号：按加盟商范围（绑定会员 + 区域内未绑定用户）
                FranchiseService::applyFranchiseOrderScope($earliestQuery, (int)$admin_id, '`o`');
            } elseif ($staffBindUserIds !== null) {
                AdminUserBind::applyBoundOrRegionalScopeToAliasedOrderQuery(
                    $earliestQuery,
                    'o',
                    $staffBindUserIds,
                    $regionalAdmins,
                    $scopeAgentGroupId
                );
            }

            // 找到当前子后台可见范围内、最早的一个未被抢单的订单
            $earliestOrder = $earliestQuery
                ->order('o.createtime', 'asc')
                ->field('o.id,o.createtime')
                ->find();
//            print_r($earliestOrder);die;
            // 如果存在比当前订单更早、且没人抢的订单，则不允许抢当前订单
//            if ($earliestOrder && $earliestOrder['id'] != $currentOrder['id'] && $earliestOrder['createtime'] < $currentOrder['createtime']) {
//               $this->error('存在更早的未抢订单，请优先抢最早生成的订单');
//            }

//             检查订单是否已被抢单
            $res = Db::name('admin_order')
                   ->where('order_id', $data)
                   ->where('group_id',$admin_group)
                   ->find();
            if ($res) {
               $this->error('已经被别人抢走了');
            }
//            // 如果订单已被抢单，给出明确提示（避免重复插入导致失败）
//            $existingGrab = Db::name('admin_order')->where('order_id', $data)->find();
//            if ($existingGrab) {
//                if ($existingGrab['admin_id'] == $admin_id) {
//                    // 当前销售已经抢到该单，直接返回成功，避免重复提示失败
//                    Db::commit();
//                    $this->success('请勿重复抢单，该订单已归属当前账号');
//                } else {
//                    $this->error('该订单已被其他账号抢走');
//                }
//            }

            // 插入订单信息
            $arr = [
                'order_id' => $data,
                'admin_id' => $admin_id,
                'group_id' => $admin_group,
                'createtime' => time()
            ];
            $res = Db::name('admin_order')->insert($arr);
            if (!$res) {
              $this->success('抢单成功');
            }
            // 提交事务
            Db::commit();
            $this->success('抢单成功');

        }
    /**
     * 添加
     */
    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }
        
        // 生成订单号
        if (empty($params['orderid'])) {
            $params['orderid'] = $this->generateOrderNumber();
        }
        
        // 设置创建时间
        if (empty($params['createtime'])) {
            $params['createtime'] = time();
        }
        
        // 如果是配车类型，计算金额
        if (isset($params['find_car_type']) && $params['find_car_type'] !== '') {
            $amountResult = $this->calculateOrderAmount($params);
            if ($amountResult === false && $params['find_car_type'] == '配车') {
                $this->error('未找到合适的物流专线,请联系管理员');
            }
            if (is_array($amountResult) && !empty($amountResult)) {
                $params = array_merge($params, $amountResult);
            }
        }
        
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            
            // 处理代收货款信息
            $chargeData = $this->request->post('charge/a');
            if (!empty($chargeData) && isset($params['delivery']) && $params['delivery'] == 1) {
                $chargeData['orderid'] = $params['orderid'];
                $chargeData['createtime'] = time();
                Db::name('charge')->insert($chargeData);
            }
            
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
    }

    /**
     * 录单（后台录入订单页面，仅 GET 展示）
     * 可单独作为菜单给录单员使用，也可在订单管理页通过“录单”按钮进入。保存走 order/ludan_save 接口。
     */
    public function ludan()
    {
        if ($this->request->isPost()) {
            $this->error('请使用录单保存接口提交');
        }
        $submitToken = bin2hex(random_bytes(16));
        $this->view->assign('admin', $this->auth->getUserinfo());
        $this->view->assign('ludan_submit_token', $submitToken);
        $configAk = 'MOzZlY5KKlLACYlMQf1g0aNah39SVGxg';
        $akList = [];
        if ($configAk !== null && $configAk !== '') {
            if (is_string($configAk)) {
                $decoded = json_decode($configAk, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $akList = $decoded;
                } else {
                    $akList = [trim($configAk)];
                }
            } elseif (is_array($configAk)) {
                $akList = $configAk;
            }
        }
        $baiduAk = '';
        if (!empty($akList)) {
            $first = reset($akList);
            $baiduAk = is_string($first) ? trim($first) : (string) $first;
        }
        if ($baiduAk === '') {
            $baiduAk = 'T6fLs4Xa9Hj16REmRNyeY20ZU5ODkpV2';
        }
        $this->view->assign('baidu_ak', $baiduAk);
        return $this->view->fetch('ludan');
    }

    /**
     * 录单保存（与后台添加订单 add() 同一套逻辑，不调用 Placeorder）
     * 仅接受 POST，表单提交到 order/ludan_save
     */
    public function ludan_save()
    {
        if (false === $this->request->isPost()) {
            $this->error('请使用 POST 提交');
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        // 提交令牌幂等：同一 token 的重复请求直接返回第一次结果（避免双单，也避免用户看到“失效”）
        $formToken = $this->request->post('submit_token', '', 'trim');
        if ($formToken === '') {
            $this->error('缺少提交令牌，请刷新页面后重试');
        }
        $tokenCacheKey = 'ludan_token_result:' . $formToken;
        $tokenCached = Cache::get($tokenCacheKey);
        if (is_array($tokenCached) && !empty($tokenCached['orderid'])) {
            $this->success('保存成功', null, $tokenCached);
        }
        if ($tokenCached === 'in_progress') {
            $this->error('提交处理中，请稍候');
        }
        Cache::set($tokenCacheKey, 'in_progress', 60);
        // 后端幂等防重：同一录单内容在短时间内只允许提交一次（兜底避免点一次生成多单）
        $dedupePayload = [
            'admin_id' => (int)$this->auth->id,
            'row' => $params,
            'loading_sender_name' => $this->request->post('loading_sender_name', '', 'trim'),
            'loading_sender_mobile' => $this->request->post('loading_sender_mobile', '', 'trim'),
            'loading_sender_address' => $this->request->post('loading_sender_address', '', 'trim'),
            'unload_receiver_name' => $this->request->post('unload_receiver_name', '', 'trim'),
            'unload_receiver_mobile' => $this->request->post('unload_receiver_mobile', '', 'trim'),
            'unload_receiver_address' => $this->request->post('unload_receiver_address', '', 'trim'),
        ];
        $dedupeKey = 'ludan_submit:' . md5(json_encode($dedupePayload, JSON_UNESCAPED_UNICODE));
        if (Cache::get($dedupeKey)) {
            $this->error('请勿重复提交');
        }
        Cache::set($dedupeKey, 1, 15);

        // 发货地址为直接输入：先写入 user_address，再得到 loading id
        $loadingName = $this->request->post('loading_sender_name', '', 'trim');
        $loadingMobile = $this->request->post('loading_sender_mobile', '', 'trim');
        $loadingAddress = $this->request->post('loading_sender_address', '', 'trim');
        if ($loadingAddress !== '') {
            if ($loadingName === '') {
                $this->error('请填写发货人');
            }
            if ($loadingMobile === '') {
                $this->error('请填写发货人手机号码');
            }
            $loadingLng = $this->request->post('loading_sender_lng', '', 'trim');
            $loadingLat = $this->request->post('loading_sender_lat', '', 'trim');
            if ($loadingLng === '' || $loadingLat === '') {
                $geo = $this->getCoordinatesFromBaiduMapForLudan($loadingAddress);
                $loadingLng = $geo['lng'] ?? '';
                $loadingLat = $geo['lat'] ?? '';
            }
            $addressRow = [
                'user_id'          => null,
                'address'          => $loadingAddress,
                'user_name'        => $loadingName,
                'mobile'           => $loadingMobile,
                'detailed_address' => $loadingAddress,
                'lng'              => $loadingLng !== '' ? $loadingLng : '',
                'lat'              => $loadingLat !== '' ? $loadingLat : '',
                'default'          => 0,
                'createtime'       => time(),
            ];
            Db::name('user_address')->insert($addressRow);
            $params['loading'] = Db::name('user_address')->getLastInsID();
        }

        // 收货地址为直接输入：先写入 user_address，再得到 unload id
        $unloadName = $this->request->post('unload_receiver_name', '', 'trim');
        $unloadMobile = $this->request->post('unload_receiver_mobile', '', 'trim');
        $unloadAddress = $this->request->post('unload_receiver_address', '', 'trim');
        if ($unloadAddress !== '') {
            if ($unloadName === '') {
                $this->error('请填写收货人');
            }
            if ($unloadMobile === '') {
                $this->error('请填写收货人手机号码');
            }
            $unloadLng = $this->request->post('unload_receiver_lng', '', 'trim');
            $unloadLat = $this->request->post('unload_receiver_lat', '', 'trim');
            if ($unloadLng === '' || $unloadLat === '') {
                $geo = $this->getCoordinatesFromBaiduMapForLudan($unloadAddress);
                $unloadLng = $geo['lng'] ?? '';
                $unloadLat = $geo['lat'] ?? '';
            }
            $addressRow = [
                'user_id'          => null,
                'address'          => $unloadAddress,
                'user_name'        => $unloadName,
                'mobile'           => $unloadMobile,
                'detailed_address' => $unloadAddress,
                'lng'              => $unloadLng !== '' ? $unloadLng : '',
                'lat'              => $unloadLat !== '' ? $unloadLat : '',
                'default'          => 0,
                'createtime'       => time(),
            ];
            Db::name('user_address')->insert($addressRow);
            $params['unload'] = Db::name('user_address')->getLastInsID();
        }

        // 与后台添加 add() 一致：录单用 userid=0、username=后台录单
        $params['userid'] = 0;
        $params['username'] = '后台录单';
        $params['mobile'] = '';
        if (empty($params['orderid'])) {
            $params['orderid'] = $this->generateOrderNumber();
        }
        if (empty($params['createtime'])) {
            $params['createtime'] = time();
        }
        if (!isset($params['pay_status'])) {
            $params['pay_status'] = 1;
        }
        if (!isset($params['logistics_status'])) {
            $params['logistics_status'] = 1;
        }
        if (isset($params['find_car_type']) && $params['find_car_type'] == '配车') {
            $amountResult = $this->calculateOrderAmount($params);
            if ($amountResult) {
                $params = array_merge($params, $amountResult);
            }
        }

        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            $chargeData = $this->request->post('charge/a');
            if (!empty($chargeData) && isset($params['delivery']) && $params['delivery'] == 1) {
                $chargeData['orderid'] = $params['orderid'];
                $chargeData['createtime'] = time();
                Db::name('charge')->insert($chargeData);
            }
            Db::name('order')->where('orderid', $params['orderid'])->update(['admin_id' => (int) $this->auth->id]);
            Db::commit();
        } catch (ValidateException|PDOException|\Exception $e) {
            Db::rollback();
            Cache::rm($dedupeKey);
            Cache::rm($tokenCacheKey);
            $this->error($e->getMessage());
        }
        if ($result === false) {
            Cache::rm($dedupeKey);
            Cache::rm($tokenCacheKey);
            $this->error(__('No rows were inserted'));
        }
        $payPrice = isset($params['pay_price']) ? $params['pay_price'] : 0;
        $nextToken = bin2hex(random_bytes(16));
        $resp = ['orderid' => $params['orderid'], 'pay_price' => $payPrice, 'submit_token' => $nextToken];
        // 将本 token 的结果缓存，重复请求直接返回
        Cache::set($tokenCacheKey, $resp, 300);
        // dedupeKey 也记录结果（便于其它路径复用）
        Cache::set($dedupeKey, $resp, 300);
        $this->success('保存成功', null, $resp);
    }

    /**
     * 录单地址兜底：通过百度地图将文本地址解析为经纬度
     */
    private function getCoordinatesFromBaiduMapForLudan($address)
    {
        $address = trim((string)$address);
        if ($address === '') {
            return ['lng' => '', 'lat' => ''];
        }
        $configAk = Config::get('site.BaiduKey');
        $ak = '';
        if (is_string($configAk) && $configAk !== '') {
            $decoded = json_decode($configAk, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                $first = reset($decoded);
                $ak = is_string($first) ? trim($first) : (string)$first;
            } else {
                $ak = trim($configAk);
            }
        } elseif (is_array($configAk) && !empty($configAk)) {
            $first = reset($configAk);
            $ak = is_string($first) ? trim($first) : (string)$first;
        }
        if ($ak === '') {
            $ak = 'T6fLs4Xa9Hj16REmRNyeY20ZU5ODkpV2';
        }

        $url = 'https://api.map.baidu.com/geocoding/v3/?address=' . urlencode($address) . '&output=json&ak=' . urlencode($ak);
        $result = @file_get_contents($url);
        if ($result === false) {
            return ['lng' => '', 'lat' => ''];
        }
        $data = json_decode($result, true);
        if (!is_array($data) || (int)($data['status'] ?? -1) !== 0) {
            return ['lng' => '', 'lat' => ''];
        }
        $location = $data['result']['location'] ?? [];
        return [
            'lng' => isset($location['lng']) ? (string)$location['lng'] : '',
            'lat' => isset($location['lat']) ? (string)$location['lat'] : '',
        ];
    }

    /**
     * 录单页用：根据地址ID返回一条地址详情（用于右侧目的网点和发站/到站带出）
     */
    public function get_address()
    {
        $ids = $this->request->param('ids');
        if (empty($ids)) {
            $this->error('缺少参数');
        }
        $id = is_array($ids) ? (int)(reset($ids)) : (int)$ids;
        $row = Db::name('user_address')->where('id', $id)->find();
        if (!$row) {
            $this->error('地址不存在');
        }
        $this->success('', null, $row);
    }
    
    /**
     * 生成订单号
     */
    private function generateOrderNumber()
    {
        // 与前端 Placeorder::generateOrderNumber 保持一致
        $randomPart = str_pad(rand(1000000000, 9999999999), 4, '0', STR_PAD_LEFT);
        return 'LZ' . '66' . $randomPart;
    }
    
    /** 
     * 计算订单金额（仅配车类型）
     */
    private function calculateOrderAmount($params)
    {
        if (empty($params['find_car_type'])) {
            return false;
        }

        // 与前端下单保持一致：专车/小票快运走单价结构，不匹配专线
        if (in_array($params['find_car_type'], ['专车', '小票快运'], true)) {
            $basePrice = isset($params['pay_price']) ? floatval($params['pay_price']) : 0;
            $goodsTypePercentage = $this->getGoodsTypePercentage($params['goods_type_id'] ?? 0);
            if ($goodsTypePercentage > 0) {
                $basePrice = $basePrice * (1 + $goodsTypePercentage / 100);
            }
            $basePrice = round($basePrice, 2);
            return [
                'logistics_id' => 0,
                'logistics_cost' => 0,
                'pickup_fee' => 0,
                'shipment_fee' => 0,
                'pickup_driver_fee' => 0,
                'shipment_driver_fee' => 0,
                'logistics_driver_cost' => $basePrice,
                'pay_price' => $basePrice,
                // 与前端一致：总成本=司机成本（专车/小票快运无专线拆分时也应计入）
                'cost_cont' => $basePrice,
                'shipping_cost' => $basePrice,
            ];
        }

        if (empty($params['loading']) || empty($params['unload']) || empty($params['car_type_id'])) {
            return false;
        }

        // 配车：匹配物流专线 + 自动算价
        $logisticsResult = $this->matchLogistics($params['loading'], $params['unload'], $params['car_type_id'], $params);
        if (!$logisticsResult) {
            return false;
        }

        return $logisticsResult;
    }
    
    /**
     * 匹配物流专线
     */
    private function matchLogistics($loadingId, $unloadId, $carTypeId, $params)
    {
        $loading = Db::name('user_address')->where('id', $loadingId)->find();
        $unload = Db::name('user_address')->where('id', $unloadId)->find();
        $carType = Db::name('car_type')->where('id', $carTypeId)->find();

        if (!$loading || !$unload || !$carType) {
            return false;
        }
        // 与前端下单保持一致：仅匹配启用中的专线
        $logisticsList = Db::name('logistics')->where('logistics_status', 1)->where('status', 2)->select();
        $candidates = [];

        foreach ($logisticsList as $logistics) {
            if (empty($logistics['shipping_latitude']) || empty($logistics['shipping_longitude']) ||
                empty($logistics['arrival_latitude']) || empty($logistics['arrival_longitude'])) {
                continue;
            }
            // 检查装货地址到物流起点的距离（200km内）
            $loadingMatch = $this->checkDistanceMatch(
                $loading['lat'], $loading['lng'],
                $logistics['shipping_latitude'], $logistics['shipping_longitude'],
                200
            );

            // 检查卸货地址到物流终点的距离（200km内）
            $unloadMatch = $this->checkDistanceMatch(
                $unload['lat'], $unload['lng'],
                $logistics['arrival_latitude'], $logistics['arrival_longitude'],
                200
            );

            // 新增：确保物流方向正确 - 物流终点应该比起点更接近订单终点
            // 这样可以避免匹配到方向不对的物流（如：订单是广东到山西，不应该匹配广东到湖北的物流）
            $directionMatch = true;
            if ($loadingMatch && $unloadMatch) {
                // 计算物流起点到订单终点的距离
                $logisticsStartToOrderEnd = calculateDistance(
                    $logistics['shipping_latitude'], $logistics['shipping_longitude'],
                    $unload['lat'], $unload['lng']
                );
                
                // 计算物流终点到订单终点的距离
                $logisticsEndToOrderEnd = calculateDistance(
                    $logistics['arrival_latitude'], $logistics['arrival_longitude'],
                    $unload['lat'], $unload['lng']
                );
                
                // 物流终点应该比起点更接近订单终点（允许一定的误差，比如50km）
                // 如果物流终点到订单终点的距离比起点到订单终点的距离大很多，说明方向不对
                if ($logisticsEndToOrderEnd > $logisticsStartToOrderEnd + 50) {
                    $directionMatch = false;
                }
            }

            if ($loadingMatch && $unloadMatch && $directionMatch) {
                // 预筛选阶段仅用直线距离，避免大量调驾车距离接口导致请求阻塞
                $distances = [
                    'loading_to_start' => calculateDistance(
                        $loading['lat'], $loading['lng'],
                        $logistics['shipping_latitude'], $logistics['shipping_longitude']
                    ),
                    'logistics_line' => $logistics['distance'] ?? 0,
                    'end_to_unload' => calculateDistance(
                        $unload['lat'], $unload['lng'],
                        $logistics['arrival_latitude'], $logistics['arrival_longitude']
                    ),
                ];
                $preview = $this->calculateLogisticsCostForAdd(
                    [
                        'logistics_id' => $logistics['id'],
                        'logistics_name' => $logistics['name'] ?? '',
                        'distances' => $distances,
                        'logistics_data' => $logistics
                    ],
                    $carType,
                    $distances,
                    $params
                );
                $candidates[] = [
                    'logistics' => $logistics,
                    'total_price' => ($preview['logistics_cost'] ?? 0) + ($preview['pickup_fee'] ?? 0) + ($preview['shipment_fee'] ?? 0),
                    'total_distance' => array_sum($distances),
                ];
            }
        }

        if (empty($candidates)) {
            return false;
        }

        // 与前端一致：先比总价，再比总距离
        usort($candidates, function($a, $b) {
            $priceCmp = ($a['total_price'] ?? PHP_FLOAT_MAX) <=> ($b['total_price'] ?? PHP_FLOAT_MAX);
            if ($priceCmp !== 0) {
                return $priceCmp;
            }
            return ($a['total_distance'] ?? 0) <=> ($b['total_distance'] ?? 0);
        });

        // 最终仅对中选专线计算一次驾车距离并返回计价
        $selected = $candidates[0]['logistics'];
        $finalDistances = $this->calculateSegmentDistances($loading, $unload, $selected);
        return $this->calculateLogisticsCostForAdd(
            [
                'logistics_id' => $selected['id'],
                'logistics_name' => $selected['name'] ?? '',
                'distances' => $finalDistances,
                'logistics_data' => $selected
            ],
            $carType,
            $finalDistances,
            $params
        );
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
     * 计算物流费用（用于添加订单时）
     */
    private function calculateLogisticsCostForAdd($logisticsInfo, $carType, $distances, $params)
    {
        $logistics = $logisticsInfo['logistics_data'];
        $weight = $params['weight'] ?? 0;
        $direction = $params['direction'] ?? 0;

        // 复用同文件内完整计价逻辑（省份阶梯价 + 货物类型上浮）
        $orderForCalc = [
            'weight' => $weight,
            'direction' => $direction,
            'loading' => $params['loading'] ?? 0,
            'unload' => $params['unload'] ?? 0,
            'goods_type_id' => $params['goods_type_id'] ?? 0,
        ];
        $cost = $this->calculateLogisticsCost($orderForCalc, $logistics, $carType, $distances);
        $payPrice = ($cost['logistics_cost'] ?? 0) + ($cost['pickup_fee'] ?? 0) + ($cost['shipment_fee'] ?? 0);
        $driverCostTotal = ($cost['logistics_driver_cost'] ?? 0) + ($cost['pickup_driver_fee'] ?? 0) + ($cost['shipment_driver_fee'] ?? 0);

        return [
            'logistics_id' => $logisticsInfo['logistics_id'],
            'arrivaltime' => $logistics['time_limit'] ?? '',
            'logistics_cost' => $cost['logistics_cost'] ?? 0,
            'logistics_driver_cost' => $cost['logistics_driver_cost'] ?? 0,
            'pickup_driver_fee' => $cost['pickup_driver_fee'] ?? 0,
            'shipment_driver_fee' => $cost['shipment_driver_fee'] ?? 0,
            'pickup_fee' => $cost['pickup_fee'] ?? 0,
            'shipment_fee' => $cost['shipment_fee'] ?? 0,
            'pay_price' => round($payPrice, 2),
            'total_amount' => round($payPrice, 2)
            ,
            // 与前端一致：总成本=专线司机成本+取货司机费+送货司机费
            'cost_cont' => round($driverCostTotal, 2),
            'shipping_cost' => round($payPrice, 2)
        ];
    }

    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edit($ids = null) 
    {
        $row = $this->model->get($ids);   
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();

        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $loading_address = Db::name('user_address')->where('id',$row['loading'])->find();
            $unload_address = Db::name('user_address')->where('id',$row['unload'])->find();
            if ($loading_address){
                $row['loading'] = $loading_address['user_name'].'-'.$loading_address['mobile'].'-'.$loading_address['address'].'-'.'-'.$loading_address['detailed_address'].'-';
                $row['loading_user_name'] = $loading_address['user_name'] ?? '';
                $row['loading_mobile'] = $loading_address['mobile'] ?? '';
            }
            if ($unload_address){
                $row['unload'] = $unload_address['user_name'].'-'.$unload_address['mobile'].'-'.$unload_address['address'].'-'.'-'.$unload_address['detailed_address'].'-';
                $row['unload_user_name'] = $unload_address['user_name'] ?? '';
                $row['unload_mobile'] = $unload_address['mobile'] ?? '';
            }
            // 加载代收货款信息
            $charge = Db::name('charge')->where('orderid', $row['orderid'])->find();
            $this->view->assign('charge', $charge ?: []);
            // 税点从 dricerorder 表回显
            $row['pickup_tax_point']   = Db::name('dricerorder')->where('order_id', $row['orderid'])->where('type', 1)->value('tax_point');
            $row['shipment_tax_point'] = Db::name('dricerorder')->where('order_id', $row['orderid'])->where('type', 3)->value('tax_point');
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $loadingAddress = Db::name('user_address')->where('id', $row['loading'])->find();
        $unloadAddress = Db::name('user_address')->where('id', $row['unload'])->find();
        if (isset($params['loading_user_name']) || isset($params['loading_mobile'])) {
            $loadingUpdate = [];
            if (isset($params['loading_user_name'])) {
                $loadingUpdate['user_name'] = trim((string)$params['loading_user_name']);
                unset($params['loading_user_name']);
            }
            if (isset($params['loading_mobile'])) {
                $loadingUpdate['mobile'] = trim((string)$params['loading_mobile']);
                unset($params['loading_mobile']);
            }
            if (!empty($loadingUpdate)) {
                Db::name('user_address')->where('id', $row['loading'])->update($loadingUpdate);
            }
        }
        if (isset($params['unload_user_name']) || isset($params['unload_mobile'])) {
            $unloadUpdate = [];
            if (isset($params['unload_user_name'])) {
                $unloadUpdate['user_name'] = trim((string)$params['unload_user_name']);
                unset($params['unload_user_name']);
            }
            if (isset($params['unload_mobile'])) {
                $unloadUpdate['mobile'] = trim((string)$params['unload_mobile']);
                unset($params['unload_mobile']);
            }
            if (!empty($unloadUpdate)) {
                Db::name('user_address')->where('id', $row['unload'])->update($unloadUpdate);
            }
        }
        $oldData = $row->toArray();
        if ($loadingAddress) {
            $oldData['loading_user_name'] = $loadingAddress['user_name'] ?? '';
            $oldData['loading_mobile'] = $loadingAddress['mobile'] ?? '';
        }
        if ($unloadAddress) {
            $oldData['unload_user_name'] = $unloadAddress['user_name'] ?? '';
            $oldData['unload_mobile'] = $unloadAddress['mobile'] ?? '';
        }
        // 税点存在 dricerorder 表，补到 oldData 用于 diff 比较，不写入 order 表
        $oldData['pickup_tax_point']   = Db::name('dricerorder')->where('order_id', $row['orderid'])->where('type', 1)->value('tax_point');
        $oldData['delivery_tax_point'] = Db::name('dricerorder')->where('order_id', $row['orderid'])->where('type', 3)->value('tax_point');

        // 只记录真正变化的字段，方便审核时“只看改了哪里”
        $diffOld = [];
        $diffNew = []; 
        foreach ($params as $field => $value) {
            // 忽略仅用于展示的虚拟字段
            if (in_array($field, ['loading', 'unload', 'loading_user_name', 'loading_mobile', 'unload_user_name', 'unload_mobile'], true)) {
                continue;
            }
            $oldValue = array_key_exists($field, $oldData) ? $oldData[$field] : null;

            // 特殊处理时间字段：旧值是时间戳，新值是日期字符串时，按时间比较
            if (is_numeric($oldValue) && (int)$oldValue > 0 && is_string($value) && $value !== '') {
                $ts = strtotime($value);
                if ($ts !== false && (int)$oldValue === (int)$ts) {
                    continue;
                }
            }
            if ((string)$oldValue === (string)$value) {
                continue;
            }
            $diffOld[$field] = $oldValue;
            $diffNew[$field] = $value;
        }

        // 代收货款打包成一个字段（如有需要审核时再展开处理）
        $chargeData = $this->request->post('charge/a');
        if (is_array($chargeData)) {
            $hasChargeChange = false;
            foreach ($chargeData as $v) {
                if (trim((string)$v) !== '') {
                    $hasChargeChange = true;
                    break;
                }
            }
            if ($hasChargeChange) {
                // 这里可以按需补充旧的代收货款数据
                $oldCharge = Db::name('charge')->where('orderid', $row['orderid'])->find() ?: [];
                $diffOld['charge'] = $oldCharge;
                $diffNew['charge'] = $chargeData;
            }
        }

        if (!$diffOld && !$diffNew) {
            $this->success('本次没有实际改动');
        }

        // 直接应用修改，不需要审核（总后台与子后台一致）
        OrderModifyApplier::apply('edit_order', (int)$row['id'], 0, $row['orderid'] ?? '', $diffNew);
        $this->success('修改成功');
    }

    /**
     * 配车/专车确定订单（转发到子控制器 admin\Order）
     */
    public function carfim()
    {
        $controller = \think\Loader::controller('admin.Order', 'controller');
        return $controller->carfim();
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
        $row = $this->model->get($ids);
//        print_r($row);die;
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }
        
        $loading_address = Db::name('user_address')->where('id',$row['loading'])->find();
        $unload_address = Db::name('user_address')->where('id',$row['unload'])->find();
        if ($loading_address){
            $row['loading'] = $loading_address['user_name'].'-'.$loading_address['mobile'].'-'.$loading_address['address'].'-'.'-'.$loading_address['detailed_address'].'-';
        }
        if ($unload_address){
            $row['unload'] = $unload_address['user_name'].'-'.$unload_address['mobile'].'-'.$unload_address['address'].'-'.'-'.$unload_address['detailed_address'].'-';
        }
        $row['car_type_id'] = Db::name('car_type')->where('id',$row['car_type_id'])->value('name');
        $row['goods_type_id'] = Db::name('goods_type')->where('id',$row['goods_type_id'])->value('name');
        $row['dimensions'] = Db::name('dimensions')->where('order_id',$row['orderid'])->select();
        $row['packaging_id'] = Db::name('packaging')->where('id',$row['packaging_id'])->value('name');
        $row['delivery_type_id'] = Db::name('delivery_type')->where('id',$row['delivery_type_id'])->value('name');
        $row['receipt_type_id'] = Db::name('receipt_type')->where('id',$row['receipt_type_id'])->value('name');
        $receipt_type = Db::name('receipt_type')->where('id',$row['receipt_type_id'])->find();
        $row['delivery_type_id'] = Db::name('delivery_type')->where('id',$row['delivery_type_id'])->value('name');
        if ($receipt_type){
            $row['receipt_type_id'] = $receipt_type['name'].'-'.$receipt_type['type'];
        }
        $row['unpack_id'] = Db::name('unpack')->where('id',$row['unpack_id'])->value('name') .'X'.$row['unpack_num'];
        $other_ids = $row['other_id'];
        if (empty($other_ids)) {
            $row['other_id'] = '';
        } elseif (strpos((string)$other_ids, ',') !== false) {
            $ids = array_filter(array_map('intval', explode(',', $other_ids)));
            $row['other_id'] = $ids ? implode(',', Db::name('other')->where('id', 'in', $ids)->column('name')) : '';
        } else {
            $row['other_id'] = Db::name('other')->where('id', $other_ids)->value('name');
        }
        // 提货要求
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
        $row['unpack_id'] = Db::name('unpack')->where('id',$row['unpack_id'])->value('name');
        $row['pickup_tax_point'] = Db::name('dricerorder')->where('order_id',$row['orderid'])->where('type',1)->value('tax_point');
        $row['shipment_tax_point'] = Db::name('dricerorder')->where('order_id',$row['orderid'])->where('type',3)->value('tax_point');
        // 加载代收货款信息
        $charge = Db::name('charge')->where('orderid', $row['orderid'])->find();
//        print_r($row);die;
        // 抢单归属：哪个线路、哪个调度抢的单（详情弹窗展示）
        $grabberMap = $this->buildOrderGrabberMap([(int)$row['id']]);
        $row['grab_line'] = (string)($grabberMap[(int)$row['id']]['line'] ?? '');
        $row['grab_dispatch'] = (string)($grabberMap[(int)$row['id']]['dispatch'] ?? '');
        // 公司利润：兼职取平台抽佣，正式取毛利40%；仅总后台/财务/超级管理员可见
        if ($this->orderListBypassBindRegionalScope()) {
            $payPrice = isset($row['pay_price']) ? floatval($row['pay_price']) : 0;
            $costCont = isset($row['cost_cont']) ? floatval($row['cost_cont']) : 0;
            $memberType = (int)Db::name('user')->where('id', $row['userid'])->value('membertype');
            $platformCommission = isset($row['platform_commission']) ? floatval($row['platform_commission']) : 0;
            $row['company_profit'] = $this->calcCompanyProfit($memberType, $payPrice, $costCont, $platformCommission);
        }
        $this->view->assign('charge', $charge ?: []);
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }


    /**
     * 总后台打印订单（针式打印机，如 EPSON LQ‑630K）
     *
     * 说明：
     * - 前端直接用浏览器「打印」功能输出到已安装好的 EPSON LQ‑630K 驱动
     * - 打印模板使用一张背景图片（即你提供的托运单），在其上通过绝对定位把字段盖上去
     * - 模板页面建议单独写成 print.html，尺寸和纸张大小、孔距调好后基本就不会变
     *
     * @param int $ids 订单表主键ID
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function print($ids = null)
    {
        // 子后台 admin_order 列表传 admin_order.id，总后台传 order.id
        $adminOrder = Db::name('admin_order')->where('id', $ids)->find();
        if ($adminOrder) {
            $order = $this->model->get($adminOrder['order_id']);
        } else {
            $order = $this->model->get($ids);
        }
        if (!$order) {
            $this->error(__('No Results were found'));
        }

        // 权限校验
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($order[$this->dataLimitField], $adminIds)) {
            $this->error(__('You have no permission'));
        }

        // 发货/收货地址（带姓名、电话、详细地址）
        $loadingAddress = Db::name('user_address')->where('id', $order['loading'])->find();
        $unloadAddress  = Db::name('user_address')->where('id', $order['unload'])->find();

        // 车长车型、货物类型、包装等显示用字段
        $order['car_type_name']   = Db::name('car_type')->where('id', $order['car_type_id'])->value('name');
        $order['goods_type_name'] = Db::name('goods_type')->where('id', $order['goods_type_id'])->value('name');
        $order['packaging_name']  = Db::name('packaging')->where('id', $order['packaging_id'])->value('name');
        // 代收货款信息（如有）
        $charge = Db::name('charge')->where('orderid', $order['orderid'])->find();

        $template = trim((string)$this->request->param('template', ''));
        if ($template === 'receipt') {
            // 签收单：字段与 api Index::getPrintData + generateReceiptContent 一致（浏览器打印 HTML）
            $recv = $unloadAddress ?: [];
            $send = $loadingAddress ?: [];
            $recvLine = !empty($recv['detailed_address']) ? $recv['detailed_address'] : ($recv['address'] ?? '');
            $sendLine = !empty($send['detailed_address']) ? $send['detailed_address'] : ($send['address'] ?? '');
            $volume = '0';
            if (!empty($order['long']) && !empty($order['wide']) && !empty($order['hige'])) {
                $volume = (string)round(($order['long'] * $order['wide'] * $order['hige']) / 1000000, 2);
            }
            if ((int)$order['pay_type'] === 1) {
                $payTypeName = '到付';
            } else {
                $payTypeName = '现付';
            }
            $codAmount = '0.00';
            if ($charge && isset($charge['amount']) && $charge['amount'] !== '' && $charge['amount'] !== null) {
                $codAmount = is_numeric($charge['amount']) ? number_format((float)$charge['amount'], 2, '.', '') : (string)$charge['amount'];
            }
            $receipt = [
                'orderid'        => $order['orderid'] ?? '',
                'createtime'     => !empty($order['createtime']) ? date('Y-m-d H:i:s', (int)$order['createtime']) : '',
                'goods_name'     => !empty($order['goods_type_name']) ? $order['goods_type_name'] : '货物',
                'pack_type'      => !empty($order['packaging_name']) ? $order['packaging_name'] : '标准',
                'weight'         => isset($order['weight']) ? $order['weight'] : '0',
                'volume'         => $volume,
                'num'            => $order['quantity'] ?? '1',
                'pay_price'      => isset($order['pay_price']) ? $order['pay_price'] : '0.00',
                'pay_type_name'  => $payTypeName,
                'declare_value'  => '0.00',
                'service_fee'    => '0.00',
                'cod_amount'     => $codAmount,
                'show_cod'       => is_numeric($codAmount) ? ((float)$codAmount > 0) : ($codAmount !== '' && $codAmount !== '0.00'),
            ];
            $this->view->assign('receipt', $receipt);
            $this->view->assign('recv', array_merge($recv, ['line' => $recvLine]));
            $this->view->assign('send', array_merge($send, ['line' => $sendLine]));
            $this->view->assign('receipt_logo', 'https://lzwl.longzhehutong.cn/uploads/20260322/6635c0538578a5e18d2055caf684bce6.jpg');
            return $this->view->fetch('print_receipt');
        }

        $order['weight'] = $order['weight'] * 1000;
        $this->view->assign('order', $order);
        $this->view->assign('loadingAddress', $loadingAddress ?: []);
        $this->view->assign('unloadAddress', $unloadAddress ?: []);
        $this->view->assign('charge', $charge ?: []);

        // 渲染打印专用模板：application/admin/view/order/print.html
        return $this->view->fetch('print');
    }

    /**
     * @return void
     * @throws DbException
     * @throws PDOException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * 规划路线
     */
    public function logistics(){

        $data = $this->request->param();
        $order_id = Db::name('admin_order')->where('id',$data['ids'])->value('order_id');
        // 获取订单信息
        $order = Db::name('order')->where('id',$order_id)->find();
        if (!$order) {
            $this->error('订单不存在');
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
        $mobile = $logistics['shipping_logistics_mobile'] ?? 
                 $logistics['Shipping_logistics_mobile'] ?? 
                 $logistics['shipping_logistics_phone'] ?? 
                 '';
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
                        'identity'   =>3
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
        $res = Db::name('order')->where('id',$order_id)->update(['logistics_id'=>$data['selected_row']]);
        if($res){
            // 重新计算金额
            $costResult = $this->recalculateLogisticsCost($order_id, $data['selected_row']);
            if ($costResult) {
                $this->success('路线规划成功，金额已重新计算');
            } else {
                $this->success('路线规划成功，但金额计算失败，请手动填写');
            }
        }else{
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

            // 路线/司机重算后需要同步更新 pay_price（含税总额）
            // 原因：OrderModifyApplier::recalcCostCont() 里的“开票税额”计算依赖 order.pay_price；
            // 如果只改 logistics_* / pickup_* / shipment_* 不更新 pay_price，会导致 cost_cont 用到旧的开票税额口径。
            $oldLogisticsSum = (float)($order['logistics_cost'] ?? 0)
                + (float)($order['pickup_fee'] ?? 0)
                + (float)($order['shipment_fee'] ?? 0);
            $newLogisticsSum = (float)($costResult['logistics_cost'] ?? 0)
                + (float)($costResult['pickup_fee'] ?? 0)
                + (float)($costResult['shipment_fee'] ?? 0);
            $currentPayPrice = (float)($order['pay_price'] ?? 0);

            // 默认：不参与开票税额重算时，直接替换运费分项即可
            $newPayPrice = round($currentPayPrice - $oldLogisticsSum + $newLogisticsSum, 2);

            // 开票订单：pay_price 为“含税总额”，税额 = base_no_tax * r/100
            // 因此先反推 base_no_tax，再替换运费分项并重算税额，避免税额与运费不一致。
            $isinvoice = isset($order['isinvoice']) ? (int)$order['isinvoice'] : 0;
            $taxPointRaw = $order['tax_point'] ?? null;
            if ($isinvoice === 1 && $taxPointRaw !== '' && $taxPointRaw !== null) {
                $r = (float)str_replace('%', '', trim((string)$taxPointRaw));
                if ($r > 0) {
                    $baseNoTax = $currentPayPrice / (1 + $r / 100);
                    $baseNoTaxNew = $baseNoTax - $oldLogisticsSum + $newLogisticsSum;
                    $taxAmount = round($baseNoTaxNew * ($r / 100), 2);
                    $newPayPrice = round($baseNoTaxNew + $taxAmount, 2);
                }
            }

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
            // 更新订单金额
            $updateData = [
                'logistics_cost' => $costResult['logistics_cost'],
                'logistics_driver_cost' => $costResult['logistics_driver_cost'],
                'pickup_driver_fee' => $pickupDriverFilled ? (float)$order['pickup_driver_fee'] : $costResult['pickup_driver_fee'],
                'shipment_driver_fee' => $shipmentDriverFilled ? (float)$order['shipment_driver_fee'] : $costResult['shipment_driver_fee'],
                'pickup_fee' => $costResult['pickup_fee'],
                'shipment_fee' => $costResult['shipment_fee'],
                'pay_price' => $newPayPrice,
            ];
            
            $result = Db::name('order')->where('id', $orderId)->update($updateData);
            if ($result !== false) {
                OrderModifyApplier::recalcCostCont($orderId);
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

        // 计算基础费用
        $logisticsCost = $priceResult['price'] * (1 + $LogisticsDriverFreight / 100);
        $pickupDriverFeeBase = $pickupDriverFee * (1 + $PickUpDriverFreight / 100);
        $shipmentDriverFeeBase = $shipmentDriverFee * (1 + $DeliveryDriverFreight / 100);
        
        // 获取货物类型价格上浮百分比
        $goodsTypePercentage = $this->getGoodsTypePercentage($order['goods_type_id'] ?? 0);
        
        // 应用货物类型价格上浮百分比
        if ($goodsTypePercentage > 0) {
            $logisticsCost = $logisticsCost * (1 + $goodsTypePercentage / 100);
            $pickupFee = $pickupDriverFeeBase * (1 + $goodsTypePercentage / 100);
            $shipmentFee = $shipmentDriverFeeBase * (1 + $goodsTypePercentage / 100);
        } else {
            $pickupFee = $pickupDriverFeeBase;
            $shipmentFee = $shipmentDriverFeeBase;
        }
        return [
            'logistics_cost' => round($logisticsCost, 2),
            'logistics_driver_cost' => round($priceResult['price'], 2),
            'pickup_driver_fee' => round($pickupDriverFee, 2),
            'shipment_driver_fee' => round($shipmentDriverFee, 2),
            'pickup_fee' => round($pickupFee, 2),
            'shipment_fee' => round($shipmentFee, 2)
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
     * 查看物流轨迹（总后台）
     *
     * @param int $ids 订单表主键ID
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function logistics_detail($ids = null)
    {
//        print_r($ids);die;
        // 先按订单表主键id查（总后台列表直接传的是order.id）
        $adminOrder = Db::name('admin_order')->where('id', $ids)->find();
            if ($adminOrder) {
                $order = Db::name('order')->where('id', $adminOrder['order_id'])->find();
            }else{
                $order = Db::name('order')->where('id', $ids)->find();
            }

        // 订单号
        $orderNumber = isset($order['orderid']) ? $order['orderid'] : '';

        // 专线信息（沿用之前的逻辑）
        $logisticsInfo = [];
        if (!empty($order['logistics_id'])) {
            $logisticsInfo = Db::name('logistics')
                ->where('id', $order['logistics_id'])
                ->find();
        }

        // 轨迹记录：来自 trajectory 表，按时间正序
        $trajectoryList = Db::name('trajectory')
            ->where('order_id', $order['id'])
            ->order('createtime', 'asc')
            ->select();
//        print_r($order['loading']);die;
        $order['loading'] = Db::name('user_address')->where('id',$order['loading'])->value('detailed_address').Db::name('user_address')->where('id',$order['loading'])->value('address');;

        $order['unload'] = Db::name('user_address')->where('id',$order['unload'])->value('detailed_address').Db::name('user_address')->where('id',$order['unload'])->value('address');

        $this->view->assign('order', $order);
        $this->view->assign('orderNumber', $orderNumber);
        $this->view->assign('trajectoryList', $trajectoryList);
        $this->view->assign('logisticsInfo', $logisticsInfo);

        return $this->view->fetch();
    }
    public function cancel_order()
    {
        $id = $this->request->param('ids');
        $data = Db::name('order')->where('id',$id)->find();
        $res = Db::name('order')->where('id',$id)->update(['pay_status'=>4]);
        if($res) {
            $this->success('取消订单成功');
        }else{
            $this->error('取消订单失败');
        }
    }

    /**
     * 财务标记是否已发工资
     *
     * @return void
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function set_pay_salary()
    {
        $id = $this->request->param('ids');
        $status = $this->request->param('status', 1);

        if (empty($id)) {
            $this->error('订单ID不能为空');
        }

        // 仅总后台和财务角色可以操作（财务 group_id = 30）
        if (!$this->auth->isSuperAdmin() && !in_array(30, $this->auth->getGroupIds(), true)) {
            $this->error('无权操作');
        }

        $status = intval($status) === 1 ? 1 : 0;

        $order = Db::name('order')->where('id', $id)->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $res = Db::name('order')->where('id', $id)->update(['is_pay_salary' => $status]);
        if ($res === false) {
            $this->error('更新失败，请重试');
        }

        $this->success($status === 1 ? '已标记为已发工资' : '已标记为未发工资');
    }

    /**
     * 保存寄回单号（快递名称 + 单号）
     */
    public function save_return_tracking()
    {
        $id = $this->request->param('ids');
//        print_r($id);die;
        $returnExpressName = $this->request->param('return_express_name', '', 'trim');
        $returnExpressNo = $this->request->param('return_express_no', '', 'trim');

        if (empty($id)) {
            $this->error('订单ID不能为空');
        }
        $this->errorIfOrderRejectedByAdminOrderId($id);
        if ($returnExpressName === '') {
            $this->error('请填写快递名称');
        }
        if ($returnExpressNo === '') {
            $this->error('请填写单号');
        }

        $admin_order = Db::name('admin_order')->where('id', $id)->find();
        $order = Db::name('order')->where('id', $admin_order['order_id'])->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $res = Db::name('delivery_receipt')->insert([
            'order_id'            => $order['orderid'],
            'return_express_name' => $returnExpressName,
            'return_express_no'   => $returnExpressNo,
            'createtime'          => time(),
        ]);
        if ($res === false) {
            $this->error('保存失败，请重试');
        }

        $this->success('寄回单号已保存');
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

        // 查询订单信息
        $order = Db::name('order')->where('id', $id)->find();
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
            Db::name('order')->where('id', $id)->update($updateData);

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
     * 删除
     *
     * @param $ids
     * @return void
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function del($ids = null)
    {
        if (false === $this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ?: $this->request->post("ids");
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $list = $this->model->where($pk, 'in', $ids)->select();

        $count = 0;
        Db::startTrans(); 
        try {
            // 同时删除 admin_order 表中 order_id 对应的关联数据
            Db::name('admin_order')->where('order_id', 'in', $ids)->delete();
            foreach ($list as $item) {
                $count += $item->delete();
            }
            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were deleted'));
    }

    /**
     * 路线变更提醒：获取当前管理员未读列表（供调度端轮询，子后台 admin/order 会请求此路径）
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
     * 路线变更提醒：标记为已读
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
        return json(['code' => 1, 'msg' => '已读']);
    }

    /**
     * 新单语音提醒：线路端有新的可抢订单 / 调度端有线路确认后的待接单，则返回 has_new 供前端播报
     * 线路 identity=2：backend_status=1 且无任意 admin_order；不看待下单(5)、取消(4)、驳回(8)
     * 调度 identity=3：backend_status=2、仅配车，且无「调度抢单」admin_order；不看待下单(5)、取消(4)、驳回(8)
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
        if ($this->orderListTreatAsUnscopedAdminView((int)$admin_group, $group_identity)) {
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
            // 与订单列表（index）保持同一口径，避免“列表有单、语音不播”或反之
            $isFranchiseAccount = FranchiseService::resolveFranchiseForAdmin((int)$admin_id) !== null;
            $isHqLineDispatch = FranchiseService::isHqLineDispatch((int)$admin_id);
            if ($group_identity == 2) {
                $restrictFindCarPeiChe = true;
                if ($isFranchiseAccount || $isHqLineDispatch) {
                    $restrictFindCarPeiChe = false; // 加盟商体系账号 / 总部直属线路不限制找车类型
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
            if ($isFranchiseAccount) {
                // 加盟商体系账号（含线路/调度/财务）：按加盟商范围（绑定会员 或 区域内未绑定普通用户）
                FranchiseService::applyFranchiseOrderScope($query, (int)$admin_id);
            } elseif ($isHqLineDispatch) {
                // 总部直属线路/调度：总部绑定用户 + 无加盟商区域内的未绑定普通用户
                FranchiseService::applyHqOrderScope($query, (int)$admin_id);
            } else {
                $staffBindUserIds = AdminUserBind::getBoundUserIdsForLineDispatchRole((int)$admin_group, $group_identity, (int)$admin_id);
                if ($staffBindUserIds !== null) {
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

    /**
     * 导出订单（含订单详细信息）
     * 使用与 index 相同的筛选与权限逻辑，分批写入 CSV，避免内存溢出
     *
     * @return void
     * @throws DbException
     */
    public function export()
    {

//
        set_time_limit(0);
        @ini_set('memory_limit', '512M');
        $this->request->filter(['strip_tags', 'trim']);

        // 只导出勾选：必须带参数 ids（GET 或 POST），否则一律报错，绝不按条件或导出全部
        $idsParam = $this->request->param('ids', '');
        $ids = is_array($idsParam) ? [] : array_filter(array_unique(array_map('intval', array_map('trim', explode(',', (string)$idsParam)))));

        if (empty($ids)) {
            $this->error('请先勾选要导出的订单再点击导出');
        }

        list($where, $sort, $order) = $this->buildparams();
        // 注意：此处故意不使用 $where，只按上面的 $ids 过滤

        $admin_id = $this->auth->id;
        $admin_group = AdminUserBind::getPrimaryBusinessGroupIdForAdmin($admin_id);
        $groupInfo = Db::name('auth_group')->where('id', $admin_group)->find();
        $group_identity = AdminUserBind::resolveEffectiveOrderRoleIdentity((int)$admin_group);
        $scopeBindRegional = AdminUserBind::orderListUsesBindRegionalScope($group_identity);
        if ($this->orderListTreatAsUnscopedAdminView((int)$admin_group, $group_identity)) {
            $scopeBindRegional = false;
        }
        $backend_status = '';
        if ($scopeBindRegional) {
            if ($group_identity == 2) {
                $backend_status = 1;
            } elseif ($group_identity == 3) {
                $backend_status = 2;
            }
        }

        $grabbedOrderIds = [];
        if ($scopeBindRegional || AdminUserBind::lineDispatchParentIsSuperAdminGroup((int)$admin_group, $group_identity)) {
            $grabbedOrderIds = ((int)$group_identity === 3)
                ? AdminUserBind::getOrderIdsGrabbedByDispatchRole()
                : AdminUserBind::getAllGrabbedOrderIds();
        }

        // 强制只按勾选的 ids 查：用 Db::name 避免模型全局 scope 导致查出全部
        $query = Db::name('order')
            ->where('id', 'in', $ids);
        if (!empty($grabbedOrderIds)) {
            $query->whereNotIn('id', $grabbedOrderIds);
        }
        if ((int)$group_identity === 3) {
            $query->where('find_car_type', '配车')->where('backend_status', 2);
        }
//            ->where('pay_status', '<>', 5)
//            ->order($sort, $order);
//        if ($admin_group != 1) {
//            if ($admin_group == 27) {
//                $query->where('find_car_type', '配车')->whereNotIn('pay_status', [4, 8]);
//            } else {
//                $query->whereNotIn('pay_status', [4, 8]);
//            }
//            if ($backend_status !== '') {
//                $query->where('backend_status', $backend_status);
//            }
//            if (!empty($grabbedOrderIds)) {
//                $query->whereNotIn('id', $grabbedOrderIds);
//            }
//        }
        $total = $query->count();
//        echo  222;die;
        if (!$total) {
            $this->error('暂无可导出的数据');
        }
    $arr = [
        'ids' => $ids,
        'name'=>$groupInfo['name'],
        'createtime' => time()
    ];
//        $res = Db::name('areas')->insert($arr);
//        if ($res){
//            $this->success('数据保存成功');
//        }
        $findCarTypeList = $this->model->getFindCarTypeList();
        $payTypeList = $this->model->getPayTypeList();
        $payStatusList = $this->model->getPayStatusList();
        $logisticsStatusList = $this->model->getLogisticsStatusList();

        $columns = [
            'id'                  => 'ID',
            'orderid'             => '订单编号',
            'find_car_type'       => '找车类型',
            'username'            => '下单人',
            'mobile'              => '下单人手机',
            'loading'             => '装货地址详情',
            'unload'              => '卸货地址详情',
            'order_address'       => '地址(装货---卸货)',
            'address_contact'     => '装卸货联系人/公司',
            'goods_type_id'       => '货物类型',
            'goods_name'          => '货物名称',
            'quantity'            => '数量',
            'weight'              => '重量(吨)',
            'direction'           => '体积(方)',
            'packaging_id'        => '包装',
            'car_type_id'         => '车型',
            'delivery_type_id'    => '运输方式',
            'receipt_type_id'     => '回单类型',
            'unpack_id'           => '拆包装',
            'other_id'            => '其他',
            'pay_type'            => '支付方式',
            'payment_method'      => '付款方式',
            'information'         => '信息费',
            'deposit'             => '定金',
            'pay_price'           => '应收/实付',
            'logistics_cost'      => '专线费',
            'pickup_fee'          => '取货费',
            'shipment_fee'        => '送货费',
            'logistics_driver_cost' => '专线司机成本',
            'pickup_driver_fee'   => '取货司机费',
            'shipment_driver_fee' => '送货司机费',
            'pay_status'          => '支付状态',
            'logistics_status'    => '物流状态',
            'is_pay_salary'       => '是否已发工资',
            'createtime'          => '下单时间',
            'earliest_time'       => '最早装货时间',
            'latest_time'         => '最晚装货时间',
            'timeout'             => '是否超时',
            'isinvoice'           => '发票',
            'delivery'            => '派送/自提',
            'control'             => '控货方式',
            'remarks'              => '备注',
        ];

        $filename = 'order_' . date('Ymd_His') . '.csv';
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $handle = fopen('php://output', 'w');
        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($handle, array_values($columns));

        $chunkSize = 200;
        $page = 1;

        while (true) {
            $rows =Db::name('order')
                ->where('id', 'in', $ids)->page($page, $chunkSize)->select();
//            $rows = $query->->select();

            if (!$rows || !count($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $v = is_array($row) ? $row : $row->toArray();
                $loading_address = Db::name('user_address')->where('id', $v['loading'])->find();
                $unload_address = Db::name('user_address')->where('id', $v['unload'])->find();
                $loadingStr = '';
                $unloadStr = '';
                if ($loading_address) {
                    $loadingStr = $loading_address['user_name'] . '-' . $loading_address['mobile'] . '-' . ($loading_address['address'] ?? '') . '-' . ($loading_address['detailed_address'] ?? '');
                }
                if ($unload_address) {
                    $unloadStr = $unload_address['user_name'] . '-' . $unload_address['mobile'] . '-' . ($unload_address['address'] ?? '') . '-' . ($unload_address['detailed_address'] ?? '');
                }
                $loadingDetail = $loading_address ? ($loading_address['detailed_address'] ?? $loading_address['address'] ?? '') : '';
                $unloadDetail = $unload_address ? ($unload_address['detailed_address'] ?? $unload_address['address'] ?? '') : '';
                $orderAddress = (function_exists('extractProvinceCityEnhanced') ? extractProvinceCityEnhanced($loadingDetail) : $loadingDetail) . '---' . (function_exists('extractProvinceCityEnhanced') ? extractProvinceCityEnhanced($unloadDetail) : $unloadDetail);
                $ln = ($loading_address && isset($loading_address['user_name'])) ? $loading_address['user_name'] : '';
                $lnCompany = ($loading_address && !empty($loading_address['company_name'])) ? $loading_address['company_name'] : '';
                $un = ($unload_address && isset($unload_address['user_name'])) ? $unload_address['user_name'] : '';
                $unCompany = ($unload_address && !empty($unload_address['company_name'])) ? $unload_address['company_name'] : '';
                $addressContact = trim(implode(' ', array_filter([$ln, $lnCompany, $un, $unCompany])));

                $payment_method = Db::name('payment_method')->where('id', $v['payment_method_id'])->find();
                $paymentMethodStr = '';
                if ($payment_method) {
                    if (!empty($payment_method['p_id'])) {
                        $p_name = Db::name('payment_method')->where('id', $payment_method['p_id'])->value('payment_method');
                        $paymentMethodStr = $p_name . '---' . $payment_method['payment_method'];
                    } else {
                        $paymentMethodStr = $payment_method['payment_method'];
                    }
                }
                $userId = isset($v['userid']) ? (int)$v['userid'] : 0;
                $out = [
                    'id' => $v['id'] ?? '',
                    'orderid' => $v['orderid'] ?? '',
                    'find_car_type' => isset($v['find_car_type']) ? ($findCarTypeList[$v['find_car_type']] ?? $v['find_car_type']) : '',
                    'username' => $userId ? (Db::name('user')->where('id', $userId)->value('username') ?: '') : '',
                    'mobile' => $userId ? (Db::name('user')->where('id', $userId)->value('mobile') ?: '') : '',
                    'loading' => $loadingStr,
                    'unload' => $unloadStr,
                    'order_address' => $orderAddress,
                    'address_contact' => $addressContact,
                    'goods_type_id' => !empty($v['goods_type_id']) ? (Db::name('goods_type')->where('id', $v['goods_type_id'])->value('name') ?: '') : '',
                    'goods_name' => $v['goods_name'] ?? '',
                    'quantity' => $v['quantity'] ?? '',
                    'weight' => $v['weight'] ?? '',
                    'direction' => $v['direction'] ?? '',
                    'packaging_id' => !empty($v['packaging_id']) ? (Db::name('packaging')->where('id', $v['packaging_id'])->value('name') ?: '') : '',
                    'car_type_id' => !empty($v['car_type_id']) ? (Db::name('car_type')->where('id', $v['car_type_id'])->value('name') ?: '') : '',
                    'delivery_type_id' => !empty($v['delivery_type_id']) ? (Db::name('delivery_type')->where('id', $v['delivery_type_id'])->value('name') ?: '') : '',
                    'receipt_type_id' => !empty($v['receipt_type_id']) ? (Db::name('receipt_type')->where('id', $v['receipt_type_id'])->value('name') ?: '') : '',
                    'unpack_id' => !empty($v['unpack_id']) ? (Db::name('unpack')->where('id', $v['unpack_id'])->value('name') ?: '') : '',
                    'other_id' => !empty($v['other_id']) ? (Db::name('other')->where('id', $v['other_id'])->value('name') ?: '') : '',
                    'pay_type' => isset($v['pay_type']) ? ($payTypeList[$v['pay_type']] ?? $v['pay_type']) : '',
                    'payment_method' => $paymentMethodStr,
                    'information' => $v['information'] ?? '',
                    'deposit' => $v['deposit'] ?? '',
                    'pay_price' => $v['pay_price'] ?? '',
                    'logistics_cost' => $v['logistics_cost'] ?? '',
                    'pickup_fee' => $v['pickup_fee'] ?? '',
                    'shipment_fee' => $v['shipment_fee'] ?? '',
                    'logistics_driver_cost' => $v['logistics_driver_cost'] ?? '',
                    'pickup_driver_fee' => $v['pickup_driver_fee'] ?? '',
                    'shipment_driver_fee' => $v['shipment_driver_fee'] ?? '',
                    'pay_status' => isset($v['pay_status']) ? ($payStatusList[$v['pay_status']] ?? $v['pay_status']) : '',
                    'logistics_status' => isset($v['logistics_status']) ? ($logisticsStatusList[$v['logistics_status']] ?? $v['logistics_status']) : '',
                    'is_pay_salary' => isset($v['is_pay_salary']) && $v['is_pay_salary'] == 1 ? '已发工资' : '未发工资',
                    'createtime' => isset($v['createtime']) ? date('Y-m-d H:i:s', $v['createtime']) : '',
                    'earliest_time' => isset($v['earliest_time']) ? date('Y-m-d H:i:s', $v['earliest_time']) : '',
                    'latest_time' => isset($v['latest_time']) ? date('Y-m-d H:i:s', $v['latest_time']) : '',
                    'timeout' => (isset($v['createtime']) && (time() - $v['createtime']) / 60 >= 15) ? '超时' : '未超时',
                    'isinvoice' => isset($v['isinvoice']) && $v['isinvoice'] == 1 ? 'Isinvoice 1' : 'Isinvoice 0',
                    'delivery' => $v['delivery'] ?? '',
                    'control' => $v['control'] ?? '',
                    'remarks' => $v['remarks'] ?? '',
                ];
                $outputRow = [];
                foreach ($columns as $field => $label) {
                    $outputRow[] = isset($out[$field]) ? $out[$field] : '';
                }
                fputcsv($handle, $outputRow);
            }
            $page++;
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
        fclose($handle);
        exit;
    }

    /**
     * 标记为送货（主订单列表）：ids 为 order 表主键
     */
    public function urgent()
    {
        $ids = $this->request->param('ids');
        if (is_array($ids)) {
            $ids = isset($ids[0]) ? $ids[0] : '';
        }
        $ids = (int)$ids;
        if ($ids <= 0) {
            $this->error('参数错误');
        }
        if (!Db::name('order')->where('id', $ids)->value('id')) {
            $this->error('订单不存在');
        }
        $res = Db::name('order')->where('id', $ids)->update(['is_urgent' => 1]);
        if ($res !== false) {
            $this->success('已标记为送货');
        }
        $this->error('标记失败');
    }

    /**
     * 取消送货标记：ids 为 order 表主键
     */
    public function cancelUrgent()
    {
        $ids = $this->request->param('ids');
        if (is_array($ids)) {
            $ids = isset($ids[0]) ? $ids[0] : '';
        }
        $ids = (int)$ids;
        if ($ids <= 0) {
            $this->error('参数错误');
        }
        if (!Db::name('order')->where('id', $ids)->value('id')) {
            $this->error('订单不存在');
        }
        $res = Db::name('order')->where('id', $ids)->update(['is_urgent' => 0]);
        if ($res !== false) {
            $this->success('已取消送货');
        }
        $this->error('操作失败');
    }
}
