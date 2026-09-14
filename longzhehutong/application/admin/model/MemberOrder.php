<?php

namespace app\admin\model;

use app\common\library\MemberService;
use think\Model;

/**
 * 会员充值订单（fa_memberorder）
 *
 * 注意：Linux 服务器上还存在一份仅大小写不同的副本 Memberorder.php，
 * URL /admin/memberorder 解析出的类名是 Memberorder，会命中那份副本；
 * 两份文件内容必须保持一致，否则线上列表会出现字段缺失（手机号/充值前身份/时长/支付状态为空）。
 */
class MemberOrder extends Model
{
    // 表名
    protected $name = MemberService::ORDER_TABLE;

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'pay_status_text',
        'membertype_before_text',
        'membertype_after_text',
        'duration_text',
    ];

    /**
     * 支付状态映射
     */
    public static function payStatusMap(): array
    {
        return [1 => '待支付', 2 => '支付中', 3 => '已支付', 4 => '已取消', 5 => '已退款'];
    }

    /**
     * 会员身份映射（与后台会员身份口径一致）
     */
    public static function memberTypeMap(): array
    {
        return [1 => '普通用户', 2 => '兼职员工', 3 => '正式员工', 4 => '会展员工'];
    }

    public function getPayStatusList()
    {
        return self::payStatusMap();
    }

    public function getMembertypeBeforeList()
    {
        return self::memberTypeMap();
    }

    public function getMembertypeAfterList()
    {
        return self::memberTypeMap();
    }

    public function getMemberUnitList()
    {
        return ['week' => '周', 'month' => '个月', 'year' => '年'];
    }

    public function getPayStatusTextAttr($value, $data)
    {
        $map = self::payStatusMap();

        return $map[(int)($data['pay_status'] ?? 0)] ?? '未知';
    }

    public function getMembertypeBeforeTextAttr($value, $data)
    {
        $map = self::memberTypeMap();

        return $map[(int)($data['membertype_before'] ?? 0)] ?? '';
    }

    public function getMembertypeAfterTextAttr($value, $data)
    {
        $map = self::memberTypeMap();

        return $map[(int)($data['membertype_after'] ?? 0)] ?? '';
    }

    public function getDurationTextAttr($value, $data)
    {
        $num = (int)($data['member_duration'] ?? 0);
        if ($num <= 0) {
            return '';
        }

        return MemberService::durationText($num, (string)($data['member_unit'] ?? ''));
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
