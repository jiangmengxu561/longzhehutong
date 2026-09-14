<?php

namespace app\admin\model\goods;

use think\Model;


class Type extends Model
{

    

    

    // 表名
    protected $name = 'goods_type';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'creatatime_text'
    ];
    

    



    public function getCreatatimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['creatatime'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCreatatimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
