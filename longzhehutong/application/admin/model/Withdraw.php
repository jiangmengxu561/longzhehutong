<?php

namespace app\admin\model;

use think\Model;


class Withdraw extends Model
{

    

    

    // 表名
    protected $name = 'withdraw';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'transfertime_text'
    ];
    

    
    public function getStatusList()
    {
        return ['created' => __('Status created'), 'successed' => __('Status successed'), 'rejected' => __('Status rejected')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }


    public function getTransfertimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['transfertime'] ?? '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setTransfertimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
