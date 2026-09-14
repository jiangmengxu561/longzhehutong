<?php

namespace app\admin\model;

use think\Model;

/**
 * 加盟商钱包流水
 */
class FranchiseWalletLog extends Model
{
    // 表名
    protected $name = 'franchise_wallet_log';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'type_text',
        'createtime_text',
    ];

    public function getTypeList()
    {
        return [
            'income'  => __('Income'),
            'expense' => __('Expense'),
        ];
    }

    public function getTypeTextAttr($value, $data)
    {
        $value = $value ?: ($data['type'] ?? '');
        $list = $this->getTypeList();

        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getCreatetimeTextAttr($value, $data)
    {
        $value = $value ?: ($data['createtime'] ?? '');

        return is_numeric($value) && $value > 0 ? date('Y-m-d H:i:s', (int)$value) : $value;
    }

    public function franchise()
    {
        return $this->belongsTo('Franchise', 'franchise_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
