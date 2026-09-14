<?php

namespace app\admin\model\user;

use think\Model;

/**
 * 用户地址模型
 */
class Address extends Model
{
    // 表名
    protected $name = 'user_address';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'name'
    ];

    /**
     * 获取格式化后的地址名称
     */
    public function getNameAttr($value, $data)
    {
        return ($data['user_name'] ?? '') . ' - ' . ($data['mobile'] ?? '') . ' - ' . ($data['address'] ?? '') . ' ' . ($data['detailed_address'] ?? '');
    }
}



