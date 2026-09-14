<?php

namespace app\common\library;

use think\Db;

/**
 * 财务记账修改/删除审核记录（子后台操作统一写入，总后台可查看与审核）
 */
class BookkeepingAuditLog
{
    /** 审核状态：待审核 */
    const AUDIT_PENDING = 0;
    /** 审核状态：已通过 */
    const AUDIT_PASSED = 1;
    /** 审核状态：已拒绝 */
    const AUDIT_REJECTED = 2;

    /** 操作类型：修改 */
    const TYPE_EDIT = 'edit';
    /** 操作类型：删除 */
    const TYPE_DEL = 'del';

    /**
     * 判断指定记账记录是否存在待审核的同类操作
     * @param int $bookkeepingId
     * @param string $type edit|del
     * @return bool
     */
    public static function hasPending($bookkeepingId, $type)
    {
        $count = Db::name('bookkeeping_audit')
            ->where('bookkeeping_id', (int)$bookkeepingId)
            ->where('type', $type)
            ->where('audit_status', self::AUDIT_PENDING)
            ->count();
        return $count > 0;
    }

    /**
     * 写入一条修改/删除审核记录
     * @param int $bookkeepingId 记账记录ID
     * @param string $type edit|del
     * @param array $oldData 修改前数据
     * @param array $newData 修改后数据（删除时可为空数组）
     * @param int $adminId 提交人ID
     * @param string $adminName 提交人名称
     * @return int|false 插入的 id 或 false
     */
    public static function add($bookkeepingId, $type, array $oldData, array $newData, $adminId, $adminName)
    {
        $row = [
            'bookkeeping_id' => (int)$bookkeepingId,
            'type'           => $type === self::TYPE_DEL ? self::TYPE_DEL : self::TYPE_EDIT,
            'old_data'       => json_encode($oldData, JSON_UNESCAPED_UNICODE),
            'new_data'       => json_encode($newData, JSON_UNESCAPED_UNICODE),
            'admin_id'       => (int)$adminId,
            'admin_name'     => mb_substr((string)$adminName, 0, 64),
            'audit_status'   => self::AUDIT_PENDING,
            'audit_remark'   => '',
            'audit_time'     => 0,
            'createtime'     => time(),
        ];
        return Db::name('bookkeeping_audit')->insertGetId($row);
    }

    /**
     * 审核通过后执行实际变更
     * @param array $row bookkeeping_audit 记录
     * @throws \Exception
     */
    public static function apply(array $row)
    {
        $bookkeepingId = (int)($row['bookkeeping_id'] ?? 0);
        if ($bookkeepingId <= 0) {
            throw new \Exception('记账记录ID无效');
        }
        $type = $row['type'] ?? self::TYPE_EDIT;
        if ($type === self::TYPE_DEL) {
            Db::name('bookkeeping')->where('id', $bookkeepingId)->delete();
            return;
        }
        // 修改：应用 new_data
        $newData = json_decode($row['new_data'] ?? '', true);
        if (!is_array($newData) || empty($newData)) {
            throw new \Exception('修改数据为空，无法应用');
        }
        $exist = Db::name('bookkeeping')->where('id', $bookkeepingId)->find();
        if (!$exist) {
            throw new \Exception('记账记录不存在或已被删除');
        }
        Db::name('bookkeeping')->where('id', $bookkeepingId)->update($newData);
    }
}
