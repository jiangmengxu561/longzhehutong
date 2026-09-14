<?php

namespace app\admin\validate;

use think\Validate;

class Finance extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'type'        => 'require|in:income,expense',
        'amount'      => 'require|float|>:0',
        'description' => 'require|max:255',
        'status'      => 'require|in:pending,confirmed,cancelled',
        'admin_id'    => 'require|integer',
        'order_id'    => 'integer'
    ];
    
    /**
     * 提示消息
     */
    protected $message = [
        'type.require'        => '财务类型不能为空',
        'type.in'             => '财务类型必须为收入或支出',
        'amount.require'      => '金额不能为空',
        'amount.float'        => '金额必须为数字',
        'amount.>'            => '金额必须大于0',
        'description.require' => '描述不能为空',
        'description.max'     => '描述不能超过255个字符',
        'status.require'      => '状态不能为空',
        'status.in'           => '状态值不正确',
        'admin_id.require'    => '管理员ID不能为空',
        'admin_id.integer'    => '管理员ID必须为整数',
        'order_id.integer'    => '订单ID必须为整数'
    ];
    
    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['type', 'amount', 'description', 'status', 'admin_id'],
        'edit' => ['type', 'amount', 'description', 'status', 'admin_id'],
    ];
}