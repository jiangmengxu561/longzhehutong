<?php

namespace app\admin\library;

use think\Db;

/**
 * 订单修改“应用”逻辑（审核通过或总后台直接操作时落库）
 * 供 Ordermodifylog 审核通过 与 总后台免审核直接应用 共用
 */
class OrderModifyApplier
{
    /**
     * 根据修改类型将 new_data 应用到业务表
     *
     * @param string $modifyType edit_order|driver_other_price|songdriver_other_price|order_other_price|cost_extra_price|cost_other_price|logistics_extra_price|upload_images
     * @param int $orderId order 表主键
     * @param int $adminOrderId admin_order 表主键，可为 0
     * @param string $orderNumber 订单号 orderid
     * @param array $newData 要应用的 new_data
     * @throws \Exception
     */
    public static function apply($modifyType, $orderId, $adminOrderId, $orderNumber, array $newData)
    {
        if ($orderId <= 0 && $adminOrderId > 0) {
            $orderId = (int)Db::name('admin_order')->where('id', $adminOrderId)->value('order_id');
        }
        switch ($modifyType) {
            case 'edit_order':
                self::applyEditOrderChange($orderId, $orderNumber, $newData);
                break;
            case 'driver_other_price':
                self::applyDriverOtherPrice($orderId, $orderNumber, $newData);
                break;
            case 'songdriver_other_price':
                self::applySongdriverOtherPrice($orderId, $orderNumber, $newData);
                break;
            case 'order_other_price':
                self::applyOrderOtherPrice($orderId, $orderNumber, $newData);
                break;
            case 'cost_extra_price':
                self::applyCostExtraPrice($orderId, $orderNumber, $newData);
                break;
            case 'cost_other_price':
                self::applyCostOtherPrice($orderId, $orderNumber, $newData);
                break;
            case 'logistics_extra_price':
                self::applyLogisticsExtraPrice($orderId, $orderNumber, $newData);
                break;
            case 'upload_images':
                self::applyUploadImages($orderId, $orderNumber, $newData);
                break;
            default:
                break;
        }
    }

    /**
     * 获取订单对应的备用金管理员ID：专车/小票快运走线路(identity=2)，配车走调度(identity=3)
     */
    private static function getDispatchAdminIdByOrderId($orderId)
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            return 0;
        }
        $order = Db::name('order')->where('id', $orderId)->field('find_car_type')->find();
        if (!$order) {
            return 0;
        }
        $targetIdentity = in_array((string)($order['find_car_type'] ?? ''), ['专车', '小票快运'], true) ? 2 : 3;
        $adminOrders = Db::name('admin_order')->where('order_id', $orderId)->order('id desc')->select();
        if (!$adminOrders) {
            return 0;
        }
        foreach ($adminOrders as $row) {
            $adminId = isset($row['admin_id']) ? (int)$row['admin_id'] : 0;
            if ($adminId <= 0) {
                continue;
            }
            $groupId = Db::name('auth_group_access')->where('uid', $adminId)->value('group_id');
            if (!$groupId) {
                continue;
            }
            $identity = Db::name('auth_group')->where('id', $groupId)->value('identity');
            if ((int)$identity === $targetIdentity) {
                return $adminId;
            }
        }
        return 0;
    }

    /**
     * 获取当前操作人名称（备用金流水“操作人”列）
     */
    private static function getOperatorName()
    {
        $adminName = \think\Session::get('admin.nickname');
        if (!$adminName) {
            $adminName = \think\Session::get('admin.username');
        }
        return $adminName ?: '系统';
    }

    /**
     * 额外费用生效（落库）时同步增减调度备用金并记录流水
     *
     * 正数=扣减，负数=退回。未找到调度时跳过（不阻断额外费用落库）。
     *
     * @param int $orderId order 表主键
     * @param string $orderNumber 订单号
     * @param float $delta 变动金额
     * @param int $type 流水类型（1取货 2干线/物流 3送货 4其他额外费用）
     * @param string $remark 流水备注
     * @throws \Exception 备用金余额不足时抛出
     */
    protected static function applyReserveFundChange($orderId, $orderNumber, $delta, $type, $remark)
    {
        $delta = round((float)$delta, 2);
        if (abs($delta) < 0.005) {
            return;
        }
        $dispatchAdminId = self::getDispatchAdminIdByOrderId((int)$orderId);
        if ($dispatchAdminId <= 0) {
            return;
        }
        $fund = Db::name('dispatch_reserve_fund')->lock(true)->where('admin_id', $dispatchAdminId)->find();
        if (!$fund) {
            $fund = [
                'admin_id'       => $dispatchAdminId,
                'balance'        => 0,
                'total_recharge' => 0,
                'total_deduct'   => 0,
            ];
        }
        $currentBalance = isset($fund['balance']) ? round((float)$fund['balance'], 2) : 0;
        if ($delta > 0 && $currentBalance < $delta) {
            throw new \Exception('调度备用金余额不足，无法支付' . $remark . '（需 ¥' . number_format($delta, 2, '.', '') . '，余额 ¥' . number_format($currentBalance, 2, '.', '') . '）');
        }
        $afterBalance = round($currentBalance - $delta, 2);
        if (isset($fund['id'])) {
            Db::name('dispatch_reserve_fund')->where('id', $fund['id'])->update([
                'balance'      => $afterBalance,
                'total_deduct' => Db::raw('total_deduct+' . $delta),
                'updatetime'   => time(),
            ]);
        } else {
            Db::name('dispatch_reserve_fund')->insert([
                'admin_id'       => $dispatchAdminId,
                'balance'        => $afterBalance,
                'total_recharge' => 0,
                'total_deduct'   => $delta > 0 ? $delta : 0,
                'createtime'     => time(),
                'updatetime'     => time(),
            ]);
        }
        Db::name('dispatch_reserve_fund_log')->insert([
            'admin_id'        => $dispatchAdminId,
            'order_id'        => (int)$orderId,
            'order_number'    => $orderNumber,
            'driver_order_id' => 0,
            'type'            => (int)$type,
            'amount'          => abs($delta),
            'direction'       => $delta > 0 ? 'deduct' : 'recharge',
            'balance_before'  => $currentBalance,
            'balance_after'   => $afterBalance,
            'remark'          => $remark,
            'admin_name'      => self::getOperatorName(),
            'createtime'      => time(),
        ]);
    }

    public static function applyEditOrderChange($orderId, $orderNumber, array $newData)
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            throw new \Exception('订单ID无效');
        }
        $order = Db::name('order')->where('id', $orderId)->find();
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        if (!$orderNumber) {
            $orderNumber = $order['orderid'];
        }
        Db::startTrans();
        try {
            $update = [];
            foreach ($newData as $field => $value) {
                // 税点保存到 dricerorder 表对应司机记录，不写入 order 表
                if (in_array($field, ['charge', 'pickup_tax_point', 'delivery_tax_point'], true)) {
                    continue;
                }
                if (is_string($value) && $value !== '' && preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                    $ts = strtotime($value);
                    $update[$field] = $ts !== false ? $ts : $value;
                } else {
                    $update[$field] = $value;
                }
            } 
            if ($update) {
                Db::name('order')->where('id', $orderId)->update($update);
            }
            if (isset($newData['charge']) && is_array($newData['charge'])) {
                $charge      = $newData['charge'];
                $deliveryNew = isset($update['delivery']) ? (int)$update['delivery'] : (int)$order['delivery'];
                if ($deliveryNew === 1) {
                    $charge['orderid'] = $orderNumber;
                    $existing          = Db::name('charge')->where('orderid', $orderNumber)->find();
                    if ($existing) {
                        Db::name('charge')->where('orderid', $orderNumber)->update($charge);
                    } else {
                        $charge['createtime'] = time();
                        Db::name('charge')->insert($charge);
                    }
                } else {
                    Db::name('charge')->where('orderid', $orderNumber)->delete();
                }
            }
            // 取货/送货司机税点写入 dricerorder 表对应司机的 tax_point 字段
            $needsRecalcCostCont = false;
            if (array_key_exists('pickup_tax_point', $newData) || array_key_exists('delivery_tax_point', $newData)) {
                $needsRecalcCostCont = true;
            }
            // 客户开票税额也会影响 cost_cont（invoiceTaxCost 口径依赖 order.tax_point + pay_price）
            if (array_key_exists('tax_point', $newData) || array_key_exists('isinvoice', $newData) || array_key_exists('pay_price', $newData)) {
                $needsRecalcCostCont = true;
            }
            if (array_key_exists('pickup_tax_point', $newData) || array_key_exists('delivery_tax_point', $newData)) {
                self::applyTaxPointsToDricerorder($orderNumber, $newData);
            }
            if ($needsRecalcCostCont) {
                // 税点变更后必须同步重算 totalCost（写入 order.cost_cont）
                self::recalcCostCont($orderId);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 将取货/送货司机税点写入 dricerorder 表对应司机记录（不写 order 表）
     */
    protected static function applyTaxPointsToDricerorder($orderNumber, array $newData)
    {
        $hasPickup   = array_key_exists('pickup_tax_point', $newData);

        $hasDelivery = array_key_exists('delivery_tax_point', $newData);
        $pickupTaxPoint   = $hasPickup ? trim((string)$newData['pickup_tax_point']) : null;
        $deliveryTaxPoint = $hasDelivery ? trim((string)$newData['delivery_tax_point']) : null;
//        print_r($orderNumber);die;
        if ($hasPickup) {
            $pickupRecord = Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 1)->find();
//            print_r($pickupRecord);die;
            $oldPickupVal = (isset($pickupRecord['tax_point']) && $pickupRecord['tax_point'] !== '' && $pickupRecord['tax_point'] !== null && is_numeric($pickupRecord['tax_point']))
                ? round((float)$pickupRecord['tax_point'], 2) : 0;
            if ((string)$pickupTaxPoint !== '') {
                if (!$pickupRecord) {
                    throw new \Exception('取货司机还没找到，不能填写税点');
                }
                $pickupVal = is_numeric($pickupTaxPoint) ? round((float)$pickupTaxPoint, 2) : null;
                if ($pickupVal !== null && $pickupVal >= 0) {
                    Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 1)->update(['tax_point' => $pickupVal]);
                    $delta = $pickupVal - $oldPickupVal;
//                    if ($delta != 0) {
//                        Db::name('order')->where('orderid', $orderNumber)->setInc('cost_cont', $delta);
//                    }
                }
            } else {
                Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 1)->update(['tax_point' => null]);
//                if ($oldPickupVal > 0) {
//                    Db::name('order')->where('orderid', $orderNumber)->setDec('cost_cont', $oldPickupVal);
//                }
            }
        }
        if ($hasDelivery) {
            $deliveryRecord = Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 3)->find();
            $oldDeliveryVal = (isset($deliveryRecord['tax_point']) && $deliveryRecord['tax_point'] !== '' && $deliveryRecord['tax_point'] !== null && is_numeric($deliveryRecord['tax_point']))
                ? round((float)$deliveryRecord['tax_point'], 2) : 0;
            if ((string)$deliveryTaxPoint !== '') {
//                if (!$deliveryRecord || empty($deliveryRecord['d_id'])) {
//                    throw new \Exception('送货司机还没找到，不能填写税点');
//                }
                $deliveryVal = is_numeric($deliveryTaxPoint) ? round((float)$deliveryTaxPoint, 2) : null;
                if ($deliveryVal !== null && $deliveryVal >= 0) {
                    Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 3)->update(['tax_point' => $deliveryVal]);
                    $delta = $deliveryVal - $oldDeliveryVal;
//                    if ($delta != 0) {
//                        Db::name('order')->where('orderid', $orderNumber)->setInc('cost_cont', $delta);
//                    }
                }
            } else {
                Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 3)->update(['tax_point' => null]);
//                if ($oldDeliveryVal > 0) {
//                    Db::name('order')->where('orderid', $orderNumber)->setDec('cost_cont', $oldDeliveryVal);
//                }
            }
        }
    }

    public static function applyDriverOtherPrice($orderId, $orderNumber, array $newData)
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            throw new \Exception('订单ID无效');
        }
        $price   = isset($newData['price']) ? (float)$newData['price'] : 0;
        $remarks = isset($newData['remarks']) ? trim((string)$newData['remarks']) : '';
        $order   = Db::name('order')->where('id', $orderId)->field('orderid,pickup_driver_fee')->find();
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        $orderNumber = $orderNumber ?: $order['orderid'];
        Db::startTrans();
        try {
            $pickup_driver_fee = $order['pickup_driver_fee'] + $price;
            Db::name('order')->where('id', $orderId)->update(['pickup_driver_fee' => $pickup_driver_fee]);
            Db::name('dirverother')->insert([
                'order_id' => $orderNumber,
                'type'     => 1,
                'price'    => $price,
                'remarks'  => mb_substr($remarks, 0, 255),
            ]);
            self::recalcCostCont($orderId);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function applySongdriverOtherPrice($orderId, $orderNumber, array $newData)
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            throw new \Exception('订单ID无效');
        }
        $price   = isset($newData['price']) ? (float)$newData['price'] : 0;
        $remarks = isset($newData['remarks']) ? trim((string)$newData['remarks']) : '';
        $order   = Db::name('order')->where('id', $orderId)->field('orderid,shipment_driver_fee')->find();
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        $orderNumber = $orderNumber ?: $order['orderid'];
        Db::startTrans();
        try {
            $shipment_driver_fee = $order['shipment_driver_fee'] + $price;
            Db::name('order')->where('id', $orderId)->update(['shipment_driver_fee' => $shipment_driver_fee]);
            Db::name('dirverother')->insert([
                'order_id' => $orderNumber,
                'type'     => 2,
                'price'    => $price,
                'remarks'  => mb_substr($remarks, 0, 255),
            ]);
            self::recalcCostCont($orderId);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function applyOrderOtherPrice($orderId, $orderNumber, array $newData)
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            throw new \Exception('订单ID无效');
        }
        $extra   = isset($newData['pay_price']) ? (float)$newData['pay_price'] : 0;
        $remarks = isset($newData['remarks']) ? trim((string)$newData['remarks']) : '';
        $order   = Db::name('order')->where('id', $orderId)->field('orderid,pay_price')->find();
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        $orderNumber = $orderNumber ?: $order['orderid'];
        Db::startTrans();
        try {
            Db::name('order_extra_price')->insert([
                'order_id'   => $orderNumber,
                'price'      => $extra,
                'remarks'    => mb_substr($remarks, 0, 255),
                'createtime' => time(),
            ]);
            // 额外价格累加在总运费上，不直接替换
            $oldPay = isset($order['pay_price']) ? (float)$order['pay_price'] : 0;
            $newPay = round($oldPay + $extra, 2);
            Db::name('order')->where('id', $orderId)->update(['pay_price' => $newPay]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function applyCostExtraPrice($orderId, $orderNumber, array $newData)
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            throw new \Exception('订单ID无效');
        }
        $price   = isset($newData['price']) ? (float)$newData['price'] : 0;
        $remarks = isset($newData['remarks']) ? trim((string)$newData['remarks']) : '';
        $order   = Db::name('order')->where('id', $orderId)->field('orderid')->find();
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        $orderNumber = $orderNumber ?: $order['orderid'];
        Db::startTrans();
        try {
            Db::name('cost_extra_price')->insert([
                'order_id'   => $orderNumber,
                'price'      => $price,
                'remarks'    => mb_substr($remarks, 0, 255),
                'createtime' => time(),
            ]);
            self::recalcCostCont($orderId);
            self::applyReserveFundChange($orderId, $orderNumber, $price, 4, '订单其他价格（成本补充费）');
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 物流额外成本：写入 logistics_extra_price 表，并更新 order.logistics_driver_cost 与总成本
     */
    public static function applyLogisticsExtraPrice($orderId, $orderNumber, array $newData)
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            throw new \Exception('订单ID无效');
        }
        $order = Db::name('order')->where('id', $orderId)->field('orderid,logistics_driver_cost')->find();
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        $oldCost = isset($order['logistics_driver_cost']) ? (float)$order['logistics_driver_cost'] : 0;
        $price   = isset($newData['price']) && is_numeric($newData['price']) ? (float)$newData['price'] : null;
        // 若 new_data 中 price 缺失或非数字，用目标专线价与当前专线价差值作为本次金额（支持负数=扣减）
        if ($price === null && isset($newData['logistics_driver_cost'])) {
            $targetCost = (float)$newData['logistics_driver_cost'];
            $price = round($targetCost - $oldCost, 2);
        }
        if ($price === null || $price == 0) {
            throw new \Exception('物流额外成本金额无效或缺失');
        }
        $remarks = isset($newData['remarks']) ? trim((string)$newData['remarks']) : '';
        $orderNumber = $orderNumber ?: $order['orderid'];
        Db::startTrans();
        try {
            Db::name('logistics_extra_price')->insert([
                'order_id'   => $orderNumber,
                'price'      => $price,
                'remarks'    => mb_substr($remarks, 0, 255),
                'createtime' => time(),
            ]);
            $newCost = round($oldCost + $price, 2);
            Db::name('order')->where('id', $orderId)->update(['logistics_driver_cost' => $newCost]);
            self::recalcCostCont($orderId);
            // 干线费扣款（填写送货司机时发生）若晚于本次额外成本落库，会把该额外金额一并扣除，因此只有干线费已扣过时才单独扣减
            $lineFeeDeducted = (bool)Db::name('dispatch_reserve_fund_log')
                ->where('order_id', $orderId)
                ->where('type', 2)
                ->where('direction', 'deduct')
                ->find();
            if ($lineFeeDeducted) {
                self::applyReserveFundChange($orderId, $orderNumber, $price, 2, '物流额外成本');
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function applyCostOtherPrice($orderId, $orderNumber, array $newData)
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            throw new \Exception('订单ID无效');
        }
        $hasPickup   = array_key_exists('pickup_tax_point', $newData);
        $hasDelivery = array_key_exists('delivery_tax_point', $newData);
        $pickupTaxPoint   = $hasPickup ? trim((string)$newData['pickup_tax_point']) : null;
        $deliveryTaxPoint = $hasDelivery ? trim((string)$newData['delivery_tax_point']) : null;
        $order = Db::name('order')->where('id', $orderId)->field('orderid')->find();
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        $orderNumber = $orderNumber ?: $order['orderid'];
        // 本次税点变动金额（税点为“已计算好的税额(元)”，正数=扣减，负数=退回）
        $taxDelta = 0;
        Db::startTrans();
        try {
            // 仅当本次提交包含取货税点时才更新取货司机；税点为“已计算好的税额(元)”，总成本由 recalcCostCont 统一累加
            if ($hasPickup) {
                $pickupRecord = Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 1)->find();
                $pickupOld = (isset($pickupRecord['tax_point']) && $pickupRecord['tax_point'] !== '' && $pickupRecord['tax_point'] !== null)
                    ? round((float)str_replace('%', '', trim((string)$pickupRecord['tax_point'])), 2) : 0;
                if ((string)$pickupTaxPoint !== '') {
                    if (!$pickupRecord || empty($pickupRecord['d_id'])) {
                        throw new \Exception('取货司机还没找到，不能填写税点');
                    }
                    $pickupVal = is_numeric($pickupTaxPoint) ? round((float)$pickupTaxPoint, 2) : null;
                    if ($pickupVal !== null && $pickupVal >= 0) {
                        Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 1)->update(['tax_point' => $pickupVal]);
                        $taxDelta += $pickupVal - $pickupOld;
                    }
                } else {
                    Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 1)->update(['tax_point' => null]);
                    $taxDelta += 0 - $pickupOld;
                }
            }
            if ($hasDelivery) {
                $deliveryRecord = Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 3)->find();
                $deliveryOld = (isset($deliveryRecord['tax_point']) && $deliveryRecord['tax_point'] !== '' && $deliveryRecord['tax_point'] !== null)
                    ? round((float)str_replace('%', '', trim((string)$deliveryRecord['tax_point'])), 2) : 0;
                if ((string)$deliveryTaxPoint !== '') {
                    if (!$deliveryRecord || empty($deliveryRecord['d_id'])) {
                        throw new \Exception('送货司机还没找到，不能填写税点');
                    }
                    $deliveryVal = is_numeric($deliveryTaxPoint) ? round((float)$deliveryTaxPoint, 2) : null;
                    if ($deliveryVal !== null && $deliveryVal >= 0) {
                        Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 3)->update(['tax_point' => $deliveryVal]);
                        $taxDelta += $deliveryVal - $deliveryOld;
                    }
                } else {
                    Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 3)->update(['tax_point' => null]);
                    $taxDelta += 0 - $deliveryOld;
                }
            }
            self::recalcCostCont($orderId);
            self::applyReserveFundChange($orderId, $orderNumber, $taxDelta, 4, '司机税点');
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function applyUploadImages($orderId, $orderNumber, array $newData)
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            throw new \Exception('订单ID无效');
        }
        $order = Db::name('order')->where('id', $orderId)->field('orderid')->find();
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        $orderNumber = $orderNumber ?: $order['orderid'];
        $pickupLoad     = isset($newData['pickup_load_image']) ? trim((string)$newData['pickup_load_image']) : '';
        $pickupUnload   = isset($newData['pickup_unload_image']) ? trim((string)$newData['pickup_unload_image']) : '';
        $lineLoad       = isset($newData['line_load_image']) ? trim((string)$newData['line_load_image']) : '';
        $lineUnload     = isset($newData['line_unload_image']) ? trim((string)$newData['line_unload_image']) : '';
        $deliveryLoad   = isset($newData['delivery_load_image']) ? trim((string)$newData['delivery_load_image']) : '';
        $deliveryUnload = isset($newData['delivery_unload_image']) ? trim((string)$newData['delivery_unload_image']) : '';
        $receiptImages  = isset($newData['receipt_images']) ? trim((string)$newData['receipt_images']) : '';
        $monadImages    = isset($newData['monad_images']) ? trim((string)$newData['monad_images']) : '';
        Db::startTrans();
        try {
            if ($pickupLoad !== '' || $pickupUnload !== '') {
                $record  = Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 1)->find();
                $payload = ['order_id' => $orderNumber, 'type' => 1, 'loading_images' => $pickupLoad, 'unloading_images' => $pickupUnload];
                if ($record) {
                    Db::name('dricerorder')->where('id', $record['id'])->update($payload);
                } else {
                    $payload['createtime'] = time();
                    Db::name('dricerorder')->insert($payload);
                }
            }
            if ($lineLoad !== '' || $lineUnload !== '') {
                $record  = Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 2)->find();
                $payload = ['order_id' => $orderNumber, 'type' => 2, 'loading_images' => $lineLoad, 'unloading_images' => $lineUnload];
                if ($record) {
                    Db::name('dricerorder')->where('id', $record['id'])->update($payload);
                } else {
                    $payload['createtime'] = time();
                    Db::name('dricerorder')->insert($payload);
                }
            }
            if ($deliveryLoad !== '' || $deliveryUnload !== '' || $receiptImages !== '') {
                $record  = Db::name('dricerorder')->where('order_id', $orderNumber)->where('type', 3)->find();
                $payload = ['order_id' => $orderNumber, 'type' => 3, 'loading_images' => $deliveryLoad, 'unloading_images' => $deliveryUnload, 'receipt_images' => $receiptImages];
                if ($record) {
                    Db::name('dricerorder')->where('id', $record['id'])->update($payload);
                } else {
                    $payload['createtime'] = time();
                    Db::name('dricerorder')->insert($payload);
                }
            }
            $monadModel  = Db::name('monad');
            $monadRecord = $monadModel->where('order_id', $orderNumber)->find();
            if ($monadImages !== '') {
                if ($monadRecord) {
                    $monadModel->where('order_id', $orderNumber)->update(['image' => $monadImages]);
                } else {
                    $monadModel->insert(['order_id' => $orderNumber, 'image' => $monadImages, 'createtime' => time()]);
                }
            } else {
                if ($monadRecord) {
                    $monadModel->where('order_id', $orderNumber)->delete();
                }
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 按下单/成本口径重算 order.cost_cont：三端司机成本 + 其他成本表 + 回单/拆包 + 信息费 + 定金
     * + 取货/送货司机税点（dricerorder.tax_point 为“已计算好的税额金额”，直接累加进总成本）
     * + 客户开票税额（与 Placeorder 一致：pay_price 为含税总额时，税额 = pay_price×r/(100+r)）
     */
    public static function recalcCostCont($orderId)
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) {
            return;
        }
        // 不显式 field(tax_point)：部分环境 order 表未建 tax_point 列，避免 1054；有该列时 find() 仍会带上
        $order = Db::name('order')->where('id', $orderId)->find();
        if (!$order) {
            return;
        }
        $logisticsCost = isset($order['logistics_driver_cost']) ? (float)$order['logistics_driver_cost'] : 0;
        $pickupCost    = isset($order['pickup_driver_fee']) ? (float)$order['pickup_driver_fee'] : 0;
        $shipmentCost  = isset($order['shipment_driver_fee']) ? (float)$order['shipment_driver_fee'] : 0;
        $extraSum = 0;
        if (!empty($order['orderid'])) {
            $extraSum = (float)Db::name('cost_extra_price')->where('order_id', $order['orderid'])->sum('price');
        }
        $receiptPrice = isset($order['receipt_type_price']) ? (float)$order['receipt_type_price'] : 0;
        $unpackPrice  = isset($order['unpack_price']) ? (float)$order['unpack_price'] : 0;
        $infoFee = (isset($order['information']) && $order['information'] !== '' && $order['information'] !== null) ? (float)$order['information'] : 0;
        $depositFee = (isset($order['deposit']) && $order['deposit'] !== '' && $order['deposit'] !== null) ? (float)$order['deposit'] : 0;
        // 平台抽佣：兼职(membertype=2)按“订单当前应付运费 pay_price × 比例”计算；非兼职保留已存金额（历史订单）
        $platformCommission = 0;
        $commissionUserId = (int)($order['userid'] ?? 0);
        if ($commissionUserId > 0) {
            $memberRow = Db::name('user')->where('id', $commissionUserId)->field('membertype,platform_commission')->find();
            $isPartTime = $memberRow && (int)($memberRow['membertype'] ?? 0) === 2;
            if ($isPartTime) {
                $rate = (float)($memberRow['platform_commission'] ?? 0);
                if ($rate <= 0) {
                    $rate = (float)\think\Config::get('site.platform_commission');
                }
                if ($rate > 0) {
                    $platformCommission = round((float)($order['pay_price'] ?? 0) * ($rate / 100), 2);
                }
            } elseif ((float)($order['platform_commission'] ?? 0) > 0) {
                $platformCommission = (float)$order['platform_commission'];
            }
        }

        $pickupTaxAmt = 0;
        $deliveryTaxAmt = 0;
        if (!empty($order['orderid'])) {
            $pickupTpRaw = Db::name('dricerorder')->where('order_id', $order['orderid'])->where('type', 1)->value('tax_point');
            $delTpRaw = Db::name('dricerorder')->where('order_id', $order['orderid'])->where('type', 3)->value('tax_point');
            $pickupTaxAmt = ($pickupTpRaw !== null && $pickupTpRaw !== '') ? round((float)str_replace('%', '', trim((string)$pickupTpRaw)), 2) : 0;
            $deliveryTaxAmt = ($delTpRaw !== null && $delTpRaw !== '') ? round((float)str_replace('%', '', trim((string)$delTpRaw)), 2) : 0;
        }
        $invoiceTaxCost = 0;
        if (!empty($order['isinvoice']) && (int)$order['isinvoice'] === 1
            && isset($order['tax_point']) && $order['tax_point'] !== '' && $order['tax_point'] !== null) {
            $r = (float)str_replace('%', '', trim((string)$order['tax_point']));
            if ($r > 0) {
                $payPrice = isset($order['pay_price']) ? (float)$order['pay_price'] : 0;
                $invoiceTaxCost = round($payPrice * ($r / 100) / (1 + $r / 100), 2);
            }
        }
        $totalCost = round(
            $logisticsCost + $pickupCost + $shipmentCost + $extraSum
            + $receiptPrice + $unpackPrice + $infoFee + $depositFee
            + $pickupTaxAmt + $deliveryTaxAmt + $invoiceTaxCost
            + $platformCommission,
            2
        );
        Db::name('order')->where('id', $orderId)->update(['cost_cont' => $totalCost, 'platform_commission' => $platformCommission]);
    }
}
