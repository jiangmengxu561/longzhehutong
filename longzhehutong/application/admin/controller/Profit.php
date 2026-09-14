<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\exception\DbException;
use think\response\Json;

/**
 * 利润报表
 *
 * @icon fa fa-bar-chart
 */
class Profit extends Backend
{

    /**
     * 无需鉴权的接口（页面渲染本身需要登录）
     *
     * @var array
     */
    protected $noNeedRight = ['report'];

    /**
     * 利润报表页面
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
     */
    public function index()
    {
        if (false === $this->request->isAjax()) {
            if (!$this->canViewProfit()) {
                $this->error('您没有查看利润报表的权限');
            }
            return $this->view->fetch();
        }
        return $this->report();
    }

    /**
     * 利润报表数据
     *
     * @return Json
     * @throws DbException
     */
    public function report()
    {
        if (!$this->canViewProfit()) {
            return json(['code' => 0, 'msg' => '您没有查看利润报表的权限']);
        }

        $startdate = trim((string)$this->request->get('startdate', ''));
        $enddate = trim((string)$this->request->get('enddate', ''));
        if ($startdate === '' || $enddate === '') {
            [$startdate, $enddate] = $this->getDefaultDateRange();
        }

        $startTs = strtotime($startdate . ' 00:00:00');
        $endTs = strtotime($enddate . ' 23:59:59');

        // 仅统计已完成订单
        $orders = Db::name('order')
            ->where('pay_status', 3)
            ->where('createtime', '>=', $startTs)
            ->where('createtime', '<=', $endTs)
            ->field('id,userid,pay_price,cost_cont,platform_commission,createtime')
            ->select();

        // 批量取下单人身份（兼职=2 / 正式=1）
        $memberTypeMap = [];
        $userIds = array_values(array_unique(array_map('intval', array_column($orders, 'userid'))));
        $userIds = array_values(array_filter($userIds, function ($v) {
            return $v > 0;
        }));
        if ($userIds) {
            $memberTypeMap = Db::name('user')->where('id', 'in', $userIds)->column('membertype', 'id');
        }

        // 按日期分组汇总
        $grouped = [];
        foreach ($orders as $order) {
            $date = date('Y-m-d', (int)$order['createtime']);
            if (!isset($grouped[$date])) {
                $grouped[$date] = ['date' => $date, 'income' => 0, 'expense' => 0, 'profit' => 0, 'orders' => 0];
            }

            $memberType = (int)($memberTypeMap[$order['userid']] ?? 0);
            $payPrice = (float)$order['pay_price'];
            $costCont = (float)$order['cost_cont'];
            $platformCommission = (float)$order['platform_commission'];
            $companyProfit = $this->calcCompanyProfit($memberType, $payPrice, $costCont, $platformCommission);

            $grouped[$date]['income'] += $payPrice;
            $grouped[$date]['expense'] += $costCont;
            $grouped[$date]['profit'] += $companyProfit;
            $grouped[$date]['orders']++;
        }

        ksort($grouped);
        $data = array_values($grouped);

        // 汇总
        $totalIncome = 0;
        $totalExpense = 0;
        $totalProfit = 0;
        $totalOrders = 0;
        foreach ($data as $row) {
            $totalIncome += $row['income'];
            $totalExpense += $row['expense'];
            $totalProfit += $row['profit'];
            $totalOrders += $row['orders'];
        }

        $profitRate = $totalIncome > 0 ? ($totalProfit / $totalIncome * 100) : 0;

        return json([
            'code' => 1,
            'data' => $data,
            'statistics' => [
                'total_income' => round($totalIncome, 2),
                'total_expense' => round($totalExpense, 2),
                'total_profit' => round($totalProfit, 2),
                'total_orders' => $totalOrders,
                'profit_rate' => round($profitRate, 1),
            ],
        ]);
    }

    /**
     * 导出利润报表 CSV
     */
    public function export()
    {
        if (!$this->canViewProfit()) {
            $this->error('您没有查看利润报表的权限');
        }

        $startdate = trim((string)$this->request->get('startdate', ''));
        $enddate = trim((string)$this->request->get('enddate', ''));
        if ($startdate === '' || $enddate === '') {
            [$startdate, $enddate] = $this->getDefaultDateRange();
        }

        $startTs = strtotime($startdate . ' 00:00:00');
        $endTs = strtotime($enddate . ' 23:59:59');

        $orders = Db::name('order')
            ->where('pay_status', 3)
            ->where('createtime', '>=', $startTs)
            ->where('createtime', '<=', $endTs)
            ->field('id,userid,pay_price,cost_cont,platform_commission,createtime')
            ->select();

        $memberTypeMap = [];
        $userIds = array_values(array_unique(array_map('intval', array_column($orders, 'userid'))));
        $userIds = array_values(array_filter($userIds, function ($v) {
            return $v > 0;
        }));
        if ($userIds) {
            $memberTypeMap = Db::name('user')->where('id', 'in', $userIds)->column('membertype', 'id');
        }

        $grouped = [];
        foreach ($orders as $order) {
            $date = date('Y-m-d', (int)$order['createtime']);
            if (!isset($grouped[$date])) {
                $grouped[$date] = ['date' => $date, 'income' => 0, 'expense' => 0, 'profit' => 0, 'orders' => 0];
            }

            $memberType = (int)($memberTypeMap[$order['userid']] ?? 0);
            $payPrice = (float)$order['pay_price'];
            $costCont = (float)$order['cost_cont'];
            $platformCommission = (float)$order['platform_commission'];
            $companyProfit = $this->calcCompanyProfit($memberType, $payPrice, $costCont, $platformCommission);

            $grouped[$date]['income'] += $payPrice;
            $grouped[$date]['expense'] += $costCont;
            $grouped[$date]['profit'] += $companyProfit;
            $grouped[$date]['orders']++;
        }
        ksort($grouped);

        $filename = '公司利润报表_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: application/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['日期', '主营收入', '总成本', '公司利润', '订单量']);

        foreach ($grouped as $row) {
            fputcsv($output, [
                $row['date'],
                number_format((float)$row['income'], 2, '.', ''),
                number_format((float)$row['expense'], 2, '.', ''),
                number_format((float)$row['profit'], 2, '.', ''),
                $row['orders'],
            ]);
        }
        fclose($output);
        exit();
    }

    /**
     * 默认统计最近30天
     */
    private function getDefaultDateRange(): array
    {
        $today = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-30 days'));
        return [$start, $today];
    }

    /**
     * 公司利润计算：
     * 兼职(membertype=2)：公司只赚总运费的5%抽佣(platform_commission)
     * 正式(membertype=1)：公司拿订单毛利(总运费-总成本)的40%
     */
    private function calcCompanyProfit($memberType, $payPrice, $costCont, $platformCommission)
    {
        $memberType = (int)$memberType;
        if ($memberType === 2) {
            return round((float)$platformCommission, 2);
        }
        return round(((float)$payPrice - (float)$costCont) * 0.4, 2);
    }

    /**
     * 是否可查看利润报表：超管 / 总后台(组1) / 财务(组30)
     */
    private function canViewProfit(): bool
    {
        if ($this->auth->isSuperAdmin()) {
            return true;
        }
        $groupIds = $this->auth->getGroupIds();
        return in_array(1, $groupIds, true) || in_array(30, $groupIds, true);
    }
}
