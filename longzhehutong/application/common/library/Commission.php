<?php

namespace app\common\library;

use think\Db;

/**
 * 后台管理员提成记录服务
 */
class Commission
{
    /**
     * 为指定管理员和订单记录提成
     *
     * @param int $adminId
     * @param int $orderId
     * @return void
     */
    public static function record($adminId, $orderId)
    {
        if (!$adminId || !$orderId) {
            return;
        }

        $order = Db::name('order')->where('id', $orderId)->find();
        if (!$order) {
            return;
        }

        $groupId = Db::name('auth_group_access')->where('uid', $adminId)->value('group_id');
        if (!$groupId) {
            return;
        }

        $group = Db::name('auth_group')->where('id', $groupId)->find();
        if (
            !$group ||
            empty($group['commission_type']) ||
            $group['commission_rate'] === null ||
            $group['commission_rate'] === ''
        ) {
            return;
        }

        $commissionType = (int)$group['commission_type']; // 1:净利润, 2:营业额
        $rate = (float)$group['commission_rate'];

        if ($rate <= 0) {
            return;
        }

        $turnover = isset($order['pay_price']) ? (float)$order['pay_price'] : 0;
        $cost = (isset($order['logistics_driver_cost']) ? (float)$order['logistics_driver_cost'] : 0)
            + (isset($order['pickup_driver_fee']) ? (float)$order['pickup_driver_fee'] : 0)
            + (isset($order['shipment_driver_fee']) ? (float)$order['shipment_driver_fee'] : 0);

        $baseAmount = $commissionType === 1 ? $turnover - $cost : $turnover;
        if ($baseAmount <= 0) {
            return;
        }

        $commissionAmount = round($baseAmount * $rate / 100, 2);
        if ($commissionAmount <= 0) {
            return;
        }

        $exists = Db::name('admin_commission_log')
            ->where('order_id', $orderId)
            ->where('admin_id', $adminId)
            ->find();

        if ($exists) {
            return;
        }

        Db::name('admin_commission_log')->insert([
            'admin_id'          => $adminId,
            'order_id'          => $orderId,
            'group_id'          => $groupId,
            'commission_type'   => $commissionType,
            'rate'              => $rate,
            'base_amount'       => round($baseAmount, 2),
            'commission_amount' => $commissionAmount,
            'status'            => '1',
            'settle_time'       => null,
            'createtime'        => time(),
        ]);
    }
}

