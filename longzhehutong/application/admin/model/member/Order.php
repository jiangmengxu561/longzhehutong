<?php

namespace app\admin\model\member;

use think\Model;


class Order extends Model
{

    

    

    // 表名
    protected $name = 'member_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'membertype_after_text',
        'member_unit_text',
        'pay_time_text'
    ];
    

    
    public function getMembertypeAfterList()
    {
        return ['2' => __('Membertype_after 2')];
    }

    public function getMemberUnitList()
    {
        return [];
    }


    public function getMembertypeAfterTextAttr($value, $data)
    {
        $value = $value ?: ($data['membertype_after'] ?? '');
        $list = $this->getMembertypeAfterList();
        return $list[$value] ?? '';
    }


    public function getMemberUnitTextAttr($value, $data)
    {
        $value = $value ?: ($data['member_unit'] ?? '');
        $list = $this->getMemberUnitList();
        return $list[$value] ?? '';
    }


    public function getPayTimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['pay_time'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPayTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
