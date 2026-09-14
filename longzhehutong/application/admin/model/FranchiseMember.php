<?php

namespace app\admin\model;

use think\Model;

/**
 * 加盟商与会员绑定
 */
class FranchiseMember extends Model
{
    // 表名
    protected $name = 'franchise_member';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    /**
     * 关联加盟商
     */
    public function franchise()
    {
        return $this->belongsTo('Franchise', 'franchise_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * 关联会员
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
