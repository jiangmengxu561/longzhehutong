<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\library\AdminUserBind;
use app\admin\library\FranchiseService;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\response\Json;

/**
 * 财务管理
 *
 * @icon fa fa-money
 */
class Finance extends Backend
{

    /** 
     * 无需鉴权的方法,但需要登录
     * reserve_fund_recharge 为AJAX接口,权限以菜单 finance/reserve_fund 为准,在方法内自行判断
     * @var array
     */
    protected $noNeedRight = ['reserve_fund_recharge', 'reserve_fund_deduct'];

    /** 
     * Finance模型对象 
     * @var \app\admin\model\Finance
     */
    protected $model = null;

    public function _initialize() 
    { 
        parent::_initialize();
        $this->model = new \app\admin\model\Finance;
        
        // 定义财务类型
        $this->view->assign("typeList", $this->model->getTypeList());
        // 定义财务状态
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除则需要自己控制这部分逻辑
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
            // 获取财务统计数据
            $statistics = $this->getFinanceStatistics();
            $this->view->assign('statistics', $statistics);
            return $this->view->fetch();
        }
        //如果发送的来源是 Selectpage，则转发到 Selectpage
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        
        $list = $this->model
            ->with(['admin'])
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
            
        foreach ($list as $row) {
            $row->visible(['id', 'type', 'amount', 'description', 'status', 'admin_id', 'created_at', 'updated_at']);
            $row->getRelation('admin')->visible(['nickname']);
        }
        
        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    /**
     * 添加
     *
     * @return string
     * @throws \think\Exception
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
     * 财务统计数据
     */
    private function getFinanceStatistics()
    {
        // 总收入：已完成订单(pay_status=3)的总运费 pay_price 合计
        $totalIncome = (float)Db::name('order')->where('pay_status', 3)->sum('pay_price');
        // 总支出：财务记账(bookkeeping)里所有已填数据之和（不区分状态/类型）
        $totalExpense = (float)$this->model->sum('amount');
        // 利润：总运费 - 总支出
        $totalProfit = $totalIncome - $totalExpense;
        return [
            'total_income' => round($totalIncome, 2),
            'total_expense' => round($totalExpense, 2),
            'total_profit' => round($totalProfit, 2),
        ];
    }

    /**
     * 财务报表
     */
    public function report()
    {
        if ($this->request->isAjax()) {
            $startdate = $this->request->get('startdate');
            $enddate = $this->request->get('enddate');

            // 总收入：已完成订单(pay_status=3)的总运费 pay_price 合计
            $totalIncome = Db::name('order')
                ->where('pay_status', 3);
            if ($startdate && $enddate) {
                $totalIncome->where('createtime', '>=', strtotime($startdate . ' 00:00:00'))
                    ->where('createtime', '<=', strtotime($enddate . ' 23:59:59'));
            }
            $totalIncome = (float)$totalIncome->sum('pay_price');

            // 总支出：bookkeeping 记账 price 合计
            $totalExpense = Db::name('bookkeeping');
            if ($startdate && $enddate) {
                $totalExpense->where('createtime', '>=', strtotime($startdate . ' 00:00:00'))
                    ->where('createtime', '<=', strtotime($enddate . ' 23:59:59'));
            }
            $totalExpense = (float)$totalExpense->sum('price');

            $netProfit = $totalIncome - $totalExpense;

            return json([
                'code' => 1,
                'statistics' => [
                    'total_income'  => round($totalIncome, 2),
                    'total_expense' => round($totalExpense, 2),
                    'net_profit'    => round($netProfit, 2),
                ],
            ]);
        }
        return $this->view->fetch();
    }

    /**
     * 批量确认
     */
    public function batchConfirm()
    {
        $ids = $this->request->post('ids');
        if (empty($ids)) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        
        $count = 0;
        Db::startTrans();
        try {
            foreach ($ids as $id) {
                $result = $this->model->where('id', $id)->update(['status' => 'confirmed']);
                if ($result) {
                    $count++;
                }
            }
            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        
        if ($count) {
            $this->success(__('Confirmed %s records', $count));
        } else {
            $this->error(__('No rows were updated'));
        }
    }

    /**
     * 导出财务报表
     */
    public function export()
    {
        $startdate = $this->request->get('startdate');
        $enddate = $this->request->get('enddate');

        // 总收入：已完成订单(pay_status=3)的总运费 pay_price 合计
        $totalIncome = Db::name('order')
            ->where('pay_status', 3);
        if ($startdate && $enddate) {
            $totalIncome->where('createtime', '>=', strtotime($startdate . ' 00:00:00'))
                ->where('createtime', '<=', strtotime($enddate . ' 23:59:59'));
        }
        $totalIncome = (float)$totalIncome->sum('pay_price');

        // 总支出：bookkeeping 记账 price 合计
        $totalExpense = Db::name('bookkeeping');
        if ($startdate && $enddate) {
            $totalExpense->where('createtime', '>=', strtotime($startdate . ' 00:00:00'))
                ->where('createtime', '<=', strtotime($enddate . ' 23:59:59'));
        }
        $totalExpense = (float)$totalExpense->sum('price');

        $netProfit = $totalIncome - $totalExpense;

        // 设置响应头
        $filename = '财务报表_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: application/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        // 输出CSV内容
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");

        // 写入表头
        fputcsv($output, ['项目', '金额']);
        fputcsv($output, ['总收入(订单总运费)', number_format($totalIncome, 2, '.', '')]);
        fputcsv($output, ['总支出(记账)', number_format($totalExpense, 2, '.', '')]);
        fputcsv($output, ['净利润', number_format($netProfit, 2, '.', '')]);

        fclose($output);
        exit();
    }

    /**
     * 订单费用汇总台账
     */
    public function cost_ledger()
    {
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            $this->view->assign('pageTitle', '订单费用汇总台账');
            $this->view->assign('dispatchList', $this->getDispatchAdminList());
            return $this->view->fetch('cost_ledger');
        }
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        $filters = $this->getCostLedgerFilters();
        $items = $this->collectCostLedgerItems($filters);
        $statisticsItems = $this->filterCostLedgerItemsByPaymentDate($items, $filters);
        $summaryRows = $this->buildCostLedgerOrderSummaryRows($items, $filters);
        $summaryRows = $this->filterCostLedgerSummaryByPaymentState($summaryRows, $filters);
        $total = count($summaryRows);
        $page = max(1, (int)$filters['page']);
        $limit = max(1, (int)$filters['limit']);
        $offset = ($page - 1) * $limit;
        $pageRows = array_slice($summaryRows, $offset, $limit);

        return json([
            'total' => $total,
            'rows' => array_values($pageRows),
            'statistics' => $this->buildCostLedgerStatistics($statisticsItems),
            'chart' => $this->buildCostLedgerChart($statisticsItems),
        ]);
    }

    /**
     * 调度费用汇总
     */
    public function cost_ledger_dispatch()
    {
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            $this->view->assign('pageTitle', '调度费用汇总');
            return $this->view->fetch('cost_ledger_dispatch');
        }
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }

        $filters = $this->getCostLedgerFilters();
        $dispatchId = (int)$this->request->get('dispatch_id', 0);
        if ($dispatchId > 0) {
            $filters['dispatch_id'] = $dispatchId;
        }
        $items = $this->collectCostLedgerItems($filters);
        $dispatchRows = $this->buildCostLedgerDispatchSummaryRows($items);
        $total = count($dispatchRows);
        $page = max(1, (int)$filters['page']);
        $limit = max(1, (int)$filters['limit']);
        $offset = ($page - 1) * $limit;
        $pageRows = array_slice($dispatchRows, $offset, $limit);

        return json([
            'total' => $total,
            'rows' => array_values($pageRows),
            'statistics' => $this->buildCostLedgerStatistics($items),
            'chart' => $this->buildCostLedgerChart($items),
        ]);
    }

    /**
     * 订单费用明细
     */
    public function cost_ledger_detail()
    {
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            $this->view->assign('pageTitle', '订单费用明细');
            $this->view->assign('detailOrderId', (int)$this->request->get('order_id', 0));
            $this->view->assign('detailDispatchId', (int)$this->request->get('dispatch_id', 0));
            return $this->view->fetch('cost_ledger_detail');
        }
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }

        $filters = $this->getCostLedgerFilters();
        $orderId = (int)$this->request->get('order_id', 0);
        if ($orderId > 0) {
            $filters['order_id'] = $orderId;
            // 指定了具体订单时，不按时间等条件筛选，直接查看该订单的明细
            $filters['startdate'] = '';
            $filters['enddate'] = '';
            $filters['orderid'] = '';
            $filters['username'] = '';
            $filters['dispatch_id'] = 0;
        }
        $dispatchId = (int)$this->request->get('dispatch_id', 0);
        if ($dispatchId > 0) {
            $filters['dispatch_id'] = $dispatchId;
        }
        $items = $this->collectCostLedgerItems($filters);
        if ($orderId > 0) {
            $items = array_values(array_filter($items, function ($item) use ($orderId) {
                return (int)($item['order_pk'] ?? 0) === $orderId;
            }));
        }
        $detailRows = $this->buildCostLedgerDetailRows($items, $filters);
        $total = count($detailRows);
        $page = max(1, (int)$filters['page']);
        $limit = max(1, (int)$filters['limit']);
        $offset = ($page - 1) * $limit;
        $pageRows = array_slice($detailRows, $offset, $limit);

        return json([
            'total' => $total,
            'rows' => array_values($pageRows),
            'statistics' => $this->buildCostLedgerStatistics($items),
            'chart' => $this->buildCostLedgerChart($items),
        ]);
    }

    private function getCostLedgerFilters(): array
    {
        $page = (int)$this->request->get('page', 1);
        $limit = (int)$this->request->get('limit', 0);
        if ($limit <= 0) {
            $limit = (int)$this->request->get('pageSize', 0);
        }
        if ($limit <= 0) {
            $limit = (int)$this->request->get('rows', 0);
        }
        if ($limit <= 0) {
            $limit = 20;
        }
        $startdate = trim((string)$this->request->get('startdate', ''));
        $enddate = trim((string)$this->request->get('enddate', ''));
        if ($startdate === '' || $enddate === '') {
            [$startdate, $enddate] = $this->getDefaultDateRange();
        }
        return [
            'startdate' => $startdate,
            'enddate' => $enddate,
            'orderid' => trim((string)$this->request->get('orderid', '')),
            'username' => trim((string)$this->request->get('username', '')),
            'dispatch_id' => (int)$this->request->get('dispatch_id', 0),
            'payment_state' => trim((string)$this->request->get('payment_state', 'all')),
            'page' => $page > 0 ? $page : 1,
            'limit' => $limit > 0 ? min($limit, 100) : 20,
        ];
    }

    private function getDefaultDateRange(): array
    {
        $today = time();
        return [date('Y-m-d', $today), date('Y-m-d', $today)];
    }

    private function currentFinanceScopeAll(): bool
    {
        if ($this->auth->isSuperAdmin()) {
            return true;
        }
        $groupIds = $this->auth->getGroupIds();
        if (in_array(1, $groupIds, true) || in_array(30, $groupIds, true)) {
            return true;
        }
        $groupId = (int)Db::name('auth_group_access')->where('uid', (int)$this->auth->id)->value('group_id');
        return $groupId === 1;
    }

    private function currentDispatchAdminId(): int
    {
        return (int)$this->auth->id;
    }

    private function collectCostLedgerItems(array $filters): array
    {
        $query = Db::name('order')->alias('o')
            ->field('o.*,u.username as user_username,u.nickname as user_nickname,u.mobile as user_mobile')
            ->join('user u', 'u.id = o.userid', 'LEFT')
            ->where('o.pay_status', '<>', 5);

        if (!$this->currentFinanceScopeAll()) {
            $franchise = FranchiseService::getFranchiseByAdminId((int)$this->auth->id);
            if ($franchise) {
                // 加盟商管理员：看本加盟商绑定会员的订单费用
                $memberIds = FranchiseService::getScopeMemberIds((int)$franchise['id']);
                if ($memberIds === []) {
                    return [];
                }
                $query->whereRaw('o.userid IN (' . implode(',', array_map('intval', $memberIds)) . ')');
            } else {
                $groupId = (int)Db::name('auth_group_access')->where('uid', (int)$this->auth->id)->value('group_id');
                $identity = (int)Db::name('auth_group')->where('id', $groupId)->value('identity');
                if ($identity === 3) {
                    $orderIds = Db::name('admin_order')->where('admin_id', $this->currentDispatchAdminId())->column('order_id');
                    if (empty($orderIds)) {
                        return [];
                    }
                    $query->where('o.id', 'in', $orderIds);
                } else {
                    return [];
                }
            }
        }

        if (!empty($filters['orderid'])) {
            $query->where('o.orderid', 'like', '%' . $filters['orderid'] . '%');
        }

        if (!empty($filters['username'])) {
            $userIds = Db::name('user')->where('username|mobile', 'like', '%' . $filters['username'] . '%')->column('id');
            if (empty($userIds)) {
                return [];
            }
            $query->where('o.userid', 'in', $userIds);
        }

        if (!empty($filters['dispatch_id'])) {
            // 调度筛选必须只匹配 identity=3 的 admin_order，避免同一账号/订单的线路记录混入。
            $dispatchGroupIds = Db::name('auth_group')->where('identity', 3)->column('id');
            if (empty($dispatchGroupIds)) {
                return [];
            }
            $dispatchOrderIds = Db::name('admin_order')
                ->where('admin_id', (int)$filters['dispatch_id'])
                ->where('group_id', 'in', array_map('intval', $dispatchGroupIds))
                ->column('order_id');
            if (empty($dispatchOrderIds)) {
                return [];
            }
            $query->where('o.id', 'in', array_values(array_unique(array_map('intval', $dispatchOrderIds))));
        }

        if (!empty($filters['order_id'])) {
            $orderIdList = array_values(array_filter(array_map('intval', (array)$filters['order_id'])));
            if (!empty($orderIdList)) {
                $query->where('o.id', 'in', $orderIdList);
            }
        }

        $orderRows = $query->order('o.createtime', 'desc')->select();
        if (!$orderRows) {
            return [];
        }

        $startTs = null;
        $endTs = null;
        if (!empty($filters['startdate']) && !empty($filters['enddate'])) {
            $startTs = strtotime($filters['startdate'] . ' 00:00:00');
            $endTs = strtotime($filters['enddate'] . ' 23:59:59');
        }

        $ledgerContext = $this->preloadCostLedgerContext($orderRows);

        $items = [];
        foreach ($orderRows as $row) {
            $order = is_array($row) ? $row : $row->toArray();
            $orderItems = $this->buildCostLedgerItemsForOrder($order, $ledgerContext);

            if ($startTs !== null && $endTs !== null) {
                $matched = false;
                foreach ($orderItems as $item) {
                    $paidTime = (int)($item['payment_time'] ?? $item['paid_at'] ?? 0);
                    if ($paidTime > 0 && $paidTime >= $startTs && $paidTime <= $endTs) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    continue;
                }
            }

            foreach ($orderItems as $item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private function filterCostLedgerItemsByPaymentDate(array $items, array $filters): array
    {
        $startTs = strtotime((string)($filters['startdate'] ?? '') . ' 00:00:00');
        $endTs = strtotime((string)($filters['enddate'] ?? '') . ' 23:59:59');
        if ($startTs === false || $endTs === false) {
            return [];
        }

        $filtered = [];
        foreach ($items as $item) {
            $paymentTime = (int)($item['payment_time'] ?? $item['paid_at'] ?? 0);
            if ($paymentTime > 0 && $paymentTime >= $startTs && $paymentTime <= $endTs) {
                $filtered[] = $item;
            }
        }
        return $filtered;
    }

    private function buildCostLedgerItemsForOrder(array $order, array $ledgerContext = []): array
    {
        $orderId = (int)($order['id'] ?? 0);
        if ($orderId <= 0) {
            return [];
        }
        $orderNumber = (string)($order['orderid'] ?? '');
        $username = (string)($order['user_username'] ?? '');
        if ($username === '') {
            $username = (string)($order['user_nickname'] ?? '');
        }
        if ($username === '') {
            $userId = (int)($order['userid'] ?? 0);
            $username = $ledgerContext['user_name_map'][$userId] ?? (string)Db::name('user')->where('id', $userId)->value('username');
        }
        $dispatch = $this->getOrderDispatchInfoByContext($order, $ledgerContext);
        $baseTime = (int)($order['createtime'] ?? time());
        $isSalaryPaid = (int)($order['is_pay_salary'] ?? 0) === 1;
        $salaryPaidAt = $this->pickSalaryPaidTime($order, $baseTime);

        $pickupRecord = $ledgerContext['dricerorder_map'][$orderNumber][1] ?? null;
        $lineRecord = $ledgerContext['dricerorder_map'][$orderNumber][2] ?? null;
        $shipmentRecord = $ledgerContext['dricerorder_map'][$orderNumber][3] ?? null;

        $pickupTime = $this->pickCostTime($pickupRecord, $baseTime, 'unsettime');
        $lineTime = $this->pickCostTime($lineRecord, $baseTime, 'unsettime');
        $shipmentTime = $this->pickCostTime($shipmentRecord, $baseTime, 'unsettime');

        // 货运平台 1=运满满 2=货拉拉
        $pickupDType = (int)($pickupRecord['d_type'] ?? 0);
        $shipmentDType = (int)($shipmentRecord['d_type'] ?? 0);

        $items = [];

        $items[] = $this->makeLedgerItem($order, $dispatch, 'order_base', '订单主单', '订单基础信息', 0, false, $baseTime, 0, '无费用', '');

        $pickupPaid = $pickupRecord && (int)($pickupRecord['status'] ?? 0) === 2;
        $linePaid = $lineRecord && (int)($lineRecord['status'] ?? 0) === 2;
        $shipmentPaid = $shipmentRecord && (int)($shipmentRecord['status'] ?? 0) === 2;
        $isNonMonthlyDelivery = (int)($order['is_urgent'] ?? 0) === 1 && (int)($order['pay_type'] ?? 0) !== 2;

        // 额外费用按每条 dirverother 记录单独展示、不累加；基础费拆分时仍需扣掉额外费合计
        $pickupExtraRows = [];
        $pickupExtraSum = 0;
        foreach (($ledgerContext['extra_map']['dirverother'] ?? [])[$orderNumber] ?? [] as $extraRow) {
            if ((int)($extraRow['type'] ?? 0) === 1) {
                $pickupExtraRows[] = $extraRow;
                $pickupExtraSum += (float)($extraRow['price'] ?? 0);
            }
        }
        $pickupExtraSum = round($pickupExtraSum, 2);
        $shipmentExtraRows = [];
        $shipmentExtraSum = 0;
        foreach (($ledgerContext['extra_map']['dirverother'] ?? [])[$orderNumber] ?? [] as $extraRow) {
            if ((int)($extraRow['type'] ?? 0) === 2) {
                $shipmentExtraRows[] = $extraRow;
                $shipmentExtraSum += (float)($extraRow['price'] ?? 0);
            }
        }
        $shipmentExtraSum = round($shipmentExtraSum, 2);
        // 物流额外成本同样逐条展示、不累加；干线基础费拆分时仍需扣掉物流额外费合计
        $logisticsExtraRows = [];
        $logisticsExtraSum = 0;
        foreach (($ledgerContext['extra_map']['logistics_extra_price'] ?? [])[$orderNumber] ?? [] as $extraRow) {
            $logisticsExtraRows[] = $extraRow;
            $logisticsExtraSum += (float)($extraRow['price'] ?? 0);
        }
        $logisticsExtraSum = round($logisticsExtraSum, 2);
        $orderExtraSum = $this->sumExtraPrices($ledgerContext['extra_map']['order_extra_price'] ?? [], $orderNumber);
        $costExtraSum = $this->sumExtraPrices($ledgerContext['extra_map']['cost_extra_price'] ?? [], $orderNumber);
        $receiptPrice = (float)($order['receipt_type_price'] ?? 0);
        $unpackPrice = (float)($order['unpack_price'] ?? 0);
        $infoFee = (float)($order['information'] ?? 0);
        $depositFee = (float)($order['deposit'] ?? 0);

        // 统一口径：基础费字段已包含额外费用，此处将基础费拆分为“净基础费 + 额外费”两条明细，
        // 基础费 = 原基础费 - 额外费，额外费单独计入，二者合计与原有基础费一致，下游所有统计共用同一金额。
        // 取货基础费直接取 dricerorder（司机订单）的金额；额外费逐条单独展示，二者不再互相扣减
        if ($pickupRecord && isset($pickupRecord['price']) && $pickupRecord['price'] !== '' && $pickupRecord['price'] !== null) {
            $pickupBaseFee = (float)$pickupRecord['price'];
        } else {
            $pickupBaseFee = (float)($order['pickup_driver_fee'] ?? 0);
        }
        $pickupNetBase = max(0.0, round($pickupBaseFee, 2));
        if ($pickupBaseFee > 0) {
            $items[] = $this->makeLedgerItem($order, $dispatch, 'pickup_base', '取货费', '取货基础费', $pickupNetBase, $pickupPaid, $pickupTime, $pickupTime, $pickupPaid ? '已付款' : '未付款', $pickupRecord['remarks'] ?? '', true, $pickupDType);
        }
        foreach ($pickupExtraRows as $extraRow) {
            $extraRemark = trim((string)($extraRow['remarks'] ?? ''));
            if ($extraRemark === '') {
                $extraRemark = '取货司机额外费用';
            }
            $items[] = $this->makeLedgerItem($order, $dispatch, 'pickup_extra', '取货费', '取货额外费', (float)($extraRow['price'] ?? 0), $pickupPaid, $pickupTime, $pickupTime, $pickupPaid ? '已付款' : '未付款', $extraRemark, true, $pickupDType);
        }
        // 干线费以订单字段为准：仅当订单未记录(NULL/空)时才回退到专线调度单价格
        $lineBaseRaw = $order['logistics_driver_cost'] ?? null;
        $lineBaseFee = ($lineBaseRaw === null || $lineBaseRaw === '') ? 0.0 : (float)$lineBaseRaw;
        if ($lineBaseFee <= 0 && $lineRecord && isset($lineRecord['price'])) {
            if ($lineBaseRaw === null || $lineBaseRaw === '') {
                $lineBaseFee = (float)$lineRecord['price'];
            }
        }
        $lineNetBase = max(0.0, round($lineBaseFee - $logisticsExtraSum, 2));
        if ($lineBaseFee > 0) {
            $items[] = $this->makeLedgerItem($order, $dispatch, 'logistics_base', '干线费', '干线基础费', $lineNetBase, $linePaid, $lineTime, $lineTime, $linePaid ? '已付款' : '未付款', $lineRecord['remarks'] ?? '');
        }
        foreach ($logisticsExtraRows as $extraRow) {
            $extraRemark = trim((string)($extraRow['remarks'] ?? ''));
            if ($extraRemark === '') {
                $extraRemark = '物流额外费用';
            }
            $items[] = $this->makeLedgerItem($order, $dispatch, 'logistics_extra', '干线费', '干线额外费', (float)($extraRow['price'] ?? 0), $linePaid, $lineTime, $lineTime, $linePaid ? '已付款' : '未付款', $extraRemark);
        }
        // 送货基础费直接取 dricerorder（司机订单）的金额；额外费逐条单独展示，二者不再互相扣减
        if ($shipmentRecord && isset($shipmentRecord['price']) && $shipmentRecord['price'] !== '' && $shipmentRecord['price'] !== null) {
            $shipmentBaseFee = (float)$shipmentRecord['price'];
        } else {
            $shipmentBaseFee = (float)($order['shipment_driver_fee'] ?? 0);
        }
        $shipmentNetBase = max(0.0, round($shipmentBaseFee, 2));
        if ($shipmentBaseFee > 0) {
            $items[] = $this->makeLedgerItem($order, $dispatch, 'shipment_base', '送货费', '送货基础费', $shipmentNetBase, $shipmentPaid, $shipmentTime, $shipmentTime, $shipmentPaid ? '已付款' : '未付款', $shipmentRecord['remarks'] ?? '', true, $shipmentDType);
        }
        foreach ($shipmentExtraRows as $extraRow) {
            $extraRemark = trim((string)($extraRow['remarks'] ?? ''));
            if ($extraRemark === '') {
                $extraRemark = '送货司机额外费用';
            }
            $items[] = $this->makeLedgerItem($order, $dispatch, 'shipment_extra', '送货费', '送货额外费', (float)($extraRow['price'] ?? 0), $shipmentPaid, $shipmentTime, $shipmentTime, $shipmentPaid ? '已付款' : '未付款', $extraRemark, true, $shipmentDType);
        }
        if ($receiptPrice > 0) {
            $items[] = $this->makeLedgerItem($order, $dispatch, 'receipt_price', '订单附加项', '回单费', $receiptPrice, $isSalaryPaid, $salaryPaidAt, $baseTime, $isSalaryPaid ? '已付款' : '未付款', '回单费用');
        }
        if ($unpackPrice > 0) {
            $items[] = $this->makeLedgerItem($order, $dispatch, 'unpack_price', '订单附加项', '拆包费', $unpackPrice, $isSalaryPaid, $salaryPaidAt, $baseTime, $isSalaryPaid ? '已付款' : '未付款', '拆包费用');
        }
        if ($infoFee > 0) {
            $infoFeePaid = (int)($order['info_fee_paid_at'] ?? 0) > 0;
            $infoFeePaidAt = $infoFeePaid ? (int)$order['info_fee_paid_at'] : $baseTime;
            $items[] = $this->makeLedgerItem($order, $dispatch, 'info_fee', '订单附加项', '信息费', $infoFee, $infoFeePaid, $infoFeePaidAt, $infoFeePaidAt, $infoFeePaid ? '已付款' : '未付款', '信息费');
        }
        if ($depositFee > 0) {
            $depositPaid = (int)($order['info_fee_paid_at'] ?? 0) > 0;
            $depositPaidAt = $depositPaid ? (int)$order['info_fee_paid_at'] : $baseTime;
            $items[] = $this->makeLedgerItem($order, $dispatch, 'deposit_fee', '订单附加项', '定金', $depositFee, $depositPaid, $depositPaidAt, $depositPaidAt, $depositPaid ? '已付款' : '未付款', '定金');
        }
        $monthlyReserveProfit = 0;
        $monthlyReserveProfitDeducted = 0;
        if ((int)($order['pay_type'] ?? 0) === 2 && (int)($order['is_urgent'] ?? 0) === 1 && $shipmentRecord && (int)($shipmentRecord['status'] ?? 0) === 2) {
            $monthlyReserveProfit = max(0.0, round((float)($order['pay_price'] ?? 0) - (float)($order['cost_cont'] ?? 0), 2));
            $monthlyReserveProfitDeducted = $monthlyReserveProfit;
        }
        if ($orderExtraSum > 0) {
            $items[] = $this->makeLedgerItem($order, $dispatch, 'order_extra', '订单附加项', '订单额外费', $orderExtraSum, $isSalaryPaid, $salaryPaidAt, $baseTime, $isSalaryPaid ? '已付款' : '未付款', '订单额外费用');
        }
        if ($monthlyReserveProfitDeducted > 0) {
            $items[] = $this->makeLedgerItem($order, $dispatch, 'monthly_reserve_profit', '其他费用', '月结订单利润', $monthlyReserveProfitDeducted, true, $shipmentTime, $shipmentTime, '已付款', '月结订单利润', true, 0, $monthlyReserveProfitDeducted, $monthlyReserveProfit);
        }
        if ($costExtraSum > 0) {
            $items[] = $this->makeLedgerItem($order, $dispatch, 'cost_extra', '订单附加项', '成本补充费', $costExtraSum, $isSalaryPaid, $salaryPaidAt, $baseTime, $isSalaryPaid ? '已付款' : '未付款', '成本补充费用');
        }

        return $items;
    }

    private function makeLedgerItem(array $order, array $dispatch, string $typeKey, string $groupName, string $feeName, float $amount, bool $paid, int $bizTime, int $paidAt, string $statusText, string $remark = '', bool $countInSummary = true, int $dType1 = 0, float $monthlyReserveProfitDeducted = 0, float $monthlyReserveProfit = 0): array
    {
        $isShipmentItem = in_array($typeKey, ['shipment_base', 'shipment_extra'], true);
        $isNonMonthlyOrder = (int)($order['is_urgent'] ?? 0) === 1 && (int)($order['pay_type'] ?? 0) !== 2;
        $isNonMonthlyShipment = $isShipmentItem && $isNonMonthlyOrder;
        $paidClass = $isNonMonthlyShipment
            ? 'ledger-shipment-black'
            : ($paid ? 'ledger-paid text-success' : 'ledger-unpaid text-danger');
        return [
            'order_pk' => (int)($order['id'] ?? 0),
            'orderid' => (string)($order['orderid'] ?? ''),
            'username' => (string)($order['user_username'] ?? ''),
            'dispatch_id' => (int)($dispatch['admin_id'] ?? 0),
            'dispatch_name' => (string)($dispatch['name'] ?? ''),
            'type_key' => $typeKey,
            'fee_group' => $groupName,
            'fee_name' => $feeName,
            'amount' => round($amount, 2),
            'd_type' => (int)$dType1,
            'platform_name' => $this->driverPlatformName($dType1),
            'paid' => $paid ? 1 : 0,
            'paid_class' => $paidClass,
            'paid_text' => $statusText,
            'biz_time' => $bizTime,
            'biz_date' => date('Y-m-d', $bizTime),
            'paid_at' => ($paid && $paidAt > 0) ? $paidAt : 0,
            'paid_date' => ($paid && $paidAt > 0) ? date('Y-m-d', $paidAt) : '',
            'paid_at_text' => ($paid && $paidAt > 0 && !$isNonMonthlyShipment) ? date('Y-m-d H:i:s', $paidAt) : '-',
            'payment_time' => ($paid && $paidAt > 0) ? $paidAt : 0,
            'payment_date' => ($paid && $paidAt > 0) ? date('Y-m-d', $paidAt) : '',
            'payment_time_text' => ($paid && $paidAt > 0) ? date('Y-m-d H:i:s', $paidAt) : '-',
            'logistics_paid_at_text' => ($paid && $paidAt > 0 && $typeKey === 'logistics_base') ? date('Y-m-d H:i:s', $paidAt) : '-',
            'shipment_paid_at_text' => ($paid && $paidAt > 0 && $typeKey === 'shipment_base') ? date('Y-m-d H:i:s', $paidAt) : '-',
            'is_non_monthly_shipment' => $isNonMonthlyShipment ? 1 : 0,
            'is_non_monthly_delivery' => $isNonMonthlyOrder ? 1 : 0,
            'monthly_reserve_profit_deducted' => $monthlyReserveProfitDeducted > 0 ? 1 : 0,
            'monthly_reserve_profit' => round($monthlyReserveProfit, 2),
            'cost_paid_at' => ($paid && $paidAt > 0) ? $paidAt : 0,
            'sort_time' => ($paid && $paidAt > 0) ? $paidAt : $bizTime,
            'remarks' => $remark,
            'count_in_summary' => $countInSummary ? 1 : 0,
            'pay_price' => (float)($order['pay_price'] ?? 0),
            'cost_cont' => (float)($order['cost_cont'] ?? 0),
            'profit' => round((float)($order['pay_price'] ?? 0) - (float)($order['cost_cont'] ?? 0), 2),
        ];
    }

    /**
     * 货运平台名称 1=运满满 2=货拉拉
     */
    private function driverPlatformName($dType1): string
    {
        $dType1 = (int)$dType1;
        if ($dType1 === 1) {
            return '运满满';
        }
        if ($dType1 === 2) {
            return '货拉拉';
        }
        return '';
    }
    private function getOrderDispatchInfo(int $orderId): array
    {
        if ($orderId <= 0) {
            return ['admin_id' => 0, 'name' => '未分配'];
        }

        $adminId = 0;
        $rows = Db::query('SELECT admin_id FROM fa_admin_order WHERE order_id = ? AND admin_id > 0 ORDER BY id DESC LIMIT 1', [$orderId]);
        if (!empty($rows[0]['admin_id'])) {
            $adminId = (int)$rows[0]['admin_id'];
        }

        if ($adminId <= 0) {
            $orderNumber = (string)Db::name('order')->where('id', $orderId)->value('orderid');
            if ($orderNumber !== '') {
                $rows = Db::query('SELECT admin_id FROM fa_admin_order WHERE order_id = ? AND admin_id > 0 ORDER BY id DESC LIMIT 1', [$orderNumber]);
                if (!empty($rows[0]['admin_id'])) {
                    $adminId = (int)$rows[0]['admin_id'];
                }
            }
        }

        if ($adminId <= 0) {
            return ['admin_id' => 0, 'name' => '未分配'];
        }

        $name = (string)Db::name('admin')->where('id', $adminId)->value('nickname');
        if ($name === '') {
            $name = (string)Db::name('admin')->where('id', $adminId)->value('username');
        }

        return [
            'admin_id' => $adminId,
            'name' => $name !== '' ? $name : '调度',
        ];
    }

    private function getOrderDispatchInfoByContext(array $order, array $ledgerContext = []): array
    {
        $orderId = (int)($order['id'] ?? 0);
        if ($orderId <= 0) {
            return ['admin_id' => 0, 'name' => '未分配'];
        }
        if (!empty($ledgerContext['dispatch_map'][$orderId])) {
            return $ledgerContext['dispatch_map'][$orderId];
        }
        $dispatch = $this->getOrderDispatchInfo($orderId);
        if (!empty($dispatch['admin_id'])) {
            return $dispatch;
        }
        $orderNumber = (string)($order['orderid'] ?? '');
        if ($orderNumber !== '') {
            $dispatch = $this->getOrderDispatchInfoByOrderNumber($orderNumber);
            if (!empty($dispatch['admin_id'])) {
                return $dispatch;
            }
        }
        return ['admin_id' => 0, 'name' => '未分配'];
    }

    private function getOrderDispatchInfoByOrderNumber(string $orderNumber): array
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return ['admin_id' => 0, 'name' => '未分配'];
        }
        $adminId = (int)Db::name('admin_order')
            ->where('order_id', $orderNumber)
            ->where('admin_id', '>', 0)
            ->order('id', 'desc')
            ->value('admin_id');
        if ($adminId <= 0) {
            return ['admin_id' => 0, 'name' => '未分配'];
        }
        $name = (string)Db::name('admin')->where('id', $adminId)->value('nickname');
        if ($name === '') {
            $name = (string)Db::name('admin')->where('id', $adminId)->value('username');
        }
        return [
            'admin_id' => $adminId,
            'name' => $name !== '' ? $name : '调度',
        ];
    }
    private function preloadCostLedgerContext($orderRows): array
    {
        $orderIds = [];
        $orderNumbers = [];  
        $userIds = [];
        foreach ($orderRows as $row) {
            $order = is_array($row) ? $row : $row->toArray();
            $orderId = (int)($order['id'] ?? 0);
            $orderNumber = (string)($order['orderid'] ?? '');
            $userId = (int)($order['userid'] ?? 0);
            if ($orderId > 0) {
                $orderIds[] = $orderId;
            }
            if ($orderNumber !== '') {
                $orderNumbers[] = $orderNumber;
            }
            if ($userId > 0) {
                $userIds[] = $userId;
            }
        }
        $orderIds = array_values(array_unique($orderIds));
        $orderNumbers = array_values(array_unique($orderNumbers));
        $userIds = array_values(array_unique($userIds));

        $dispatchMap = [];
        if (!empty($orderIds)) {
            // 一个订单可能同时存在线路和调度两条 admin_order 记录：
            // 台账中的“调度”必须取 identity=3 的调度记录，不能按 id 倒序取到线路。
            $adminOrders = Db::name('admin_order')->where('order_id', 'in', $orderIds)->order('id', 'desc')->select();
            $adminMap = [];
            $adminIds = [];
            $adminGroupIds = [];
            foreach ($adminOrders as $row) {
                $oid = (int)($row['order_id'] ?? 0);
                $aid = (int)($row['admin_id'] ?? 0);
                $gid = (int)($row['group_id'] ?? 0);
                if ($oid <= 0 || $aid <= 0) {
                    continue;
                }
                if ($gid > 0) {
                    $adminGroupIds[$gid] = true;
                }
                if (!isset($adminMap[$oid])) {
                    $adminMap[$oid] = ['admin_id' => $aid, 'group_id' => $gid];
                }
                $adminIds[$aid] = true;
            }
            $dispatchGroupIds = [];
            if (!empty($adminGroupIds)) {
                $dispatchGroupIds = Db::name('auth_group')
                    ->where('id', 'in', array_keys($adminGroupIds))
                    ->where('identity', 3)
                    ->column('id');
            }
            $dispatchGroupIds = array_map('intval', $dispatchGroupIds);
            foreach ($adminOrders as $row) {
                $oid = (int)($row['order_id'] ?? 0);
                $aid = (int)($row['admin_id'] ?? 0);
                $gid = (int)($row['group_id'] ?? 0);
                if ($oid > 0 && $aid > 0 && in_array($gid, $dispatchGroupIds, true)) {
                    $adminMap[$oid] = ['admin_id' => $aid, 'group_id' => $gid];
                }
            }
            $adminIds = array_keys($adminIds);
            $adminNames = [];
            if (!empty($adminIds)) {
                $admins = Db::name('admin')->where('id', 'in', $adminIds)->field('id,nickname,username')->select();
                foreach ($admins as $admin) {
                    $aid = (int)($admin['id'] ?? 0);
                    $name = (string)($admin['nickname'] ?? '');
                    if ($name === '') {
                        $name = (string)($admin['username'] ?? '');
                    }
                    $adminNames[$aid] = $name !== '' ? $name : '调度';
                }
            }
            foreach ($adminMap as $oid => $dispatch) {
                $aid = (int)($dispatch['admin_id'] ?? 0);
                $dispatchMap[$oid] = [
                    'admin_id' => $aid,
                    'name' => $adminNames[$aid] ?? '调度',
                ];
            }
        }

        $dricerorderMap = [];
        if (!empty($orderNumbers)) {
            $records = Db::name('dricerorder')->where('order_id', 'in', $orderNumbers)->order('id', 'desc')->select();
            foreach ($records as $record) {
                $orderNumber = (string)($record['order_id'] ?? '');
                $type = (int)($record['type'] ?? 0);
                if ($orderNumber === '' || $type <= 0) {
                    continue;
                }
                if (!isset($dricerorderMap[$orderNumber])) {
                    $dricerorderMap[$orderNumber] = [];
                }
                if (!isset($dricerorderMap[$orderNumber][$type])) {
                    $dricerorderMap[$orderNumber][$type] = is_array($record) ? $record : $record->toArray();
                }
            }
        }

        $extraMap = [];
        $extraTables = ['dirverother', 'logistics_extra_price', 'order_extra_price', 'cost_extra_price'];
        foreach ($extraTables as $table) {
            if (empty($orderNumbers)) {
                continue;
            }
            $rows = Db::name($table)->where('order_id', 'in', $orderNumbers)->select();
            foreach ($rows as $row) {
                $orderNumber = (string)($row['order_id'] ?? '');
                if ($orderNumber === '') {
                    continue;
                }
                if (!isset($extraMap[$table])) {
                    $extraMap[$table] = [];
                }
                if (!isset($extraMap[$table][$orderNumber])) {
                    $extraMap[$table][$orderNumber] = [];
                }
                $extraMap[$table][$orderNumber][] = is_array($row) ? $row : $row->toArray();
            }
        }

        $userNameMap = [];
        if (!empty($userIds)) {
            $users = Db::name('user')->where('id', 'in', $userIds)->field('id,username,nickname')->select();
            foreach ($users as $user) {
                $uid = (int)($user['id'] ?? 0);
                $name = (string)($user['username'] ?? '');
                if ($name === '') {
                    $name = (string)($user['nickname'] ?? '');
                }
                $userNameMap[$uid] = $name;
            }
        }

        return [
            'dispatch_map' => $dispatchMap,
            'dricerorder_map' => $dricerorderMap,
            'extra_map' => $extraMap,
            'user_name_map' => $userNameMap,
        ];
    }

    private function pickCostTime($record, int $fallbackTime, string $preferredField = 'createtime'): int
    {
        if (!$record) {
            return $fallbackTime;
        }
        foreach ([$preferredField, 'unsettime', 'grabbingtime', 'createtime', 'updatetime'] as $field) {
            if (isset($record[$field]) && (int)$record[$field] > 0) {
                return (int)$record[$field];
            }
        }
        return $fallbackTime;
    }

    private function pickSalaryPaidTime(array $order, int $fallbackTime): int
    {
        foreach (['pay_time', 'updatetime', 'createtime'] as $field) {
            if (!empty($order[$field]) && (int)$order[$field] > 0) {
                return (int)$order[$field];
            }
        }
        return $fallbackTime;
    }

    private function sumExtraPrices(array $rowsByOrderNumber, string $orderNumber, ?int $type = null): float
    {
        if ($orderNumber === '' || empty($rowsByOrderNumber[$orderNumber])) {
            return 0.0;
        }
        $total = 0.0;
        foreach ($rowsByOrderNumber[$orderNumber] as $row) {
            if ($type !== null && (int)($row['type'] ?? 0) !== $type) {
                continue;
            }
            $total += (float)($row['price'] ?? 0);
        }
        return round($total, 2);
    }

    private function buildCostLedgerOrderSummaryRows(array $items, array $filters = []): array
    {
        $startTs = 0;
        $endTs = 0;
        if (!empty($filters['startdate']) && !empty($filters['enddate'])) {
            $startTs = strtotime((string)$filters['startdate'] . ' 00:00:00') ?: 0;
            $endTs = strtotime((string)$filters['enddate'] . ' 23:59:59') ?: 0;
        }
        // 未设置时间范围时，已付费用一律视为本期已付（绿色）
        $hasRange = $startTs > 0 && $endTs > 0;
        $defaultInRange = !$hasRange;
        $grouped = [];
        foreach ($items as $item) {
            $orderPk = (int)($item['order_pk'] ?? 0);
            if ($orderPk <= 0) {
                continue;
            }
            if (!isset($grouped[$orderPk])) {
                $grouped[$orderPk] = [
                    'order_pk' => $orderPk,
                    'orderid' => $item['orderid'] ?? '',
                    'username' => $item['username'] ?? '',
                    'dispatch_id' => (int)($item['dispatch_id'] ?? 0),
                    'dispatch_name' => $item['dispatch_name'] ?? '',
                    'biz_time' => (int)($item['biz_time'] ?? 0),
                    'biz_date' => $item['biz_date'] ?? '',
                    'pickup_driver_fee' => 0,
                    'pickup_driver_other_fee' => 0,
                    'logistics_driver_cost' => 0,
                    'logistics_extra_fee' => 0,
                    'shipment_driver_fee' => 0,
                    'shipment_driver_other_fee' => 0,
                    'pickup_yunmanman' => 0,
                    'pickup_huolala' => 0,
                    'shipment_yunmanman' => 0,
                    'shipment_huolala' => 0,
                    'receipt_type_price' => 0,
                    'unpack_price' => 0,
                    'information' => 0,
                    'deposit' => 0,
                    'order_extra_price' => 0,
                    'cost_extra_price' => 0,
                    'monthly_reserve_profit' => 0,
                    'paid_total' => 0,
                    'unpaid_total' => 0,
                    'cost_total' => 0,
                    'pay_price' => (float)($item['pay_price'] ?? 0),
                    'cost_cont' => (float)($item['cost_cont'] ?? 0),
                    'profit' => (float)($item['profit'] ?? 0),
                    'pickup_driver_fee_paid' => 0,
                    'pickup_driver_other_fee_paid' => 0,
                    'logistics_driver_cost_paid' => 0,
                    'logistics_extra_fee_paid' => 0,
                    'shipment_driver_fee_paid' => 0,
                    'shipment_driver_other_fee_paid' => 0,
                    'receipt_type_price_paid' => 0,
                    'unpack_price_paid' => 0,
                    'information_paid' => 0,
                    'deposit_paid' => 0,
                    'order_extra_price_paid' => 0,
                    'cost_extra_price_paid' => 0,
                    'pickup_paid' => 0,
                    'logistics_paid' => 0,
                    'shipment_paid' => 0,
                    'other_paid' => 0,
                    'pickup_amount' => 0,
                    'pickup_paid_amount' => 0,
                    'pickup_in_range' => $defaultInRange,
                    'logistics_amount' => 0,
                    'logistics_paid_amount' => 0,
                    'logistics_in_range' => $defaultInRange,
                    'shipment_amount' => 0,
                    'shipment_paid_amount' => 0,
                    'shipment_in_range' => $defaultInRange,
                    'other_amount' => 0,
                    'other_paid_amount' => 0,
                    'other_in_range' => $defaultInRange,
                    'payment_state' => 'unpaid',
                    'payment_state_text' => '未付款',
                    'row_class' => 'ledger-row-unpaid',
                    'payment_time' => 0,
                    'payment_date' => '',
                    'payment_time_text' => '-',
                ];
            }
            $typeKey = (string)($item['type_key'] ?? '');
            $amount = (float)($item['amount'] ?? 0);
            $feeAmount = $amount;
            $paid = !empty($item['paid']);
            $paidAtText = (string)($item['paid_at_text'] ?? '-');
            $countInSummary = !empty($item['count_in_summary']);
            if (!empty($item['is_non_monthly_delivery'])) {
                $grouped[$orderPk]['is_non_monthly_delivery'] = 1;
            }
            if ($typeKey !== 'order_base' && $amount <= 0) {
                continue;
            }
            if ($typeKey !== 'order_base' && $countInSummary) {
                $grouped[$orderPk]['cost_total'] += $feeAmount;
            }
            if ($countInSummary) {
                if ($paid) {
                    $grouped[$orderPk]['paid_total'] += $feeAmount;
                } else {
                    $grouped[$orderPk]['unpaid_total'] += $feeAmount;
                }
            }
            $itemBizTime = (int)($item['paid_at'] ?? 0);
            if ($itemBizTime <= 0) {
                $itemBizTime = (int)($item['biz_time'] ?? 0);
            }
            if (empty($grouped[$orderPk]['biz_time']) || $itemBizTime < (int)$grouped[$orderPk]['biz_time']) {
                $grouped[$orderPk]['biz_time'] = $itemBizTime;
                $grouped[$orderPk]['biz_date'] = (string)($item['paid_date'] ?? $item['biz_date'] ?? $grouped[$orderPk]['biz_date']);
            }
            if (empty($grouped[$orderPk]['sort_time']) || $itemBizTime > (int)$grouped[$orderPk]['sort_time']) {
                $grouped[$orderPk]['sort_time'] = $itemBizTime;
            }
            if ($paid && $itemBizTime > 0) {
                if (empty($grouped[$orderPk]['payment_time']) || $itemBizTime > (int)$grouped[$orderPk]['payment_time']) {
                    $grouped[$orderPk]['payment_time'] = $itemBizTime;
                    $grouped[$orderPk]['payment_date'] = (string)($item['paid_date'] ?? $item['payment_date'] ?? $grouped[$orderPk]['payment_date']);
                    $grouped[$orderPk]['payment_time_text'] = (string)($item['paid_at_text'] ?? $item['payment_time_text'] ?? $grouped[$orderPk]['payment_time_text']);
                }
            }
            if ($typeKey === 'pickup_base' || $typeKey === 'pickup_extra') {
                if ($paid) {
                    $grouped[$orderPk]['pickup_paid'] = 1;
                }
            } elseif ($typeKey === 'logistics_base' || $typeKey === 'logistics_extra') {
                if ($paid) {
                    $grouped[$orderPk]['logistics_paid'] = 1;
                }
            } elseif ($typeKey === 'shipment_base' || $typeKey === 'shipment_extra') {
                if ($paid) {
                    $grouped[$orderPk]['shipment_paid'] = 1; 
                }
            } elseif (in_array($typeKey, ['receipt_price', 'unpack_price', 'info_fee', 'deposit_fee', 'order_extra', 'cost_extra', 'monthly_reserve_profit'], true)) {
                if ($paid) {
                    $grouped[$orderPk]['other_paid'] = 1; 
                }
            }
            $catPaidAt = (int)($item['paid_at'] ?? 0);
            $catInRange = $catPaidAt > 0 && $hasRange && $catPaidAt >= $startTs && $catPaidAt <= $endTs;
            if ($typeKey === 'pickup_base' || $typeKey === 'pickup_extra') {
                $grouped[$orderPk]['pickup_amount'] += $amount;
                if ($paid) {
                    $grouped[$orderPk]['pickup_paid_amount'] += $amount;
                    if ($catInRange) {
                        $grouped[$orderPk]['pickup_in_range'] = true;
                    }
                }
            } elseif ($typeKey === 'logistics_base' || $typeKey === 'logistics_extra') {
                $grouped[$orderPk]['logistics_amount'] += $amount;
                if ($paid) {
                    $grouped[$orderPk]['logistics_paid_amount'] += $amount;
                    if ($catInRange) {
                        $grouped[$orderPk]['logistics_in_range'] = true;
                    }
                }
            } elseif ($typeKey === 'shipment_base' || $typeKey === 'shipment_extra') {
                $grouped[$orderPk]['shipment_amount'] += $amount;
                if ($paid) {
                    $grouped[$orderPk]['shipment_paid_amount'] += $amount;
                    if ($catInRange) {
                        $grouped[$orderPk]['shipment_in_range'] = true;
                    }
                }
            } elseif (in_array($typeKey, ['receipt_price', 'unpack_price', 'info_fee', 'deposit_fee', 'order_extra', 'cost_extra', 'monthly_reserve_profit'], true)) {
                $grouped[$orderPk]['other_amount'] += $amount;
                if ($paid) {
                    $grouped[$orderPk]['other_paid_amount'] += $amount;
                    if ($catInRange) {
                        $grouped[$orderPk]['other_in_range'] = true;
                    }
                }
            }
            if (!$countInSummary) {
                continue;
            } 
            $dType = (int)($item['d_type'] ?? 0);
            if ($typeKey === 'pickup_base') {
                $grouped[$orderPk]['pickup_driver_fee'] += $amount;
                $grouped[$orderPk]['pickup_driver_fee_paid_at'] = $paidAtText;
                if ($dType === 1) {
                    $grouped[$orderPk]['pickup_yunmanman'] += $amount;
                } elseif ($dType === 2) {
                    $grouped[$orderPk]['pickup_huolala'] += $amount;
                }
            } elseif ($typeKey === 'pickup_extra') {
                $grouped[$orderPk]['pickup_driver_other_fee'] += $amount;
                $grouped[$orderPk]['pickup_driver_other_fee_paid_at'] = $paidAtText;
                if ($dType === 1) {
                    $grouped[$orderPk]['pickup_yunmanman'] += $amount;
                } elseif ($dType === 2) {
                    $grouped[$orderPk]['pickup_huolala'] += $amount;
                }
            } elseif ($typeKey === 'logistics_base') {
                $grouped[$orderPk]['logistics_driver_cost'] += $amount;
                $grouped[$orderPk]['logistics_driver_cost_paid_at'] = $paidAtText;
                $grouped[$orderPk]['logistics_paid_at_text'] = $paidAtText;
            } elseif ($typeKey === 'logistics_extra') {
                $grouped[$orderPk]['logistics_extra_fee'] += $amount;
                $grouped[$orderPk]['logistics_extra_fee_paid_at'] = $paidAtText;
            } elseif ($typeKey === 'shipment_base') {
                $grouped[$orderPk]['shipment_driver_fee'] += $amount;
                $grouped[$orderPk]['shipment_driver_fee_paid_at'] = $paidAtText;
                $grouped[$orderPk]['shipment_paid_at_text'] = $paidAtText;
                if ($dType === 1) {
                    $grouped[$orderPk]['shipment_yunmanman'] += $amount;
                } elseif ($dType === 2) {
                    $grouped[$orderPk]['shipment_huolala'] += $amount;
                }
            } elseif ($typeKey === 'shipment_extra') {
                $grouped[$orderPk]['shipment_driver_other_fee'] += $amount;
                $grouped[$orderPk]['shipment_driver_other_fee_paid_at'] = $paidAtText;
                if ($dType === 1) {
                    $grouped[$orderPk]['shipment_yunmanman'] += $amount;
                } elseif ($dType === 2) {
                    $grouped[$orderPk]['shipment_huolala'] += $amount;
                }
            } elseif ($typeKey === 'receipt_price') {
                $grouped[$orderPk]['receipt_type_price'] += $amount;
                $grouped[$orderPk]['receipt_type_price_paid_at'] = $paidAtText;
            } elseif ($typeKey === 'unpack_price') {
                $grouped[$orderPk]['unpack_price'] += $amount;
                $grouped[$orderPk]['unpack_price_paid_at'] = $paidAtText;
            } elseif ($typeKey === 'info_fee') {
                $grouped[$orderPk]['information'] += $amount;
                $grouped[$orderPk]['information_paid_at'] = $paidAtText;
            } elseif ($typeKey === 'deposit_fee') {
                $grouped[$orderPk]['deposit'] += $amount;
                $grouped[$orderPk]['deposit_paid_at'] = $paidAtText;
            } elseif ($typeKey === 'order_extra') {
                $grouped[$orderPk]['order_extra_price'] += $amount;
                $grouped[$orderPk]['order_extra_price_paid_at'] = $paidAtText;
            } elseif ($typeKey === 'monthly_reserve_profit') {
                $grouped[$orderPk]['monthly_reserve_profit'] += $amount;
            } elseif ($typeKey === 'cost_extra') {
                $grouped[$orderPk]['cost_extra_price'] += $amount;
                $grouped[$orderPk]['cost_extra_price_paid_at'] = $paidAtText;
            }
        }

        foreach ($grouped as &$row) {
            $row['pickup_driver_fee'] = round($row['pickup_driver_fee'], 2);
            $row['pickup_driver_other_fee'] = round($row['pickup_driver_other_fee'], 2);
            $row['logistics_driver_cost'] = round($row['logistics_driver_cost'], 2);
            $row['logistics_extra_fee'] = round($row['logistics_extra_fee'], 2);
            $row['shipment_driver_fee'] = round($row['shipment_driver_fee'], 2);
            $row['shipment_driver_other_fee'] = round($row['shipment_driver_other_fee'], 2);
            $row['pickup_yunmanman'] = round($row['pickup_yunmanman'], 2);
            $row['pickup_huolala'] = round($row['pickup_huolala'], 2);
            $row['shipment_yunmanman'] = round($row['shipment_yunmanman'], 2);
            $row['shipment_huolala'] = round($row['shipment_huolala'], 2);
            $row['receipt_type_price'] = round($row['receipt_type_price'], 2);
            $row['unpack_price'] = round($row['unpack_price'], 2);
            $row['information'] = round($row['information'], 2);
            $row['deposit'] = round($row['deposit'], 2);
            $row['order_extra_price'] = round($row['order_extra_price'], 2);
            $row['cost_extra_price'] = round($row['cost_extra_price'], 2);
            $row['pay_price'] = round($row['pay_price'], 2);
            $row['cost_cont'] = round($row['cost_cont'], 2);
            $row['profit'] = round($row['profit'], 2);
            $row['pickup_total'] = round($row['pickup_driver_fee'] + $row['pickup_driver_other_fee'], 2);
            $row['logistics_total'] = round($row['logistics_driver_cost'] + $row['logistics_extra_fee'], 2);
            $row['shipment_total'] = round($row['shipment_driver_fee'] + $row['shipment_driver_other_fee'], 2);
            $row['other_fee_total'] = round($row['receipt_type_price'] + $row['unpack_price'] + $row['information'] + $row['deposit'] + $row['order_extra_price'] + $row['cost_extra_price'] + (float)($row['monthly_reserve_profit'] ?? 0), 2);
            if ((float)($row['monthly_reserve_profit'] ?? 0) > 0) {
                $row['other_amount'] += (float)$row['monthly_reserve_profit'];
                $row['other_paid_amount'] += (float)$row['monthly_reserve_profit'];
                $row['other_paid'] = 1;
            }
            $state = $this->resolveLedgerPaymentState($row['paid_total'], $row['cost_total']);
            $row['payment_state'] = $state['state'];
            $row['payment_state_text'] = $state['text'];
            $row['row_class'] = $state['row_class'];
            $row['pickup_cell_state'] = $this->resolveLedgerCellState($row['pickup_amount'], $row['pickup_paid_amount'], !empty($row['pickup_in_range']));
            $row['logistics_cell_state'] = $this->resolveLedgerCellState($row['logistics_amount'], $row['logistics_paid_amount'], !empty($row['logistics_in_range']));
            $row['shipment_cell_state'] = $this->resolveLedgerCellState($row['shipment_amount'], $row['shipment_paid_amount'], !empty($row['shipment_in_range']));
            $row['other_cell_state'] = $this->resolveLedgerCellState($row['other_amount'], $row['other_paid_amount'], !empty($row['other_in_range']));
            $nonMonthlyDelivery = (int)($row['is_non_monthly_delivery'] ?? 0) === 1;
            if ($nonMonthlyDelivery) {
                $row['logistics_paid_at_text'] = '-';
                $row['shipment_paid_at_text'] = '-';
                $row['logistics_cell_state'] = 'non_monthly_delivery';
                $row['shipment_cell_state'] = 'non_monthly_delivery';
                $row['is_non_monthly_shipment'] = 1;
            }
        }
        unset($row);

        $adminIds = [];
        foreach ($grouped as $row) {
            $dispatchId = (int)($row['dispatch_id'] ?? 0);
            if ($dispatchId > 0) {
                $adminIds[] = $dispatchId;
            }
        }
        $reserveBalances = $this->loadReserveBalances($adminIds);
        foreach ($grouped as &$row) {
            $dispatchId = (int)($row['dispatch_id'] ?? 0);
            $row['reserve_balance'] = $dispatchId > 0 ? ($reserveBalances[$dispatchId] ?? 0) : 0;
        }
        unset($row);

        usort($grouped, function ($a, $b) {
            return (int)($b['payment_time'] ?? $b['paid_at'] ?? 0) <=> (int)($a['payment_time'] ?? $a['paid_at'] ?? 0);
        });

        return array_values($grouped);
    }

    /**
     * 按付款状态过滤台账汇总行
     */
    private function filterCostLedgerSummaryByPaymentState(array $rows, array $filters): array
    {
        $state = trim((string)($filters['payment_state'] ?? 'all'));
        if ($state === '' || $state === 'all') {
            return $rows;
        }
        return array_values(array_filter($rows, function ($row) use ($state) {
            return (string)($row['payment_state'] ?? '') === $state;
        }));
    }

    private function buildCostLedgerDispatchSummaryRows(array $items): array
    {
        $grouped = [];
        foreach ($items as $item) {
            // 取货/送货额外费用已包含在基础费中，不再重复累计
            if (empty($item['count_in_summary'])) {
                continue;
            }
            $dispatchId = (int)($item['dispatch_id'] ?? 0);
            $dispatchName = trim((string)($item['dispatch_name'] ?? ''));
            $key = $dispatchId > 0 ? $dispatchId : md5($dispatchName ?: 'none');
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'dispatch_id' => $dispatchId,
                    'dispatch_name' => $dispatchName !== '' ? $dispatchName : '未分配',
                    'total_orders' => [],
                    'paid_total' => 0,
                    'unpaid_total' => 0,
                    'cost_total' => 0,
                    'pickup_total' => 0,
                    'pickup_yunmanman' => 0, 
                    'pickup_huolala' => 0,
                    'logistics_total' => 0,
                    'shipment_total' => 0,
                    'shipment_yunmanman' => 0,
                    'shipment_huolala' => 0,
                    'other_fee_total' => 0,
                    'monthly_reserve_profit' => 0,
                    'other_amount' => 0,
                    'other_paid_amount' => 0,
                    'row_class' => 'ledger-row-unpaid',
                ];
            }
            $orderPk = (int)($item['order_pk'] ?? 0);
            if ($orderPk > 0) {
                $grouped[$key]['total_orders'][$orderPk] = true;
            }
            $amount = (float)($item['amount'] ?? 0);
            $groupName = (string)($item['fee_group'] ?? '');
            $paid = !empty($item['paid']);
            $dType = (int)($item['d_type'] ?? 0);
            $feeAmount = $amount;
            $grouped[$key]['cost_total'] += $feeAmount;
            if (!empty($item['monthly_reserve_profit_deducted']) && !empty($item['monthly_reserve_profit'])) {
                $profit = (float)$item['monthly_reserve_profit'];
                $grouped[$key]['monthly_reserve_profit'] += $profit;
                $grouped[$key]['other_fee_total'] += $profit;
                $grouped[$key]['other_amount'] += $profit;
                $grouped[$key]['other_paid_amount'] += $profit;
            }
            if ($paid) {
                $grouped[$key]['paid_total'] += $feeAmount;
            } else {
                $grouped[$key]['unpaid_total'] += $feeAmount;
            }
            if ($groupName === '取货费') {
                $grouped[$key]['pickup_total'] += $feeAmount;
                if ($dType === 1) {
                    $grouped[$key]['pickup_yunmanman'] += $feeAmount;
                } elseif ($dType === 2) {
                    $grouped[$key]['pickup_huolala'] += $amount;
                }
            } elseif ($groupName === '干线费') {
                $grouped[$key]['logistics_total'] += $amount;
            } elseif ($groupName === '送货费') {
                $grouped[$key]['shipment_total'] += $feeAmount;
                if ($dType === 1) {
                    $grouped[$key]['shipment_yunmanman'] += $feeAmount;
                } elseif ($dType === 2) {
                    $grouped[$key]['shipment_huolala'] += $amount;
                }
            } else {
                $grouped[$key]['other_fee_total'] += $amount;
            }
        }

        foreach ($grouped as &$row) {
            $row['total_orders'] = count($row['total_orders']);
            $state = $this->resolveLedgerPaymentState($row['paid_total'], $row['cost_total']);
            $row['payment_state'] = $state['state'];
            $row['payment_state_text'] = $state['text'];
            $row['row_class'] = $state['row_class'];
            $row['paid_total'] = round($row['paid_total'], 2);
            $row['unpaid_total'] = round($row['unpaid_total'], 2);
            $row['cost_total'] = round($row['cost_total'], 2);
            $row['pickup_total'] = round($row['pickup_total'], 2);
            $row['pickup_yunmanman'] = round($row['pickup_yunmanman'], 2);
            $row['pickup_huolala'] = round($row['pickup_huolala'], 2);
            $row['logistics_total'] = round($row['logistics_total'], 2);
            $row['shipment_total'] = round($row['shipment_total'], 2);
            $row['shipment_yunmanman'] = round($row['shipment_yunmanman'], 2);
            $row['shipment_huolala'] = round($row['shipment_huolala'], 2);
            $row['other_fee_total'] = round($row['other_fee_total'], 2);
        }
        unset($row);

        $adminIds = array_map(function ($row) {
            return (int)($row['dispatch_id'] ?? 0);
        }, $grouped);
        $reserveBalances = $this->loadReserveBalances($adminIds);
        foreach ($grouped as &$row) {
            $dispatchId = (int)($row['dispatch_id'] ?? 0);
            $row['reserve_balance'] = $dispatchId > 0 ? ($reserveBalances[$dispatchId] ?? 0) : 0;
        }
        unset($row);

        usort($grouped, function ($a, $b) {
            return (float)($b['cost_total'] ?? 0) <=> (float)($a['cost_total'] ?? 0);
        });

        return array_values($grouped);
    }

    /**
     * 批量获取调度备用金余额
     */
    private function loadReserveBalances(array $adminIds): array
    {
        $adminIds = array_values(array_unique(array_filter(array_map('intval', $adminIds))));
        if (empty($adminIds)) {
            return [];
        }

        $funds = Db::name('dispatch_reserve_fund')
            ->where('admin_id', 'in', $adminIds)
            ->column('balance', 'admin_id');
        $balances = [];
        foreach ($funds as $adminId => $balance) {
            $balances[(int)$adminId] = round((float)$balance, 2);
        }
        return $balances;
    }

    private function buildCostLedgerDetailRows(array $items, array $filters = []): array
    {
        // 基础费已在 buildCostLedgerItemsForOrder 中扣除额外费用，明细直接展示即可
        $startTs = 0;
        $endTs = 0;
        if (!empty($filters['startdate']) && !empty($filters['enddate'])) {
            $startTs = strtotime((string)$filters['startdate'] . ' 00:00:00') ?: 0;
            $endTs = strtotime((string)$filters['enddate'] . ' 23:59:59') ?: 0;
        }
        foreach ($items as &$item) {
            $itemPaidAt = (int)($item['paid_at'] ?? 0);
            $typeKey = (string)($item['type_key'] ?? '');
            $dType = (int)($item['d_type'] ?? 0);
            if (empty($item['paid'])) {
                $item['cell_state'] = 'unpaid';
            } elseif ($startTs > 0 && $endTs > 0 && ($itemPaidAt < $startTs || $itemPaidAt > $endTs)) {
                $item['cell_state'] = 'outrange';
            } else {
                $item['cell_state'] = 'paid';
            }
        }
        unset($item);

        usort($items, function ($a, $b) {
            return (int)($b['payment_time'] ?? $b['paid_at'] ?? 0) <=> (int)($a['payment_time'] ?? $a['paid_at'] ?? 0);
        });
        return array_values($items);
    }

    private function buildCostLedgerStatistics(array $items): array
    {
        $stats = [
            'order_count' => 0,
            'item_count' => count($items),
            'pickup_total' => 0,
            'pickup_paid_total' => 0,
            'pickup_yunmanman' => 0,
            'pickup_huolala' => 0,
            'logistics_total' => 0,
            'logistics_paid_total' => 0,
            'shipment_total' => 0,
            'shipment_paid_total' => 0,
            'shipment_yunmanman' => 0,
            'shipment_huolala' => 0,
            'other_total' => 0,
            'other_paid_total' => 0,
            'cost_total' => 0,
            'paid_total' => 0,
            'unpaid_total' => 0,
            'profit_total' => 0,
            'monthly_reserve_profit' => 0,
            'reserve_total' => 0,
        ];
        $orderIds = [];
        foreach ($items as $item) {
            $orderIds[(int)($item['order_pk'] ?? 0)] = true;
            $amount = (float)($item['amount'] ?? 0);
            $paid = !empty($item['paid']);
            $groupName = (string)($item['fee_group'] ?? '');
            $dType = (int)($item['d_type'] ?? 0);
            $feeAmount = $amount;
            // 非月结送货的干线费/送货费不参与卡片统计
            if (!empty($item['is_non_monthly_delivery']) && in_array($groupName, ['干线费', '送货费'], true)) {
                continue;
            }
            // 月结订单扣除的备用金利润，算到“其他费用”里
            if (!empty($item['monthly_reserve_profit_deducted']) && !empty($item['monthly_reserve_profit']) && $groupName === '其他费用') {
                $stats['other_total'] += (float)$item['monthly_reserve_profit'];
                $stats['other_paid_total'] += (float)$item['monthly_reserve_profit'];
                $stats['monthly_reserve_profit'] += (float)$item['monthly_reserve_profit'];
                continue;
            }
            // 统一口径：卡片与明细/汇总/图表一致，按费用类别分组统计
            if ($groupName === '取货费') {
                $stats['pickup_total'] += $feeAmount;
                if ($paid) {
                    $stats['pickup_paid_total'] += $feeAmount;
                }
                if ($dType === 1) {
                    $stats['pickup_yunmanman'] += $feeAmount;
                } elseif ($dType === 2) {
                    $stats['pickup_huolala'] += $amount;
                }
            } elseif ($groupName === '干线费') {
                $stats['logistics_total'] += $amount;
                if ($paid) {
                    $stats['logistics_paid_total'] += $amount;
                }
            } elseif ($groupName === '送货费') {
                $stats['shipment_total'] += $feeAmount;
                if ($paid) {
                    $stats['shipment_paid_total'] += $feeAmount;
                }
                if ($dType === 1) {
                    $stats['shipment_yunmanman'] += $feeAmount;
                } elseif ($dType === 2) {
                    $stats['shipment_huolala'] += $amount;
                }
            } else {
                $stats['other_total'] += $amount;
                if ($paid) {
                    $stats['other_paid_total'] += $amount;
                }
            }
            // 费用总额、已付金额和未付金额均按参与汇总的明细金额统计
            if (!empty($item['count_in_summary'])) {
                $stats['cost_total'] += $feeAmount;
                if ($paid) {
                    $stats['paid_total'] += $feeAmount;
                } else {
                    $stats['unpaid_total'] += $feeAmount;
                }
            }
            $stats['profit_total'] += (float)($item['profit'] ?? 0);
            if (!empty($item['monthly_reserve_profit_deducted'])) {
                $stats['monthly_reserve_profit'] += (float)($item['monthly_reserve_profit'] ?? 0);
            }
        }
        $stats['order_count'] = count($orderIds);
        $stats['pickup_total'] = round($stats['pickup_total'], 2);
        $stats['pickup_paid_total'] = round($stats['pickup_paid_total'], 2);
        $stats['pickup_yunmanman'] = round($stats['pickup_yunmanman'], 2);
        $stats['pickup_huolala'] = round($stats['pickup_huolala'], 2);
        $stats['logistics_total'] = round($stats['logistics_total'], 2);
        $stats['logistics_paid_total'] = round($stats['logistics_paid_total'], 2);
        $stats['shipment_total'] = round($stats['shipment_total'], 2);
        $stats['shipment_paid_total'] = round($stats['shipment_paid_total'], 2);
        $stats['shipment_yunmanman'] = round($stats['shipment_yunmanman'], 2);
        $stats['shipment_huolala'] = round($stats['shipment_huolala'], 2);
        $stats['other_total'] = round($stats['other_total'], 2);
        $stats['other_paid_total'] = round($stats['other_paid_total'], 2);
        $stats['cost_total'] = round($stats['cost_total'], 2);
        $stats['paid_total'] = round($stats['paid_total'], 2);
        $stats['unpaid_total'] = round($stats['unpaid_total'], 2);
        $stats['profit_total'] = round($stats['profit_total'], 2);
        $stats['monthly_reserve_profit'] = round($stats['monthly_reserve_profit'], 2);
        $adminIds = [];
        foreach ($items as $item) {
            $dispatchId = (int)($item['dispatch_id'] ?? 0);
            if ($dispatchId > 0) {
                $adminIds[$dispatchId] = true;
            }
        }
        $reserveBalances = $this->loadReserveBalances(array_keys($adminIds));
        $stats['reserve_total'] = round(array_sum($reserveBalances), 2);
        return $stats;
    }

    private function buildCostLedgerChart(array $items): array
    {
        $map = [];
        foreach ($items as $item) {
            // 取货/送货额外费用已包含在基础费中，图表不再重复累计
            if (empty($item['count_in_summary'])) {
                continue;
            }
            $date = (string)($item['payment_date'] ?? $item['paid_date'] ?? $item['biz_date'] ?? '');
            if ($date === '') {
                continue;
            }
            if (!isset($map[$date])) {
                $map[$date] = [
                    'pickup' => 0,
                    'pickup_yunmanman' => 0,
                    'pickup_huolala' => 0,
                    'logistics' => 0,
                    'shipment' => 0,
                    'shipment_yunmanman' => 0,
                    'shipment_huolala' => 0,
                    'other' => 0,
                    'paid' => 0,
                    'unpaid' => 0,
                    'total' => 0,
                ];
            }
            $amount = (float)($item['amount'] ?? 0);
            $groupName = (string)($item['fee_group'] ?? '');
            $dType = (int)($item['d_type'] ?? 0);
            $feeAmount = $amount;
            if ($groupName === '取货费') {
                $map[$date]['pickup'] += $feeAmount;
                if ($dType === 1) {
                    $map[$date]['pickup_yunmanman'] += $feeAmount;
                } elseif ($dType === 2) {
                    $map[$date]['pickup_huolala'] += $amount;
                }
            } elseif ($groupName === '干线费') {
                $map[$date]['logistics'] += $amount;
            } elseif ($groupName === '送货费') {
                $map[$date]['shipment'] += $feeAmount;
                if ($dType === 1) {
                    $map[$date]['shipment_yunmanman'] += $feeAmount;
                } elseif ($dType === 2) {
                    $map[$date]['shipment_huolala'] += $amount;
                }
            } else {
                $map[$date]['other'] += $amount;
            }
            $map[$date]['total'] += $feeAmount;
            if (!empty($item['paid'])) {
                $map[$date]['paid'] += $feeAmount;
            } else {
                $map[$date]['unpaid'] += $feeAmount;
            }
        }
        ksort($map);
        $labels = array_keys($map);
        $pickup = $pickupYunmanman = $pickupHuolala = $logistics = $shipment = $shipmentYunmanman = $shipmentHuolala = $other = $paid = $unpaid = $total = [];
        foreach ($labels as $label) {
            $pickup[] = round($map[$label]['pickup'], 2);
            $pickupYunmanman[] = round($map[$label]['pickup_yunmanman'], 2);
            $pickupHuolala[] = round($map[$label]['pickup_huolala'], 2);
            $logistics[] = round($map[$label]['logistics'], 2);
            $shipment[] = round($map[$label]['shipment'], 2);
            $shipmentYunmanman[] = round($map[$label]['shipment_yunmanman'], 2);
            $shipmentHuolala[] = round($map[$label]['shipment_huolala'], 2);
            $other[] = round($map[$label]['other'], 2);
            $paid[] = round($map[$label]['paid'], 2);
            $unpaid[] = round($map[$label]['unpaid'], 2);
            $total[] = round($map[$label]['total'], 2);
        }
        return [
            'labels' => $labels,
            'pickup' => $pickup,
            'pickup_yunmanman' => $pickupYunmanman,
            'pickup_huolala' => $pickupHuolala,
            'logistics' => $logistics,
            'shipment' => $shipment,
            'shipment_yunmanman' => $shipmentYunmanman,
            'shipment_huolala' => $shipmentHuolala,
            'other' => $other,
            'paid' => $paid,
            'unpaid' => $unpaid,
            'total' => $total,
        ];
    }

    private function resolveLedgerPaymentState($paidTotal, $total): array
    {
        $paidTotal = (float)$paidTotal;
        $total = (float)$total;
        if ($total <= 0) {
            return ['state' => 'empty', 'text' => '无数据', 'row_class' => 'ledger-row-empty'];
        }
        if ($paidTotal + 0.01 < $total) {
            if ($paidTotal <= 0) {
                return ['state' => 'unpaid', 'text' => '未付款', 'row_class' => 'ledger-row-unpaid'];
            }
            return ['state' => 'partial', 'text' => '部分付款', 'row_class' => 'ledger-row-unpaid'];
        }
        return ['state' => 'paid', 'text' => '已付款', 'row_class' => 'ledger-row-paid'];
    }

    /**
     * 费用单元格状态：none=无费用 unpaid=未付 paid=本期已付 outrange=非本期已付
     */
    private function resolveLedgerCellState($totalAmount, $paidAmount, bool $inRange = false): string
    {
        $totalAmount = (float)$totalAmount;
        $paidAmount = (float)$paidAmount;
        if ($totalAmount <= 0) {
            return 'none';
        }
        if ($paidAmount + 0.01 < $totalAmount) {
            return 'unpaid';
        }
        if ($inRange) {
            return 'paid';
        }
        return 'outrange';
    }

    /**
     * 调度备用金管理
     */
    public function reserve_fund()
    {
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            $this->view->assign('pageTitle', '线路/调度备用金');
            $this->view->assign('canRecharge', $this->auth->check('finance/reserve_fund') ? 1 : 0);
            $this->view->assign('reserveRole', trim((string)$this->request->get('role', 'all')));
            return $this->view->fetch('reserve_fund');
        }
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }

        $role = trim((string)$this->request->get('role', 'all'));
        $reserveAdmins = $this->getReserveAdminList();
        if ($role === 'line') {
            $reserveAdmins = array_values(array_filter($reserveAdmins, static function ($row) {
                return (int)($row['identity'] ?? 0) === 2;
            }));
        } elseif ($role === 'dispatch') {
            $reserveAdmins = array_values(array_filter($reserveAdmins, static function ($row) {
                return (int)($row['identity'] ?? 0) === 3;
            }));
        }
        $total = count($reserveAdmins);
        $page = max(1, (int)$this->request->get('page', 1));
        $limit = max(1, min((int)$this->request->get('limit', 20), 100));
        $offset = ($page - 1) * $limit;
        $pageRows = array_slice($reserveAdmins, $offset, $limit);

        return json([
            'total' => $total,
            'rows' => array_values($pageRows),
        ]);
    }

    /**
     * 调度备用金充值（仅填写金额，立即入账，无支付）
     */
    public function reserve_fund_recharge()
    {
        if (!$this->auth->check('finance/reserve_fund')) {
            $this->error('您没有充值备用金的权限');
        }
        if (!$this->request->isPost()) {
            $this->error('请使用POST请求');
        }
        $adminId = (int)$this->request->post('admin_id', 0);
        $amount = round((float)$this->request->post('amount', 0), 2);
        $remark = trim((string)$this->request->post('remark', ''));

        if ($adminId <= 0) {
            $this->error('请选择管理员');
        }
        $scopeAdminIds = $this->franchiseScopeAdminIds();
        if ($scopeAdminIds !== null && !in_array($adminId, $scopeAdminIds, true)) {
            $this->error('只能给本加盟商名下的调度/线路账号充值');
        }
        if ($amount <= 0) {
            $this->error('充值金额必须大于0');
        }

        Db::startTrans();
        try {
            $fund = Db::name('dispatch_reserve_fund')->lock(true)->where('admin_id', $adminId)->find();
            $currentBalance = $fund ? round((float)$fund['balance'], 2) : 0;
            $afterBalance = round($currentBalance + $amount, 2);

            if ($fund) {
                Db::name('dispatch_reserve_fund')->where('admin_id', $adminId)->update([
                    'balance' => $afterBalance,
                    'total_recharge' => Db::raw('total_recharge+' . $amount),
                    'updatetime' => time(),
                ]);
            } else {
                Db::name('dispatch_reserve_fund')->insert([
                    'admin_id' => $adminId,
                    'balance' => $afterBalance,
                    'total_recharge' => $amount,
                    'total_deduct' => 0,
                    'createtime' => time(),
                    'updatetime' => time(),
                ]);
            }

            Db::name('dispatch_reserve_fund_log')->insert([
                'admin_id' => $adminId,
                'order_id' => 0,
                'order_number' => '',
                'driver_order_id' => 0,
                'type' => 0,
                'amount' => $amount,
                'direction' => 'recharge',
                'balance_before' => $currentBalance,
                'balance_after' => $afterBalance,
                'remark' => $remark !== '' ? $remark : '备用金充值',
                'admin_name' => $this->auth->nickname ?: $this->auth->username,
                'createtime' => time(),
            ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('充值成功');
    }

    /**
     * 调度备用金扣除（仅填写金额，立即从余额扣减）
     */
    public function reserve_fund_deduct()
    {
        if (!$this->auth->check('finance/reserve_fund')) {
            $this->error('您没有扣除备用金的权限');
        }
        if (!$this->request->isPost()) {
            $this->error('请使用POST请求');
        }
        $adminId = (int)$this->request->post('admin_id', 0);
        $amount = round((float)$this->request->post('amount', 0), 2);
        $remark = trim((string)$this->request->post('remark', ''));

        if ($adminId <= 0) {
            $this->error('请选择管理员');
        }
        $scopeAdminIds = $this->franchiseScopeAdminIds();
        if ($scopeAdminIds !== null && !in_array($adminId, $scopeAdminIds, true)) {
            $this->error('只能扣除本加盟商名下的调度/线路账号备用金');
        }
        if ($amount <= 0) {
            $this->error('扣除金额必须大于0');
        }

        Db::startTrans();
        try {
            $fund = Db::name('dispatch_reserve_fund')->lock(true)->where('admin_id', $adminId)->find();
            $currentBalance = $fund ? round((float)$fund['balance'], 2) : 0;
            if (!$fund) {
                throw new \Exception('该账号暂无备用金，无法扣除');
            }
            if ($currentBalance < $amount) {
                throw new \Exception('备用金余额不足，无法扣除（当前余额 ¥' . number_format($currentBalance, 2, '.', '') . '）');
            }
            $afterBalance = round($currentBalance - $amount, 2);

            Db::name('dispatch_reserve_fund')->where('admin_id', $adminId)->update([
                'balance' => $afterBalance,
                'total_deduct' => Db::raw('total_deduct+' . $amount),
                'updatetime' => time(),
            ]);

            Db::name('dispatch_reserve_fund_log')->insert([
                'admin_id' => $adminId,
                'order_id' => 0,
                'order_number' => '',
                'driver_order_id' => 0,
                'type' => 0,
                'amount' => $amount,
                'direction' => 'deduct',
                'balance_before' => $currentBalance,
                'balance_after' => $afterBalance,
                'remark' => $remark !== '' ? $remark : '备用金扣除',
                'admin_name' => $this->auth->nickname ?: $this->auth->username,
                'createtime' => time(),
            ]);

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('扣除成功');
    }

    /**
     * 调度备用金流水
     */
    public function reserve_fund_log()
    {
        $this->request->filter(['strip_tags', 'trim']);
        $currentAdminId = (int)$this->auth->id;
        $onlySelf = $this->isDispatchOrLineAdmin($currentAdminId);
        if (false === $this->request->isAjax()) {
            $this->view->assign('pageTitle', '备用金流水');
            $this->view->assign('detailAdminId', (int)$this->request->get('admin_id', 0));
            $dispatchAdmins = array_values(array_filter($this->getDispatchAdminList(), static function ($row) {
                return (int)($row['identity'] ?? 0) === 3;
            }));
            $this->view->assign('dispatchAdmins', $dispatchAdmins);
            return $this->view->fetch('reserve_fund_log');
        }
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }

        $adminId = (int)$this->request->get('admin_id', 0);
        $selectedDispatchId = (int)$this->request->get('selected_dispatch_id', 0);
        $dispatchId = (int)$this->request->get('dispatch_id', 0);
        $orderNumber = trim((string)$this->request->get('order_number', ''));
        $startDate = trim((string)$this->request->get('startdate', ''));
        $endDate = trim((string)$this->request->get('enddate', ''));
        $filterAdminId = $selectedDispatchId > 0 ? $selectedDispatchId : ($dispatchId > 0 ? $dispatchId : $adminId);
        $scopeAdminIds = $this->franchiseScopeAdminIds();
        $applyFilters = static function ($query) use ($filterAdminId, $orderNumber, $startDate, $endDate, $scopeAdminIds, $onlySelf, $currentAdminId) {
            if ($onlySelf) {
                // 调度/线路账号只能看自己的备用金流水
                $query->where('admin_id', $currentAdminId);
            } else {
                if ($scopeAdminIds !== null) {
                    if ($scopeAdminIds === []) {
                        $query->whereRaw('1 = 0');
                    } else {
                        $query->whereRaw('admin_id IN (' . implode(',', array_map('intval', $scopeAdminIds)) . ')');
                    }
                }
                if ($filterAdminId > 0) {
                    $query->where('admin_id', $filterAdminId);
                }
            }
            if ($orderNumber !== '') {
                $query->where('order_number', 'like', '%' . $orderNumber . '%');
            }
            if ($startDate !== '') {
                $query->where('createtime', '>=', strtotime($startDate . ' 00:00:00'));
            }
            if ($endDate !== '') {
                $query->where('createtime', '<=', strtotime($endDate . ' 23:59:59'));
            }
            return $query;
        };

        // count() 会改变 ThinkPHP 5 查询对象状态，列表查询必须重新创建，避免翻页重复第一页数据。
        $total = $applyFilters(Db::name('dispatch_reserve_fund_log'))->count();
        // 兼容 bootstrap-table 的两种服务端分页参数：pageNumber/pageSize 或 offset/limit。
        $page = (int)$this->request->param('page', 0);
        $limit = (int)$this->request->param('limit', 0);
        if ($page <= 0) {
            $offsetParam = (int)$this->request->param('offset', 0);
            if ($limit > 0) {
                $page = (int)floor($offsetParam / $limit) + 1;
            } else {
                $page = (int)$this->request->param('pageNumber', 1);
            }
        }
        if ($page < 1) {
            $page = 1;
        }
        if ($limit <= 0) {
            $limit = (int)$this->request->param('pageSize', 20);
        }
        $limit = max(1, min($limit, 100));
        $offset = ($page - 1) * $limit;
        $rows = $applyFilters(Db::name('dispatch_reserve_fund_log'))
            ->order('id', 'desc')
            ->limit($offset . ',' . $limit)
            ->select();

        return json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }  

    /**
     * 判断当前登录账号是否为财务或总后台
     */ 
    private function isFinanceAdmin(): bool
    {
        if ($this->auth->isSuperAdmin()) {
            return true;
        } 
        $groupIds = $this->auth->getGroupIds();
        return in_array(30, $groupIds, true);
    }

    private function getDispatchAdminList(): array
    {
        return $this->getReserveAdminList();
    }

    private function getReserveAdminList(): array
    {
        $admins = Db::name('admin')
            ->field('id,username,nickname,mobile')
            ->order('id', 'asc')
            ->select();

        $list = [];
        foreach ($admins as $admin) {
            $adminId = (int)($admin['id'] ?? 0); 
            if ($adminId <= 0) { 
                continue; 
            }

            $groupIds = Db::name('auth_group_access')->where('uid', $adminId)->column('group_id');
            $groupIds = array_values(array_unique(array_map('intval', $groupIds ?: [])));
            if ($groupIds === []) {
                continue;
            }
    
            $identity = 0;
            foreach ($groupIds as $groupId) {
                $groupIdentity = AdminUserBind::resolveEffectiveOrderRoleIdentity($groupId);
                if ($groupIdentity === 2) {
                    $identity = 2;
                    break; 
                }
                if ($groupIdentity === 3) {
                    $identity = 3;
                } 
            }
            if (!in_array($identity, [2, 3], true)) {
                continue;
            }

            $name = trim((string)($admin['nickname'] ?? ''));
            if ($name === '') {
                $name = trim((string)($admin['username'] ?? ''));
            }
            $fund = Db::name('dispatch_reserve_fund')->where('admin_id', $adminId)->find();
            $list[] = [
                'admin_id' => $adminId,
                'username' => $admin['username'] ?? '',
                'nickname' => $admin['nickname'] ?? '',
                'name' => $name,
                'mobile' => $admin['mobile'] ?? '',
                'identity' => $identity,
                'role_name' => $identity === 2 ? '线路' : '调度',
                'balance' => round((float)($fund['balance'] ?? 0), 2),
                'total_recharge' => round((float)($fund['total_recharge'] ?? 0), 2),
                'total_deduct' => round((float)($fund['total_deduct'] ?? 0), 2),
            ];
        }

        $scopeAdminIds = $this->franchiseScopeAdminIds();
        if ($scopeAdminIds !== null) {
            $list = array_values(array_filter($list, static function ($row) use ($scopeAdminIds) {
                return in_array((int)$row['admin_id'], $scopeAdminIds, true);
            }));
        }

        // 调度/线路账号只能看到自己的备用金
        $currentAdminId = (int)$this->auth->id;
        if ($this->isDispatchOrLineAdmin($currentAdminId)) {
            $list = array_values(array_filter($list, static function ($row) use ($currentAdminId) {
                return (int)($row['admin_id'] ?? 0) === $currentAdminId;
            }));
        }

        return array_values($list);
    }

    /**
     * 当前管理员若属于加盟商体系，返回其可管理的“调度/线路”管理员ID集合；否则返回 null（不限）
     *
     * @return int[]|null
     */
    private function franchiseScopeAdminIds(): ?array
    {
        if ($this->auth->isSuperAdmin()) {
            return null;
        }
        $fr = FranchiseService::resolveFranchiseForAdmin((int)$this->auth->id);
        if (!$fr) {
            return null; // 非加盟商（普通代理/线路/调度等）不限制，维持原逻辑
        }

        return FranchiseService::getFranchiseSubtreeDispatchLineAdminIds((int)$fr['id']);
    }

    /**
     * 当前账号是否为调度(identity=3)或线路(identity=2)子账号
     * 用于限定此类账号只能查看/操作自己的备用金
     */
    private function isDispatchOrLineAdmin(int $adminId): bool
    {
        if ($adminId <= 0) {
            return false;
        }
        $groupIds = Db::name('auth_group_access')->where('uid', $adminId)->column('group_id');
        $groupIds = array_values(array_unique(array_map('intval', $groupIds ?: [])));
        foreach ($groupIds as $groupId) {
            $identity = AdminUserBind::resolveEffectiveOrderRoleIdentity($groupId);
            if ($identity === 2 || $identity === 3) {
                return true;
            }
        }
        return false;
    }

    private function getAdminIdentity(int $adminId): int
    {
        if ($adminId <= 0) {
            return 0;
        }
        return (int)Db::name('auth_group_access')
            ->alias('aga')
            ->join('auth_group ag', 'ag.id = aga.group_id', 'LEFT')
            ->where('aga.uid', $adminId)
            ->order('aga.group_id', 'desc')
            ->value('ag.identity');
    }

    /**
     * 初始化订单费用台账菜单节点
     *
     * 说明：由于菜单数据可能因环境而异，这里提供一个可手动访问的安装接口。
     * 生产环境建议执行下方 SQL。
     */
    public function install_cost_ledger_menu()
    {
        if (!$this->request->isPost()) {
            $this->error('请使用 POST 请求');
        }

        $financePid = Db::name('auth_rule')->where('name', 'finance')->value('id');
        if (!$financePid) {
            Db::name('auth_rule')->insert([
                'pid'        => 0,
                'name'       => 'finance',
                'title'      => '财务管理',
                'icon'       => 'fa fa-money',
                'ismenu'     => 1,
                'status'     => 'normal',
                'weigh'      => 0,
                'createtime' => time(),
                'updatetime' => time(),
            ]);
            $financePid = Db::name('auth_rule')->getLastInsID();
        }

        $nodes = [
            ['finance/cost_ledger', '订单费用台账', 'fa fa-list-alt'],
            ['finance/cost_ledger_dispatch', '调度费用汇总', 'fa fa-sitemap'],
            ['finance/cost_ledger_detail', '订单费用明细', 'fa fa-file-text-o'],
            ['finance/reserve_fund', '线路/调度备用金', 'fa fa-cny'],
            ['finance/reserve_fund_log', '备用金流水', 'fa fa-list'],
        ];

        $added = 0;
        foreach ($nodes as $node) {
            $exists = Db::name('auth_rule')->where('name', $node[0])->find();
            if (!$exists) {
                Db::name('auth_rule')->insert([
                    'pid'        => $financePid,
                    'name'       => $node[0],
                    'title'      => $node[1],
                    'icon'       => $node[2],
                    'ismenu'     => 1,
                    'status'     => 'normal',
                    'weigh'      => 0,
                    'createtime' => time(),
                    'updatetime' => time(),
                ]);
                $added++;
            }
        }

        $this->success('菜单初始化完成，新增 ' . $added . ' 个节点');
    }
}
