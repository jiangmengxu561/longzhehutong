<?php

namespace app\admin\model;

use think\Model;


class Opinion extends Model
{

    

    

    // 表名
    protected $name = 'opinion';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'identity_text'
    ];
    

    
    public function getIdentityList()
    {
        return ['1' => __('Identity 1'), '2' => __('Identity 2')];
    }


    public function getIdentityTextAttr($value, $data)
    {
        $value = $value ?: ($data['identity'] ?? '');
        $list = $this->getIdentityList();
        return $list[$value] ?? '';
    }




}
