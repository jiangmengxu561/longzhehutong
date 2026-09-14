<?php

namespace app\admin\model;

use think\Model;

class AdminUserBind extends Model
{
    protected $name = 'admin_user_bind';

    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'createtime';

    protected $updateTime = false;

    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
