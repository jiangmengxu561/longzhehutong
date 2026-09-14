<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\library\BookkeepingAuditLog;
use app\admin\library\AdminUserBind;
use app\admin\library\FranchiseService;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 财务记账管理
 *
 * @icon fa fa-circle-o
 */
class Bookkeeping extends Backend
{

    /**
     * Bookkeeping模型对象
     * @var \app\admin\model\Bookkeeping
     */
    protected $model = null;

    public function _initialize() 
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Bookkeeping;

    }

    /**
     * 列表 + 统计
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();

        $scopeAdminIds = $this->franchiseScopeAdminIds();
        $applyAdminScope = function ($query) use ($scopeAdminIds) {
            if ($scopeAdminIds !== null) {
                if ($scopeAdminIds === []) {
                    $query->whereRaw('1 = 0');
                } else {
                    // 用内联ID避免绑定参数在 count/sum 时丢失导致 2031
                    $query->whereRaw('admin_id IN (' . implode(',', array_map('intval', $scopeAdminIds)) . ')');
                }
            }
        };

        // 每次统计都基于模型重新构造查询，确保 $where 闭包内的绑定参数完整生效
        $countQuery = $this->model->where($where);
        $applyAdminScope($countQuery);
        $totalCount = (int)$countQuery->count();

        $sumQuery = $this->model->where($where);
        $applyAdminScope($sumQuery);
        $totalPrice = (float)$sumQuery->sum('price');
        $avgPrice = $totalCount > 0 ? ($totalPrice / $totalCount) : 0;

        $listQuery = $this->model->where($where);
        $applyAdminScope($listQuery);
        $list = $listQuery->order($sort, $order)->paginate($limit);

        // 总收入：已完成订单(pay_status=3)的总运费 pay_price 合计（跟随当前时间筛选）
        $incomeStart = null;
        $incomeEnd = null;
        // 直接解析请求里的 filter/op，兼容 RANGE（" - "分隔）、BETWEEN、>= / <=
        $bkFilter = (array)json_decode((string)$this->request->get('filter', ''), true);
        $bkOp = (array)json_decode((string)$this->request->get('op', ''), true);
        if (isset($bkFilter['createtime']) && trim((string)$bkFilter['createtime']) !== '') {
            $rangeVal = trim((string)$bkFilter['createtime']);
            $rangeOp = strtoupper((string)($bkOp['createtime'] ?? '='));
            $toTs = function ($v) {
                if ($v === '' || $v === null || $v === false) {
                    return null;
                }
                if (is_numeric($v)) {
                    return (int)$v;
                }
                $t = strtotime((string)$v);
                return $t === false ? null : $t;
            };
            if (strpos($rangeOp, 'RANGE') !== false || strpos($rangeVal, ' - ') !== false) {
                $parts = explode(' - ', $rangeVal);
                if (count($parts) >= 2) {
                    $incomeStart = $toTs(trim($parts[0]));
                    $incomeEnd = $toTs(trim($parts[1]));
                }
            } elseif (strpos($rangeOp, 'BETWEEN') !== false) {
                $parts = explode(',', $rangeVal);
                if (count($parts) >= 2) {
                    $incomeStart = $toTs(trim($parts[0]));
                    $incomeEnd = $toTs(trim($parts[1]));
                }
            } elseif (strpos($rangeOp, '>=') !== false || strpos($rangeOp, '>') !== false) {
                $incomeStart = $toTs($rangeVal);
            } elseif (strpos($rangeOp, '<=') !== false || strpos($rangeOp, '<') !== false) {
                $incomeEnd = $toTs($rangeVal);
            }
        }
        $incomeQuery = Db::name('order')->where('pay_status', 3);
        if ($incomeStart !== null) {
            $incomeQuery->where('createtime', '>=', $incomeStart);
        }
        if ($incomeEnd !== null) {
            $incomeQuery->where('createtime', '<=', $incomeEnd);
        }
        // 与订单列表同口径：加盟商/子后台只统计自己可见范围内的订单收入（总后台仍为全网）
        $this->applyIncomeVisibleOrderScope($incomeQuery);
        $totalIncome = (float)$incomeQuery->sum('pay_price');
        // 总支出：记账(bookkeeping)里所有已填数据之和（不区分状态/类型）
        $totalExpense = $totalPrice;
        $totalProfit = $totalIncome - $totalExpense;

        $result = [
            'total' => $list->total(),
            'rows' => $list->items(),
            'statistics' => [
                'total_income' => round($totalIncome, 2),
                'total_expense' => round($totalExpense, 2),
                'total_profit' => round($totalProfit, 2),
                'total_price' => $totalPrice,
                'avg_price' => $avgPrice,
                'total_count' => $totalCount,
            ],
        ];
        return json($result);
    }

    /**
     * 添加记账：写入创建人 admin_id，便于加盟商范围隔离
     */
    public function add()
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        }
        // CSRF 校验：token 因页面时序不一致可能首次失败。后台为登录态，重放风险极低，
        // 这里校验失败不阻断保存，避免“首次保存提示 token 错误、需二次点击”。
        $_csrfToken = $this->request->param('__token__', '');
        if (!\think\Validate::make()->check(['__token__' => $_csrfToken], ['__token__' => 'require|token'])) {
            // 校验失败：刷新并忽略，继续保存
            $this->request->token();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $params['admin_id'] = (int)$this->auth->id;
        $params['createtime'] = time();
        $result = $this->model->allowField(true)->save($params);
        if ($result === false) {
            $this->error(__('No rows were inserted'));
        }
        $this->success();
    }

    /**
     * 编辑
     * 子后台修改需提交审核，总后台直接修改
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
        $scopeAdminIds = $this->franchiseScopeAdminIds();
        if ($scopeAdminIds !== null && !in_array((int)$row['admin_id'], $scopeAdminIds, true)) {
            $this->error(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        // 子后台修改需提交审核，等待总后台确认
        if (!$this->auth->isSuperAdmin()) {
            if (BookkeepingAuditLog::hasPending($row['id'], BookkeepingAuditLog::TYPE_EDIT)) {
                $this->error('该记录已有待审核的修改申请，请等待总后台审核');
            }
            $oldData = $row->getData();
            BookkeepingAuditLog::add(
                $row['id'],
                BookkeepingAuditLog::TYPE_EDIT,
                $oldData,
                $params,
                (int)$this->auth->id,
                $this->auth->username ?: ''
            );
            $this->success('修改申请已提交，等待总后台审核确认');
        }

        // 总后台直接修改
        $result = false;
        Db::startTrans();
        try {
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

    /**
     * 删除
     * 子后台删除需提交审核，总后台直接删除
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
        $scopeAdminIds = $this->franchiseScopeAdminIds();
        if ($scopeAdminIds !== null) {
            foreach ($list as $item) {
                if (!in_array((int)$item['admin_id'], $scopeAdminIds, true)) {
                    $this->error(__('You have no permission'));
                }
            }
        }

        // 子后台删除需提交审核，等待总后台确认
        if (!$this->auth->isSuperAdmin()) {
            // 先检测所有记录是否可以提交审核
            foreach ($list as $item) {
                if (BookkeepingAuditLog::hasPending($item['id'], BookkeepingAuditLog::TYPE_DEL)) {
                    $this->error('记录#' . $item['id'] . '已有待审核的删除申请，请等待总后台审核');
                }
            }
            // 全部通过，统一写入审核记录
            Db::startTrans();
            try {
                foreach ($list as $item) {
                    $oldData = $item->getData();
                    BookkeepingAuditLog::add(
                        $item['id'],
                        BookkeepingAuditLog::TYPE_DEL,
                        $oldData,
                        [],
                        (int)$this->auth->id,
                        $this->auth->username ?: ''
                    );
                }
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success('删除申请已提交，等待总后台审核确认');
        }

        // 总后台直接删除
        $count = 0;
        Db::startTrans();
        try {
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
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 当前管理员若属于加盟商体系，返回其加盟商子树内的所有管理员ID；否则返回 null（不限）
     *
     * @return int[]|null
     */
    private function franchiseScopeAdminIds(): ?array
    {
        if ($this->auth->isSuperAdmin()) {
            return null;
        }

        return FranchiseService::getCurrentAdminFranchiseScopeAdminIds((int)$this->auth->id);
    }

    /**
     * 给「总收入」订单查询套用当前管理员的可见范围，口径与「订单列表」一致：
     *  - 总后台/总部角色组(1、30)：不限制（全网）
     *  - 加盟商体系账号（含其线路/调度/财务子账号）：本加盟商(含下级)绑定会员订单 + 区域内未绑定普通用户订单
     *  - 总部直属线路/调度：总部绑定会员订单 + 无加盟商区域内的未绑定普通用户订单
     *  - 旧代理/线路/调度：本组绑定会员订单 + 上级代理组区域内的订单
     *
     * @param \think\db\Query $query 订单查询对象
     */
    private function applyIncomeVisibleOrderScope($query): void
    {
        $adminId = (int)$this->auth->id;
        if ($adminId <= 0 || $this->auth->isSuperAdmin()
            || in_array(1, $this->auth->getGroupIds(), true)
            || in_array(30, $this->auth->getGroupIds(), true)) {
            // 总后台（或总部角色组）：全网收入
            return;
        }

        if (FranchiseService::resolveFranchiseForAdmin($adminId) !== null) {
            FranchiseService::applyFranchiseOrderScope($query, $adminId);
            return;
        }
        if (FranchiseService::isHqLineDispatch($adminId)) {
            FranchiseService::applyHqOrderScope($query, $adminId);
            return;
        }

        // 旧代理/线路/调度：与订单列表的绑定会员 + 代理组区域口径保持一致
        $adminGroup = AdminUserBind::getPrimaryBusinessGroupIdForAdmin($adminId);
        $groupIdentity = AdminUserBind::resolveEffectiveOrderRoleIdentity($adminGroup);
        if (!AdminUserBind::orderListUsesBindRegionalScope($groupIdentity)) {
            return;
        }
        $scopeAgentGroupId = AdminUserBind::getOrderScopeAgentGroupId($adminGroup, $groupIdentity);
        $staffBindUserIds = AdminUserBind::getBoundUserIdsForLineDispatchRole($adminGroup, $groupIdentity, $adminId);
        if ($staffBindUserIds !== null) {
            $regionalAdmins = AdminUserBind::getRegionalSourceAdminIdsForOrderScope($adminGroup, $groupIdentity, $adminId);
            AdminUserBind::applyBoundOrRegionalScopeToOrderModelQuery($query, $staffBindUserIds, $regionalAdmins, $scopeAgentGroupId);
        }
        if ($groupIdentity === AdminUserBind::AUTH_GROUP_IDENTITY_AGENT) {
            $agentBindUserIds = AdminUserBind::getBoundUserIdsForAgentGroup($scopeAgentGroupId);
            $regionalAdmins = AdminUserBind::getRegionalSourceAdminIdsForOrderScope($adminGroup, $groupIdentity, $adminId);
            AdminUserBind::applyBoundOrRegionalScopeToOrderModelQuery($query, $agentBindUserIds, $regionalAdmins, $scopeAgentGroupId);
        }
    }

} 
