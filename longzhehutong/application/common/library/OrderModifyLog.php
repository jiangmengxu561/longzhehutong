<?php

namespace app\common\library;

use think\Db;

/**
 * 订单修改记录（子后台操作统一写入，总后台可查看与审核）
 */
class OrderModifyLog
{
    /** 审核状态：待审核 */
    const AUDIT_PENDING = 0;
    /** 审核状态：已通过 */
    const AUDIT_PASSED = 1;
    /** 审核状态：已拒绝 */
    const AUDIT_REJECTED = 2;

    /**
     * 写入一条修改记录
     * @param array $data 必须包含: admin_order_id, order_id, orderid, modify_type, title, admin_id, logistics_status, audit_status
     *                   可选: old_data, new_data, admin_name, remark (均为标量或可 JSON 编码的数组)
     * @return int|false 插入的 id 或 false
     */
    public static function add($data)
    {
         $row = [
            'admin_order_id'   => isset($data['admin_order_id']) ? intval($data['admin_order_id']) : 0,
            'order_id'        => isset($data['order_id']) ? intval($data['order_id']) : 0,
            'orderid'         => isset($data['orderid']) ? mb_substr((string)$data['orderid'], 0, 64) : '',
            'modify_type'     => isset($data['modify_type']) ? mb_substr((string)$data['modify_type'], 0, 50) : '',
            'title'           => isset($data['title']) ? mb_substr((string)$data['title'], 0, 100) : '',
            'old_data'        => is_string($data['old_data'] ?? '') ? $data['old_data'] : json_encode($data['old_data'] ?? [], JSON_UNESCAPED_UNICODE),
            'new_data'        => is_string($data['new_data'] ?? '') ? $data['new_data'] : json_encode($data['new_data'] ?? [], JSON_UNESCAPED_UNICODE),
            'admin_id'        => isset($data['admin_id']) ? intval($data['admin_id']) : 0,
            'admin_name'      => isset($data['admin_name']) ? mb_substr((string)$data['admin_name'], 0, 64) : '',
            'logistics_status'=> isset($data['logistics_status']) ? intval($data['logistics_status']) : 0,
            'audit_status'    => isset($data['audit_status']) ? intval($data['audit_status']) : self::AUDIT_PENDING,
            'remark'          => isset($data['remark']) ? mb_substr((string)$data['remark'], 0, 500) : '',
            'createtime'      => time(),
        ];
        // 表名已调整为 ordermodifylog
        $res = Db::name('ordermodifylog')->insertGetId($row);
        return $res ? $res : false;
    }
}
