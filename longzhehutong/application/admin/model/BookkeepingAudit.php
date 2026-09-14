<?php

namespace app\admin\model;

use think\Model;


class BookkeepingAudit extends Model
{

    // 表名
    protected $name = 'bookkeeping_audit';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [ 
        'audit_time_text',
        'type_text',
        'audit_status_text',
    ];

    public function getAuditTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['audit_time'] ?? '');
        return is_numeric($value) && $value > 0 ? date("Y-m-d H:i:s", $value) : '';
    }

    protected function setAuditTimeAttr($value)
    {
        return $value === '' || $value === null ? 0 : (!is_numeric($value) ? strtotime($value) : $value);
    }

    public function getTypeTextAttr($value, $data)
    {
        $type = $data['type'] ?? '';
        $map = ['edit' => '修改', 'del' => '删除'];
        return isset($map[$type]) ? $map[$type] : $type;
    }

    public function getAuditStatusTextAttr($value, $data)
    {
        $status = $data['audit_status'] ?? 0;
        $map = [0 => '待审核', 1 => '已通过', 2 => '已拒绝'];
        return isset($map[(int)$status]) ? $map[(int)$status] : '';
    }

}
