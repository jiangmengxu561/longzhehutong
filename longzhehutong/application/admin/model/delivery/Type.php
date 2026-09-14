<?php

namespace app\admin\model\delivery;

use think\Model;


class Type extends Model
{

    

    

    // 表名
    protected $name = 'delivery_type';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







}
