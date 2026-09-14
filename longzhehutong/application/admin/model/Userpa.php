<?php

namespace app\admin\model;

use think\Model;


class Userpa extends Model
{

    

    

    // 表名
    protected $name = 'userpa';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'is_kaiqi_text'
    ];
    

    
    public function getIsKaiqiList()
    {
        return ['1' => __('Is_kaiqi 1'), '2' => __('Is_kaiqi 2')];
    }


    public function getIsKaiqiTextAttr($value, $data)
    {
        $value = $value ?: ($data['is_kaiqi'] ?? '');
        $list = $this->getIsKaiqiList();
        return $list[$value] ?? '';
    }




}
