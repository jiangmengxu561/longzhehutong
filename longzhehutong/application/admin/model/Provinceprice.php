<?php

namespace app\admin\model;

use think\Model;


class Provinceprice extends Model
{

    

    

    // 表名
    protected $name = 'provinceprice';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'car_type_text'
    ];
    

    
    public function getCarTypeList()
    {
        return ['1' => __('Car_type 1'), '2' => __('Car_type 2'), '3' => __('Car_type 3'), '4' => __('Car_type 4')];
    }


    public function getCarTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['car_type'] ?? '');
        $list = $this->getCarTypeList();
        return $list[$value] ?? '';
    }




}
