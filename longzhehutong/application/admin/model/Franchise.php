<?php

namespace app\admin\model;

use think\Model;

/**
 * 加盟商模型
 */
class Franchise extends Model
{
    // 表名
    protected $name = 'franchise';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'level_text',
    ];

    public function getLevelList()
    {
        return ['1' => '一级加盟商', '2' => '二级加盟商'];
    }

    public function getStatusList()
    {
        return [
            'normal'   => __('Normal'),
            'hidden'   => __('Hidden'),
            'disabled' => __('Disabled'),
        ];
    }

    public function getLevelTextAttr($value, $data)
    {
        $value = $value ?: ($data['level'] ?? '');
        $list = $this->getLevelList();

        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ?: ($data['status'] ?? '');
        $list = $this->getStatusList();

        return isset($list[$value]) ? $list[$value] : '';
    }

    /**
     * 关联管理员
     */
    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 关联上级加盟商
     */
    public function parent()
    {
        return $this->belongsTo('Franchise', 'parent_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
