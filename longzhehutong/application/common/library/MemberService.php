<?php

namespace app\common\library;

/**
 * 会员充值相关公共逻辑
 */
class MemberService
{

    /**
     * 数据表名（不含 fa_ 前缀，可按实际表名修改）
     */
    const PACKAGE_TABLE = 'member';
    const ORDER_TABLE   = 'memberorder';

    /**
     * 会员身份
     */
    const MEMBERTYPE_COMMON = 1; // 普通用户
    const MEMBERTYPE_PARTTIME = 2; // 兼职员工

    /**
     * 时长单位
     */
    const UNIT_WEEK = 'week';
    const UNIT_MONTH = 'month';
    const UNIT_YEAR = 'year';

    /**
     * 是否允许前端充值（仅普通/兼职）
     */
    public static function canRecharge($membertype): bool
    {
        $t = (int)$membertype;
        return $t === self::MEMBERTYPE_COMMON || $t === self::MEMBERTYPE_PARTTIME;
    }

    /**
     * 生成订单号
     */
    public static function makeOrderNo(): string
    {
        return date('YmdHis') . mt_rand(100000, 999999);
    }

    /**
     * 规范化 fa_user.member_time，统一为秒级时间戳
     */
    public static function normalizeMemberTime($memberTime): int
    {
        $ts = (int)$memberTime;
        if ($ts <= 0) {
            return 0;
        }
        if (strlen((string)abs($ts)) === 13) {
            return (int)floor($ts / 1000);
        }
        return $ts;
    }

    /**
     * 计算充值后的到期时间
     * @param int    $baseTs    基准时间(通常取 max(now, 当前到期))
     * @param int    $duration  时长数量
     * @param string $unit      单位 week/month/year
     */
    public static function calcExpiry(int $baseTs, int $duration, string $unit): int
    {
        $duration = max(1, $duration);
        switch ($unit) {
            case self::UNIT_WEEK:
                return $baseTs + $duration * 7 * 86400;
            case self::UNIT_YEAR:
                // 按日历年+(12个月)处理
                return self::addMonths($baseTs, $duration * 12);
            case self::UNIT_MONTH:
            default:
                return self::addMonths($baseTs, $duration);
        }
    }

    /**
     * 指定时间戳基础上加 N 个自然月（跨月日期取当月最后一天，避免滚动溢出）
     */
    protected static function addMonths(int $ts, int $months): int
    {
        $d  = (int)date('j', $ts);
        $n  = (int)date('n', $ts);
        $y  = (int)date('Y', $ts);
        $total = $y * 12 + ($n - 1) + $months;
        $ny = intdiv($total, 12);
        $nm = ($total % 12) + 1;
        $lastDay = (int)date('t', mktime(0, 0, 0, $nm, 1, $ny));
        $nd = min($d, $lastDay);
        return mktime(0, 0, 0, $nm, $nd, $ny);
    }

    /**
     * 时长展示文本
     */
    public static function durationText(int $duration, string $unit): string
    {
        switch ($unit) {
            case self::UNIT_WEEK:
                return $duration . '周';
            case self::UNIT_YEAR:
                return $duration . '年';
            case self::UNIT_MONTH:
            default:
                return $duration . '个月';
        }
    }
}
