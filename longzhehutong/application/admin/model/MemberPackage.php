<?php

namespace app\admin\model;

use app\common\library\MemberService;
use think\Model;

/**
 * 会员充值套餐
 */
class MemberPackage extends Model
{
    // 表名
    protected $name = MemberService::PACKAGE_TABLE;

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'unit_text',
        'duration_text',
    ];

    public function getUnitTextAttr($value, $data)
    {
        $map = ['week' => '周', 'month' => '月', 'year' => '年'];
        return $map[$data['unit'] ?? 'month'] ?? '月';
    }

    public function getDurationTextAttr($value, $data)
    {
        $unit = $data['unit'] ?? 'month';
        $num  = (int)($data['duration'] ?? 0);
        $map = ['week' => '周', 'month' => '个月', 'year' => '年'];
        return $num . ($map[$unit] ?? '个月');
    }
}
