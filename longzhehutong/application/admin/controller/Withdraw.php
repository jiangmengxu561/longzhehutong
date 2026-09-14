<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\library\FranchiseService;
use addons\epay\library\Service as EpayService;
use think\Db;
use think\Exception;

/**
 * 提现管理
 *
 * @icon fa fa-circle-o
 */
class Withdraw extends Backend
{
    protected $noNeedRight = ['order_detail', 'order_detail_order'];

    /**
     * Withdraw模型对象
     * @var \app\admin\model\Withdraw
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize( );
        $this->model = new \app\admin\model\Withdraw;
        $this->view->assign("statusList", $this->model->getStatusList());
    }

    /**
     * 查看（加盟商只能看其绑定会员的提现）
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }
        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $query = $this->model->where($where);
        $scope = FranchiseService::getCurrentAdminFranchiseScopeMemberIds((int)$this->auth->id);
        if ($scope !== null) {
            if ($scope === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereRaw('user_id IN (' . implode(',', array_map('intval', $scope)) . ')');
            }
        }
        $list = $query->order($sort, $order)->paginate($limit);

        return json(['total' => $list->total(), 'rows' => $list->items()]);
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
 
    /**
     * 打开关联订单详情，便于财务对账
     *
     * @param string|null $ids 
     */
    public function order_detail($ids = null)
    {
        $ids = $ids ?: $this->request->param('ids');
        if (empty($ids)) {
            $this->error('参数错误');
        }

        if (is_array($ids)) {
            $ids = reset($ids);
        }

        $withdraw = $this->model->get($ids);
        if (!$withdraw) {
            $this->error('提现记录不存在');
        }

        $orderNos = preg_split('/[\s,，;；]+/', (string)($withdraw['orderid'] ?? ''));
        $orderNos = array_values(array_unique(array_filter(array_map('trim', (array)$orderNos))));
        if (empty($orderNos)) {
            $this->error('该提现未关联订单');
        }

        $orders = [];
        foreach ($orderNos as $orderNo) {
            $order = Db::name('order')->where('orderid', $orderNo)->find();
            if ($order) {
                $orders[] = $order;
            }
        }

        if (empty($orders)) {
            $this->error('关联订单不存在');
        }

        $this->view->assign('withdraw', $withdraw);
        $this->view->assign('orders', $orders);
        $this->view->assign('orderCount', count($orders));
        return $this->view->fetch();
    }

    public function order_detail_order($ids = null)
    {
        $ids = $ids ?: $this->request->param('ids');
        if (empty($ids)) {
            $this->error('参数错误');
        }

        if (is_array($ids)) {
            $ids = reset($ids);
        }

        $order = Db::name('order')->where('id', $ids)->find();
        if (!$order) {
            $this->error('订单不存在');
        }

        $this->view->assign('row', $order);
        $this->view->assign('order', $order);
        return $this->view->fetch('withdraw/order_detail_order');
    }

    /**
     * 后台一键打款（仅修改状态和打款时间）
     *
     * @param string|null $ids
     */
    public function pay($ids = null)
    {
        $ids = $ids ?: $this->request->param('ids');
        if (empty($ids)) {
            $this->error('参数错误');
        }

        // 只允许单条操作
        if (is_array($ids)) {
            $ids = reset($ids);
        }

        $row = $this->model->get($ids);
        if (!$row) {
            $this->error('记录不存在');
        }

        // 已打款：兼容 status='successed' 或 status='2'
        if (($row['status'] ?? '') === 'successed' || ($row['status'] ?? '') === '2') {
            $this->error('该提现已打款，无需重复操作');
        }

        // 提现金额：兼容 money 或 price 字段；若有手续费/税费则扣除
        $money      = (float)($row['money'] ?? $row['price'] ?? 0);
        $handingfee = (float)($row['handingfee'] ?? 0);
        $taxes      = (float)($row['taxes'] ?? 0);
        $settled    = max(0, $money - $handingfee - $taxes);

        if ($settled < 0.1) {
            $this->error('最终提现金额至少为 0.1 元');
        }

        // 检查 epay 插件
        $info = get_addon_info('epay');
        if (!$info || !$info['state']) {
            $this->error('请确认微信支付宝整合插件已安装并启用');
        }

        if (!empty($row['transactionid']) || (!empty($row['paymenttime']) && ($row['status'] ?? '') === '2')) {
            $this->error('该提现已存在交易流水号，无法重复打款');
        }

        // 用户ID：兼容 user_id 或 uid（小程序提现写入的为 user_id，表可能为 uid）
        $userId = (int)($row['user_id'] ?? $row['uid'] ?? 0);
        if ($userId <= 0) {
            $this->error('提现记录缺少用户信息');
        }

        // 获取用户微信 openid（从第三方绑定表，小程序绑定的是 wechat+miniapp）
        $openid = Db::name('user')
            ->where('id', $userId)
//            ->where('platform', 'wechat')
            ->value('openid');

        if (!$openid) {
            $this->error('用户未绑定微信，无法打款到微信零钱');
        }
        // 商户订单号：由系统生成，不需从小程序获取。仅支持数字/字母、1-32位、唯一
        $partnerTradeNo = 'TX' . date('YmdHis') . str_pad((string)$row['id'], 6, '0', STR_PAD_LEFT) . mt_rand(100, 999);
//        print_r($partnerTradeNo);die;
        Db::startTrans();
        try {
            // 调用微信企业付款到零钱（小程序提现到微信零钱）
            EpayService::wechatWithdrawToBalance($openid, $settled, $partnerTradeNo, '余额提现到微信零钱');

            // 更新提现记录（fa_withdraw 表为 status=2、paymenttime；若有 orderid/transactionid/transfertime 字段则一并写入）
            $update = ['status' => '2', 'paymenttime' => time()];
            $optional = ['orderid' => $partnerTradeNo, 'transactionid' => $partnerTradeNo, 'transfertime' => time()];
            foreach ($optional as $key => $val) {
                if (array_key_exists($key, $row)) {
                    $update[$key] = $val;
                }
            }
            $this->model->where('id', $row['id'])->update($update);

            Db::commit();
            $this->success('打款成功，已转入用户微信零钱');
        } catch (Exception $e) {
            Db::rollback();
            $this->error('打款失败：' . $e->getMessage());
        }
    }
}
