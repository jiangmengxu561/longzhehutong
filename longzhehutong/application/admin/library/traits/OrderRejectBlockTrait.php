<?php

namespace app\admin\library\traits;

use think\Db;

/**
 * 订单 pay_status=8（驳回）时禁止子后台继续写入业务
 */
trait OrderRejectBlockTrait
{
    /**
     * @param int $adminOrderId admin_order 表主键
     */
    protected function errorIfOrderRejectedByAdminOrderId($adminOrderId)
    {
        $adminOrderId = (int) $adminOrderId;
        if ($adminOrderId <= 0) {
            return;
        }
        $orderId = (int) Db::name('admin_order')->where('id', $adminOrderId)->value('order_id');
        if ($orderId <= 0) {
            return;
        }
        $payStatus = (int) Db::name('order')->where('id', $orderId)->value('pay_status');
        if ($payStatus === 8) {
            $this->error('订单已驳回，请待用户在前端修改订单后再操作');
        }
    }

    /**
     * @param string $orderNumber 业务订单号 order.orderid
     */
    protected function errorIfOrderRejectedByOrderNumber($orderNumber)
    {
        $orderNumber = trim((string) $orderNumber);
        if ($orderNumber === '') {
            return;
        }
        $payStatus = (int) Db::name('order')->where('orderid', $orderNumber)->value('pay_status');
        if ($payStatus === 8) {
            $this->error('订单已驳回，请待用户在前端修改订单后再操作');
        }
    }
}
