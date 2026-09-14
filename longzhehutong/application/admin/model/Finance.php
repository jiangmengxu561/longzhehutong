<?php

namespace app\admin\model;

use think\Model;

class Finance extends Model
{

    // 表名
    protected $name = 'finance';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'status_text'
    ];
    
    // 定义财务类型
    public function getTypeList()
    {
        return ['income' => __('Income'), 'expense' => __('Expense')];
    }
    
    // 定义财务状态
    public function getStatusList()
    {
        return ['pending' => __('Pending'), 'confirmed' => __('Confirmed'), 'cancelled' => __('Cancelled')];
    }

    public function getTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['type']) ? $data['type'] : '');
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function order()
    {
        return $this->belongsTo('Order', 'order_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}