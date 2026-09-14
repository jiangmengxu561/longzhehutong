<?php

namespace app\admin\model\order\modify;

use think\Model;


class Log extends Model
{

    

    

    // 表名
    protected $name = 'order_modify_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'audit_time_text'
    ];
    

    



    public function getAuditTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['audit_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setAuditTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
