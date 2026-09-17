<?php

namespace app\admin\library;

use think\Db;
use think\Exception;
use think\exception\PDOException;

/**
 * 加盟商业务服务层
 * 覆盖：档案/范围隔离/钱包/绑定/扣费/正式员工名额/区域/兼职抽成/调度备用金充值/新建加盟商
 */
class FranchiseService
{
    public const LEVEL_1 = 1;
    public const LEVEL_2 = 2;

    public const RELATED_ORDER = 'order';
    public const RELATED_MEMBER = 'member';
    public const RELATED_MEMBER_RECHARGE = 'member_recharge';
    public const RELATED_ADD_FRANCHISEE = 'add_franchisee';
    public const RELATED_ADJUST = 'adjust';

    public const ORDER_BASE_FIELD = 'pay_price';
    public const DAYS_PER_MONTH = 30;

    /** 正式员工 membertype 值 */
    public const MEMBERTYPE_FORMAL = '3';
    /** 兼职员工 membertype 值 */
    public const MEMBERTYPE_PARTTIME = '2';

    /**
     * 全局费用（总部统一设置，全加盟商共用）
     */
    public static function getGlobalConfig(): array
    {
        $row = Db::name('franchise_config')->where('id', 1)->find();
        if (!$row) {
            Db::name('franchise_config')->insert([
                'id' => 1, 'member_month_fee' => '150.00', 'add_level2_fee' => '500.00',
                'template_franchise_id' => 0, 'updatetime' => time(),
            ]);
            $row = Db::name('franchise_config')->where('id', 1)->find();
        }

        return [
            'member_month_fee'     => (float)($row['member_month_fee'] ?? 150),
            'add_level2_fee'       => (float)($row['add_level2_fee'] ?? 500),
            'monthly_fee'          => (float)($row['monthly_fee'] ?? 1000),
            'template_franchise_id' => (int)($row['template_franchise_id'] ?? 0),
        ];
    }

    public static function saveGlobalConfig(float $memberFee, float $addLevel2Fee, int $templateFranchiseId = 0, float $monthlyFee = 1000): bool
    {
        $up = [
            'member_month_fee'      => round($memberFee, 2),
            'add_level2_fee'        => round($addLevel2Fee, 2),
            'monthly_fee'           => round($monthlyFee, 2),
            'template_franchise_id' => (int)$templateFranchiseId,
            'updatetime'            => time(),
        ];

        return (bool)Db::name('franchise_config')->where('id', 1)->update($up);
    }

    /**
     * 加盟商月度自动扣费：对状态正常、且“最近扣费月份”早于当前月份的加盟商，从钱包扣取月费
     *
     * @return array{success:bool,msg:string,charged:int[]}
     */
    public static function chargeMonthlyFee(): array
    {
        $cfg = self::getGlobalConfig();
        $monthlyFee = (float)($cfg['monthly_fee'] ?? 1000);
        if ($monthlyFee <= 0) {
            return ['success' => true, 'msg' => '月费金额为0，跳过', 'charged' => []];
        }
        $nowMonth = date('Y-m');
        $todayDay = (int)date('d');
        $lastDayOfMonth = (int)date('t');
        $rows = Db::name('franchise')
            ->where('status', 'normal')
            ->where('last_fee_month', '<>', $nowMonth)
            ->select();
        $charged = [];
        foreach ($rows as $fr) {
            $lastMonth = (string)($fr['last_fee_month'] ?? '');
            if ($lastMonth === $nowMonth) {
                continue;
            }
            if ($lastMonth !== '' && $lastMonth > $nowMonth) {
                continue;
            }
            $createTs = (int)($fr['createtime'] ?? 0);
            // 创建当月不扣，从下个月起按创建日扣
            if ($createTs > 0 && date('Y-m', $createTs) === $nowMonth) {
                continue;
            }
            // 扣费日 = 创建日期的那一天；本月无该日则取本月最后一天
            $chargeDay = $createTs > 0 ? (int)date('d', $createTs) : 1;
            if ($chargeDay < 1) {
                $chargeDay = 1;
            }
            $chargeDay = min($chargeDay, $lastDayOfMonth);
            if ($todayDay < $chargeDay) {
                continue;
            }
            $before = round((float)$fr['wallet_balance'], 2);
            $after = round($before - $monthlyFee, 2);
            Db::startTrans();
            try {
                Db::name('franchise')->where('id', (int)$fr['id'])->lock(true)->update([
                    'wallet_balance' => $after,
                    'last_fee_month' => $nowMonth,
                    'updatetime'     => time(),
                ]);
                Db::name('franchise_wallet_log')->insert([
                    'franchise_id'      => (int)$fr['id'],
                    'type'              => 'expense',
                    'amount'            => $monthlyFee,
                    'balance_before'    => $before,
                    'balance_after'     => $after,
                    'related_type'      => 'monthly_fee',
                    'related_id'        => 0,
                    'remark'            => $nowMonth . ' 月度加盟费自动扣款',
                    'operator_admin_id' => 0,
                    'operator_name'     => '系统',
                    'createtime'        => time(),
                ]);
                Db::commit();
                $charged[] = (int)$fr['id'];
            } catch (\Exception $e) {
                Db::rollback();
            }
        }

        return [
            'success' => true,
            'msg'     => count($charged) > 0 ? (count($charged) . ' 个加盟商已扣月费') : '本月无待扣月费',
            'charged' => $charged,
        ];
    }

    /**
     * 每日节流：一天最多触发一次月度扣费检查（避免每次后台请求都执行）
     */
    public static function ensureMonthlyFeeCharged(): void
    {
        $cacheKey = 'franchise_monthly_fee_' . date('Y-m-d');
        if (\think\Cache::get($cacheKey)) {
            return;
        }
        try {
            self::chargeMonthlyFee();
        } catch (\Exception $e) {
            // 扣费失败不阻断页面
        }
        \think\Cache::set($cacheKey, 1, 86400);
    }

    /**
     * “整月”标准（月对月同日，而不是固定 30 天）：
     *  - 以“起算日”的日为锚点，到期日等于起算日在目标月的同一天（该月没有这一天则取月末）即为整月；
     *    例：1月1日 → 2月1日 = 1 个整月（2月只有 28 天也照样是整月）；1月31日 → 2月28日 = 1 个整月。
     *  - 超过锚点的部分算“不足一月”，按“不足一月按一月”进位：
     *    例：1月1日 → 2月2日 = 1 个月零 1 天，扣 2 个月。
     *  - 到期时间早于或等于今天时无法判断剩余时长（expired = true），调用方应先提示修改到期时间。
     *
     * @return int 扣费月数（最少 1 个月）
     */
    protected static function calcMemberMonths(int $nowTs, int $targetTs): int
    {
        return self::memberMonthsDetail($nowTs, $targetTs)['months'];
    }

    /**
     * 会员到期时间的扣费月数明细（后台提示文案共用同一套算法）
     *
     * @return array{months:int,whole:bool,whole_months:int,extra_days:int,expired:bool}
     */
    public static function memberMonthsDetail(int $nowTs, int $targetTs): array
    {
        $nowDay = strtotime(date('Y-m-d', $nowTs));
        $targetDay = strtotime(date('Y-m-d', $targetTs));
        // 到期时间早于/等于今天：没有可用的剩余时长，需先修改到期时间
        if ($targetDay === false || $nowDay === false || $targetDay <= $nowDay) {
            return ['months' => 1, 'whole' => false, 'whole_months' => 0, 'extra_days' => 0, 'expired' => true];
        }
        // 到期日与起算日相差的“日历月”数
        $monthDiff = ((int)date('Y', $targetDay) - (int)date('Y', $nowDay)) * 12
            + ((int)date('n', $targetDay) - (int)date('n', $nowDay));
        $anchor = self::addMonthsSameDay($nowDay, $monthDiff);
        if ($targetDay === $anchor) {
            // 正好整月
            $months = max(1, $monthDiff);

            return ['months' => $months, 'whole' => true, 'whole_months' => $months, 'extra_days' => 0, 'expired' => false];
        }
        if ($targetDay > $anchor) {
            // 已满 monthDiff 个整月，多出的不足一月再进位
            $wholeMonths = $monthDiff;
            $months = $monthDiff + 1;
        } else {
            // 不满 monthDiff 个整月（如 1个月零1天），按 monthDiff 个月计费
            $wholeMonths = max(0, $monthDiff - 1);
            $months = $monthDiff;
        }
        $extraDays = (int)round(($targetDay - self::addMonthsSameDay($nowDay, $wholeMonths)) / 86400);

        return [
            'months'       => max(1, $months),
            'whole'        => false,
            'whole_months' => $wholeMonths,
            'extra_days'   => max(0, $extraDays),
            'expired'      => false,
        ];
    }

    /**
     * 以“月对月同日”推进月数：该月若无此日则取当月最后一天，返回当日 0 点时间戳
     */
    protected static function addMonthsSameDay(int $baseTs, int $addMonths): int
    {
        $year = (int)date('Y', $baseTs);
        $month = (int)date('n', $baseTs);
        $day = (int)date('j', $baseTs);
        $total = $year * 12 + ($month - 1) + $addMonths;
        $newYear = (int)floor($total / 12);
        $newMonth = $total - $newYear * 12 + 1;
        $maxDay = (int)date('t', mktime(0, 0, 0, $newMonth, 1, $newYear));

        return (int)mktime(0, 0, 0, $newMonth, min($day, $maxDay), $newYear);
    }

    /**
     * 加盟商是否开启“物流专线需审核”
     */
    public static function logisticsAuditEnabled(int $franchiseId): bool
    {
        if ($franchiseId <= 0) {
            return false;
        }

        return (int)Db::name('franchise')->where('id', $franchiseId)->value('logistics_audit') === 1;
    }

    /**
     * 某后台管理员所属加盟商是否开启“物流专线需审核”
     */
    public static function adminLogisticsAuditEnabled(int $adminId): bool
    {
        $fr = self::resolveFranchiseForAdmin((int)$adminId);

        return $fr ? self::logisticsAuditEnabled((int)$fr['id']) : false;
    }

    /**
     * 加盟商角色组默认可见的顶层菜单名（精简清单，避免和总后台完全一致）
     */
    public static function franchiseDefaultMenuNames(): array
    {
        return [
            'dashboard',     // 首页
            'user/user',     // 会员管理
            'order',         // 订单管理
            'admin/order',   // 我的订单（线路/调度）
            'logistics',     // 物流/专线
            'bookkeeping',   // 财务记账
            'caiwu',         // 财务
            'franchise',     // 加盟商管理
        ];
    }

    /**
     * 生成加盟商角色组默认 rules（逗号分隔的 fa_auth_rule.id），含上述顶层菜单及其全部子规则
     */
    public static function getFranchiseDefaultRuleIds(): string
    {
        $rows = Db::name('auth_rule')->field('id,pid,name')->select();
        $nameId = [];
        $children = [];
        foreach ($rows as $r) {
            $nameId[(string)$r['name']] = (int)$r['id'];
            $children[(int)$r['pid']][] = (int)$r['id'];
        }
        $roots = [];
        foreach (self::franchiseDefaultMenuNames() as $name) {
            if (isset($nameId[$name])) {
                $roots[] = $nameId[$name];
            }
        }
        if ($roots === []) {
            return '';
        }
        $seen = [];
        $stack = $roots;
        $rules = [];
        while ($stack !== []) {
            $id = (int)array_pop($stack);
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $rules[] = $id;
            if (!empty($children[$id])) {
                foreach ($children[$id] as $c) {
                    $stack[] = $c;
                }
            }
        }
        $rules = array_values(array_unique($rules));
        sort($rules);

        return implode(',', $rules);
    }

    public static function isFranchiseAdmin($adminId): bool
    {
        return (bool)Db::name('franchise')->where('admin_id', (int)$adminId)->value('id');
    }

    /**
     * 权限模板加盟商：当前已配置好菜单的加盟商（取最小的一个一级加盟商，作为后续加盟商的规则模板）
     */
    public static function getTemplateFranchise(): ?array
    {
        $cfg = self::getGlobalConfig();
        $tplId = (int)($cfg['template_franchise_id'] ?? 0);
        if ($tplId > 0) {
            $row = Db::name('franchise')->where('id', $tplId)->where('level', self::LEVEL_1)->where('status', 'normal')->find();
            if ($row) {
                return $row;
            }
        }
        $row = Db::name('franchise')->where('level', self::LEVEL_1)->where('status', 'normal')->order('id', 'asc')->find();

        return $row ?: null;
    }

    /**
     * 取模板加盟商的 线路/调度/财务 子组ID（role=>group_id）
     *
     * @return array<string,int>
     */
    public static function getTemplateFranchiseChildGroupIds(): array
    {
        $template = self::getTemplateFranchise();
        if (!$template) {
            return [];
        }

        return self::ensureFranchiseChildGroups((int)$template['id']);
    }

    /**
     * 加盟商子组配置：key => [组名后缀, identity]
     *
     * @return array<string,array{0:string,1:string}>
     */
    public static function franchiseChildGroupConfig(): array
    {
        return [
            'lin'  => ['线路', '2'],
            'diao' => ['调度', '3'],
            'cai'  => ['财务', '1'],
        ];
    }

    /**
     * 模板加盟商各子组已配置的 rules（role=>rules），无模板或未配置则空
     *
     * @return array<string,string>
     */
    protected static function templateChildGroupRules(): array
    {
        $template = self::getTemplateFranchise();
        if (!$template) {
            return [];
        }
        $root = (int)$template['group_id'];
        $out = [];
        foreach (self::franchiseChildGroupConfig() as $key => [$roleName]) {
            $gname = $template['name'] . '（' . $roleName . '）';
            $gid = Db::name('auth_group')->where('pid', $root)->where('name', $gname)->value('id');
            if ($gid) {
                $out[$key] = (string)Db::name('auth_group')->where('id', (int)$gid)->value('rules');
            }
        }

        return $out;
    }

    /**
     * 确保加盟商已创建“线路/调度/财务”三个子角色组（挂在其根组下），返回 角色=>组ID
     *
     * @return array<string,int>
     */
    public static function ensureFranchiseChildGroups(int $franchiseId): array
    {
        $fr = Db::name('franchise')->where('id', $franchiseId)->find();
        if (!$fr) {
            return [];
        }
        $rootGroupId = (int)$fr['group_id'];
        if ($rootGroupId <= 0) {
            return [];
        }
        $cfgs = self::franchiseChildGroupConfig();
        $templateChildRules = self::templateChildGroupRules();
        $rules = (string)Db::name('auth_group')->where('id', $rootGroupId)->value('rules');
        $rules = $rules === '' ? '*' : $rules;
        $name = $fr['name'];
        $now = time();
        $out = [];
        foreach ($cfgs as $key => [$roleName, $identity]) {
            $gname = $name . '（' . $roleName . '）';
            $gid = Db::name('auth_group')->where('pid', $rootGroupId)->where('name', $gname)->value('id');
            if (!$gid) {
                $childRule = $rules;
                if (!empty($templateChildRules[$key]) && $templateChildRules[$key] !== '') {
                    $childRule = $templateChildRules[$key];
                }
                $gid = (int)Db::name('auth_group')->insertGetId([
                    'pid'         => $rootGroupId,
                    'name'        => $gname,
                    'identity'    => $identity,
                    'rules'       => $childRule,
                    'createtime'  => $now,
                    'updatetime'  => $now,
                    'status'      => 'normal',
                    'city'        => '',
                    'province'    => '',
                    'district'    => '',
                ]);
            } else {
                $gid = (int)$gid;
            }
            $out[$key] = $gid;
        }

        return $out;
    }

    /**
     * 加盟商添加员工（线路/调度/财务）
     *
     * @param string $role lin=diao=cai
     */
    public static function createFranchiseStaff(int $franchiseId, string $role, string $username, string $password, string $nickname = '', string $mobile = '', $auth = null): array
    {
        $groups = self::ensureFranchiseChildGroups($franchiseId);
        if (!isset($groups[$role])) {
            return ['success' => false, 'msg' => '角色不存在'];
        }
        // 线路/调度必须填写手机号
        if (in_array($role, ['lin', 'diao'], true) && $mobile === '') {
            return ['success' => false, 'msg' => '线路/调度员工必须填写手机号'];
        }
        if (Db::name('admin')->where('username', $username)->value('id')) {
            return ['success' => false, 'msg' => '登录名已存在'];
        }
        $franchise = Db::name('franchise')->where('id', $franchiseId)->find();
        // 线路/调度账号数量上限（财务不限）
        if ($role === 'lin' || $role === 'diao') {
            $quotaField = $role === 'lin' ? 'line_quota' : 'dispatch_quota';
            $quota = (int)($franchise[$quotaField] ?? 0);
            if ($quota > 0) {
                $current = (int)Db::name('auth_group_access')->where('group_id', (int)$groups[$role])->count();
                if ($current >= $quota) {
                    $label = $role === 'lin' ? '线路' : '调度';

                    return ['success' => false, 'msg' => $label . '账号数量已达上限（' . $quota . '），请先停用或删除已有账号'];
                }
            }
        }
        $salt = \fast\Random::alnum();
        $passwordEnc = $auth
            ? $auth->getEncryptPassword($password, $salt)
            : \app\common\library\Auth::instance()->getEncryptPassword($password, $salt);
        Db::startTrans();
        try {
            $adminId = (int)Db::name('admin')->insertGetId([
                'username'      => $username,
                'nickname'      => $nickname !== '' ? $nickname : $username,
                'password'      => $passwordEnc,
                'salt'          => $salt,
                'avatar'        => '/assets/img/avatar.png',
                'email'         => '',
                'mobile'        => $mobile,
                'createtime'    => time(),
                'updatetime'    => time(),
                'status'        => 'normal',
                'loginfailure'  => 0,
                'logintime'     => 0,
                'loginip'       => '',
                'token'         => '',
                'city'          => '',
            ]);
            Db::name('auth_group_access')->insert(['uid' => $adminId, 'group_id' => $groups[$role]]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();

            return ['success' => false, 'msg' => '添加失败：' . $e->getMessage()];
        }

        return ['success' => true, 'msg' => '员工添加成功', 'admin_id' => $adminId];
    }

    /**
     * 设置员工状态（normal/hidden/disabled）
     */
    public static function setFranchiseStaffStatus(int $adminId, string $status): array
    {
        if ($adminId <= 0) {
            return ['success' => false, 'msg' => '参数错误'];
        }
        if (!in_array($status, ['normal', 'hidden', 'disabled'], true)) {
            return ['success' => false, 'msg' => '状态错误'];
        }
        Db::name('admin')->where('id', $adminId)->update([
            'status'     => $status,
            'updatetime' => time(),
        ]);

        return ['success' => true, 'msg' => '操作成功'];
    }

    /**
     * 删除员工（移除管理员与角色组关联）
     */
    public static function deleteFranchiseStaff(int $adminId): array
    {
        if ($adminId <= 0) {
            return ['success' => false, 'msg' => '参数错误'];
        }
        Db::startTrans();
        try {
            Db::name('auth_group_access')->where('uid', $adminId)->delete();
            Db::name('admin')->where('id', $adminId)->delete();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();

            return ['success' => false, 'msg' => '删除失败：' . $e->getMessage()];
        }

        return ['success' => true, 'msg' => '已删除'];
    }

    public static function getFranchiseByAdminId($adminId): ?array
    {
        $row = Db::name('franchise')->where('admin_id', (int)$adminId)->find();

        return $row ?: null;
    }

    public static function getFranchiseByGroupId($groupId): ?array
    {
        $row = Db::name('franchise')->where('group_id', (int)$groupId)->find();

        return $row ?: null;
    }

    /**
     * 解析某后台管理员所属的加盟商：
     *  - 加盟商管理员：按 admin_id 命中；
     *  - 加盟商的调度/线路/财务子账号：沿其主业务角色组向上找“在 fa_franchise.group_id 中”的加盟商组。
     */
    public static function resolveFranchiseForAdmin(int $adminId): ?array
    {
        if (array_key_exists($adminId, self::$franchiseByAdminIdCache)) {
            return self::$franchiseByAdminIdCache[$adminId];
        }

        return self::$franchiseByAdminIdCache[$adminId] = self::doResolveFranchiseForAdmin($adminId);
    }

    /**
     * 请求内缓存：管理员 ID => 所属加盟商（null 表示不属于任何加盟商）
     *
     * @var array<int,array<string,mixed>|null>
     */
    protected static $franchiseByAdminIdCache = [];

    /**
     * 加盟商归属解析逻辑（结果由 resolveFranchiseForAdmin 缓存，同一次请求内只查一次）
     */
    protected static function doResolveFranchiseForAdmin(int $adminId): ?array
    {
        $fr = self::getFranchiseByAdminId($adminId);
        if ($fr) {
            return $fr;
        }
        $gid = (int)\app\admin\library\AdminUserBind::getPrimaryBusinessGroupIdForAdmin($adminId);
        if ($gid <= 0) {
            return null;
        }
        $franchiseByGroupId = self::franchiseByGroupIdMap();
        $groupPid = self::authGroupPidMap();
        $seen = [];
        while ($gid > 0 && !isset($seen[$gid])) {
            $seen[$gid] = true;
            $fr = $franchiseByGroupId[$gid] ?? null;
            if ($fr) {
                return $fr;
            }
            $gid = (int)($groupPid[$gid] ?? 0);
        }

        return null;
    }

    /**
     * 请求内缓存：角色组 ID => 加盟商（沿角色组向上找加盟商时用，避免逐层查库）
     *
     * @var array<int,array<string,mixed>>|null
     */
    protected static $franchiseByGroupIdMapCache = null;

    /**
     * 请求内缓存：角色组 ID => pid
     *
     * @var array<int,int>|null
     */
    protected static $authGroupPidMapCache = null;

    /**
     * @return array<int,array<string,mixed>>
     */
    protected static function franchiseByGroupIdMap(): array
    {
        if (self::$franchiseByGroupIdMapCache === null) {
            $map = [];
            foreach (Db::name('franchise')->where('group_id', '>', 0)->select() as $row) {
                $gid = (int)$row['group_id'];
                if (!isset($map[$gid])) {
                    $map[$gid] = $row;
                }
            }
            self::$franchiseByGroupIdMapCache = $map;
        }

        return self::$franchiseByGroupIdMapCache;
    }

    /**
     * @return array<int,int>
     */
    protected static function authGroupPidMap(): array
    {
        if (self::$authGroupPidMapCache === null) {
            $map = [];
            foreach (Db::name('auth_group')->field('id,pid')->select() as $row) {
                $map[(int)$row['id']] = (int)$row['pid'];
            }
            self::$authGroupPidMapCache = $map;
        }

        return self::$authGroupPidMapCache;
    }

    /**
     * 加盟商子树ID（含自身）
     */
    public static function getSubtreeFranchiseIds(int $franchiseId): array
    {
        if ($franchiseId <= 0) {
            return [];
        }
        $rows = Db::name('franchise')->field('id,parent_id')->select();
        $children = [];
        foreach ($rows as $r) {
            $children[(int)$r['parent_id']][] = (int)$r['id'];
        }
        $ids = [$franchiseId];
        $stack = $children[$franchiseId] ?? [];
        while ($stack !== []) {
            $id = (int)array_pop($stack);
            $ids[] = $id;
            if (!empty($children[$id])) {
                foreach ($children[$id] as $c) {
                    $stack[] = $c;
                }
            }
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public static function getAncestorFranchiseIds(int $franchiseId): array
    {
        $out = [];
        $gid = $franchiseId;
        $seen = [];
        while ($gid > 0 && !isset($seen[$gid])) {
            $seen[$gid] = true;
            $out[] = $gid;
            $gid = (int)Db::name('franchise')->where('id', $gid)->value('parent_id');
        }

        return $out;
    }

    public static function getScopeMemberIds(int $franchiseId): array
    {
        $ids = self::getSubtreeFranchiseIds($franchiseId);
        if ($ids === []) {
            return [];
        }
        $uids = Db::name('franchise_member')->where('franchise_id', 'in', $ids)->column('user_id');

        return array_values(array_unique(array_map('intval', $uids ?: [])));
    }

    /**
     * 某加盟商角色组子树内、身份为“调度/线路(identity 2/3)”的所有管理员ID
     *
     * @return int[]
     */
    public static function getFranchiseSubtreeDispatchLineAdminIds(int $franchiseId): array
    {
        $fr = Db::name('franchise')->where('id', $franchiseId)->find();
        if (!$fr) {
            return [];
        }
        $treeGroupIds = self::getAuthGroupIdsInSubtree((int)$fr['group_id']);
        if ($treeGroupIds === []) {
            return [];
        }
        $groupIds = Db::name('auth_group')
            ->where('id', 'in', $treeGroupIds)
            ->where('identity', 'in', ['2', '3'])
            ->column('id');
        if ($groupIds === []) {
            return [];
        }
        $uids = Db::name('auth_group_access')->where('group_id', 'in', $groupIds)->column('uid');

        return array_values(array_unique(array_map('intval', $uids ?: [])));
    }

    /**
     * 某加盟商角色组子树内**所有**管理员ID（含加盟商管理员、财务、线路、调度等）
     *
     * @return int[]
     */
    public static function getFranchiseSubtreeAdminIds(int $franchiseId): array
    {
        $fr = Db::name('franchise')->where('id', $franchiseId)->find();
        if (!$fr) {
            return [];
        }
        $treeGroupIds = self::getAuthGroupIdsInSubtree((int)$fr['group_id']);
        if ($treeGroupIds === []) {
            return [];
        }
        $uids = Db::name('auth_group_access')->where('group_id', 'in', $treeGroupIds)->column('uid');

        return array_values(array_unique(array_map('intval', $uids ?: [])));
    }

    /**
     * 当前管理员若属于加盟商体系，返回其加盟商子树内的所有管理员ID；否则返回 null（不限）
     *
     * @return int[]|null
     */
    public static function getCurrentAdminFranchiseScopeAdminIds(int $adminId): ?array
    {
        $fr = self::resolveFranchiseForAdmin($adminId);
        if (!$fr) {
            return null;
        }

        return self::getFranchiseSubtreeAdminIds((int)$fr['id']);
    }

    /**
     * 当前管理员若属于加盟商体系，返回其加盟商范围内可见的绑定会员ID；否则返回 null（不限）
     *
     * @return int[]|null
     */
    public static function getCurrentAdminFranchiseScopeMemberIds(int $adminId): ?array
    {
        $fr = self::resolveFranchiseForAdmin($adminId);
        if (!$fr) {
            return null;
        }

        return self::getScopeMemberIds((int)$fr['id']);
    }

    /**
     * 剔除“绑定到总部或其它加盟商后台”的用户；保留“绑定到本加盟商自己后台”的员工。
     * 加盟商免费开通/绑定的内部员工会写入 admin_user_bind(admin_id=该加盟商后台)，
     * 不能因为存在 admin_user_bind 就把他们从可见范围里剔除。
     *
     * @param int[] $memberIds  franchise_member 里的 user_id
     * @param int[] $scopeIds   本加盟商(含子树)的 franchise id
     * @return int[]
     */
    private static function excludeForeignBoundUsers(array $memberIds, array $scopeIds): array
    {
        $memberIds = array_values(array_unique(array_map('intval', $memberIds)));
        if ($memberIds === []) {
            return [];
        }
        // 本加盟商(含子树)自己的后台 admin_id 集合
        $ownAdminIds = [];
        foreach ($scopeIds as $sid) {
            $aid = (int)Db::name('franchise')->where('id', $sid)->value('admin_id');
            if ($aid > 0) {
                $ownAdminIds[$aid] = true;
            }
        }
        if ($ownAdminIds === []) {
            return $memberIds;
        }
        // 批量取这些用户绑定的 admin_id
        $boundAdminMap = [];
        $rows = Db::name('admin_user_bind')->where('user_id', 'in', $memberIds)->field('user_id,admin_id')->select();
        foreach ($rows as $r) {
            $boundAdminMap[(int)$r['user_id']] = (int)$r['admin_id'];
        }
        $keep = [];
        foreach ($memberIds as $uid) {
            if (!isset($boundAdminMap[$uid])) {
                $keep[] = $uid;
                continue;
            }
            if (isset($ownAdminIds[$boundAdminMap[$uid]])) {
                $keep[] = $uid;
            }
        }
        return array_values($keep);
    }

    /**
     * 加盟商（含下级）体系对应的角色组ID集合（fa_auth_group 子树），
     * 用于判断订单是否被「本加盟商体系之外」的账号（如总部线路/调度）抢过。
     *
     * @param int[] $scopeIds 加盟商ID（含下级）
     * @return int[]
     */
    protected static function getScopeAuthGroupIdsForOrderScope(array $scopeIds): array
    {
        $groupIds = [];
        foreach ($scopeIds as $sid) {
            $gid = (int)Db::name('franchise')->where('id', (int)$sid)->value('group_id');
            if ($gid <= 0) {
                continue;
            }
            foreach (self::getAuthGroupIdsInSubtree($gid) as $g) {
                $groupIds[(int)$g] = true;
            }
        }

        return array_keys($groupIds);
    }

    /**
     * 生成「未被本加盟商体系之外账号抢过」的 SQL 条件（抢单记录见 fa_admin_order）
     *
     * @param string $orderIdExpr   订单主键表达式（如 `fa_order`.`id`）
     * @param int[]  $scopeGroupIds 本加盟商体系的角色组ID；为空表示只能收完全没人抢过的订单
     */
    protected static function buildNotGrabbedOutsideFranchiseSql(string $orderIdExpr, array $scopeGroupIds): string
    {
        if ($scopeGroupIds === []) {
            return "NOT EXISTS (SELECT 1 FROM `fa_admin_order` ao WHERE ao.order_id = {$orderIdExpr})";
        }

        return "NOT EXISTS (SELECT 1 FROM `fa_admin_order` ao WHERE ao.order_id = {$orderIdExpr}"
            . ' AND ao.group_id NOT IN (' . implode(',', array_map('intval', $scopeGroupIds)) . '))';
    }

    /**
     * 订单是否已被「本加盟商体系之外」的账号抢过（单条校验用，口径同上）
     *
     * @param int[] $scopeIds 加盟商ID（含下级）
     */
    protected static function orderGrabbedOutsideFranchiseScope(array $scopeIds, int $orderId): bool
    {
        if ($orderId <= 0) {
            return false;
        }
        $scopeGroupIds = self::getScopeAuthGroupIdsForOrderScope($scopeIds);
        $query = Db::name('admin_order')->where('order_id', $orderId);
        if ($scopeGroupIds !== []) {
            $query->where('group_id', 'not in', $scopeGroupIds);
        }

        return (bool)$query->value('id');
    }

    /**
     * 把“加盟商范围”应用到订单查询：加盟商管理员=自己+所有下级；调度/线路子账号=其所属那家加盟商。
     * 范围 = 绑定会员的订单 OR 该加盟商区域内“未绑定普通用户”的订单（多区域）。
     *
     * @param \think\db\Query $query
     */
    public static function applyFranchiseOrderScope($query, int $adminId, string $tableExpr = '`fa_order`'): void
    {
        $fr = self::resolveFranchiseForAdmin($adminId);
        if (!$fr) {
            return;
        }
        $isManager = ((int)$fr['admin_id'] === $adminId);
        $scopeIds = $isManager ? self::getSubtreeFranchiseIds((int)$fr['id']) : [(int)$fr['id']];
        $memberIds = Db::name('franchise_member')
            ->where('franchise_id', 'in', $scopeIds)
            ->column('user_id');
        $memberIds = array_values(array_unique(array_map('intval', $memberIds ?: [])));
        // 仅剔除绑定到“总部或其它加盟商后台”的用户；绑定到“本加盟商自己后台”的员工应保留可见
        if ($memberIds !== []) {
            $memberIds = self::excludeForeignBoundUsers($memberIds, $scopeIds);
        }
        $regionIds = [];
        foreach ($scopeIds as $sid) {
            $rids = json_decode((string)Db::name('franchise')->where('id', $sid)->value('region_ids'), true) ?: [];
            foreach ($rids as $rid) {
                $regionIds[] = (int)$rid;
            }
        }
        $regionIds = array_values(array_unique($regionIds));

        $ucol = $tableExpr . '.`userid`';
        $lcol = $tableExpr . '.`loading`';
        $icol = $tableExpr . '.`id`';
        $parts = [];
        $bind = [];
        $seq = 0;
        if ($memberIds !== []) {
            $parts[] = $ucol . ' IN (' . implode(',', array_map('intval', $memberIds)) . ')';
        }
        // 区域公海只收「尚未被本加盟商体系之外账号（如总部线路/调度）抢过」的订单：
        // 已被外部处理过的历史订单不该出现在加盟商后台（如加盟商入驻后才匹配到区域的老单）
        $notGrabbedOutside = self::buildNotGrabbedOutsideFranchiseSql($icol, self::getScopeAuthGroupIdsForOrderScope($scopeIds));
        foreach ($regionIds as $rid) {
            $names = self::getOrderRegionMatchSegments($rid);
            if ($names === []) {
                continue;
            }
            $conds = [];
            foreach ($names as $nm) {
                $k1 = 'frScope_v' . (++$seq);
                $k2 = 'frScope_v' . (++$seq);
                $conds[] = "(la.detailed_address LIKE :{$k1} OR IFNULL(la.address,'') LIKE :{$k2})";
                $bind[$k1] = '%' . $nm . '%';
                $bind[$k2] = '%' . $nm . '%';
            }
            // 区域只匹配“未被任何加盟商绑定”的普通用户订单，避免已绑定他加盟商的订单被本加盟商看到
            $unbound = "NOT EXISTS (SELECT 1 FROM `fa_franchise_member` fm WHERE fm.user_id = " . $ucol . ')'
                . " AND NOT EXISTS (SELECT 1 FROM `fa_admin_user_bind` ab WHERE ab.user_id = " . $ucol . ')';
            $parts[] = "EXISTS (SELECT 1 FROM `fa_user_address` la WHERE la.id = " . $lcol . ' AND '
                . implode(' AND ', $conds) . ') AND ' . $unbound . ' AND ' . $notGrabbedOutside;
        }
        if ($parts === []) {
            $query->where('id', -1);

            return;
        }
        $query->whereRaw('(' . implode(' OR ', $parts) . ')', $bind);
    }

    /**
     * 按指定加盟商 id 把“加盟商范围”应用到订单查询（用于订单管理按加盟商筛选）。
     * 范围 = 该加盟商(含其下级)绑定会员的订单 OR 区域内“未绑定普通用户”的订单。
     *
     * @param \think\db\Query $query
     */
    public static function applyFranchiseOrderScopeById($query, int $franchiseId, string $tableExpr = '`fa_order`'): void
    {
        $fr = Db::name('franchise')->where('id', $franchiseId)->find();
        if (!$fr) {
            $query->where('id', -1);

            return;
        }
        $scopeIds = self::getSubtreeFranchiseIds($franchiseId);
        $memberIds = Db::name('franchise_member')
            ->where('franchise_id', 'in', $scopeIds)
            ->column('user_id');
        $memberIds = array_values(array_unique(array_map('intval', $memberIds ?: [])));
        // 仅剔除绑定到“总部或其它加盟商后台”的用户；绑定到“本加盟商自己后台”的员工应保留可见
        if ($memberIds !== []) {
            $memberIds = self::excludeForeignBoundUsers($memberIds, $scopeIds);
        }

        $regionIds = [];
        foreach ($scopeIds as $sid) {
            $rids = json_decode((string)Db::name('franchise')->where('id', $sid)->value('region_ids'), true) ?: [];
            foreach ($rids as $rid) {
                $regionIds[] = (int)$rid;
            }
        }
        $regionIds = array_values(array_unique($regionIds));

        $ucol = $tableExpr . '.`userid`';
        $lcol = $tableExpr . '.`loading`';
        $parts = [];
        $bind = [];
        $seq = 0;
        if ($memberIds !== []) {
            $parts[] = $ucol . ' IN (' . implode(',', array_map('intval', $memberIds)) . ')';
        }
        foreach ($regionIds as $rid) {
            $names = self::getOrderRegionMatchSegments($rid);
            if ($names === []) {
                continue;
            }
            $conds = [];
            foreach ($names as $nm) {
                $k1 = 'frById_v' . (++$seq);
                $k2 = 'frById_v' . (++$seq);
                $conds[] = "(la.detailed_address LIKE :{$k1} OR IFNULL(la.address,'') LIKE :{$k2})";
                $bind[$k1] = '%' . $nm . '%';
                $bind[$k2] = '%' . $nm . '%';
            }
            // 区域只匹配“未被任何加盟商绑定”的普通用户订单
            $unbound = "NOT EXISTS (SELECT 1 FROM `fa_franchise_member` fm WHERE fm.user_id = " . $ucol . ')'
                . " AND NOT EXISTS (SELECT 1 FROM `fa_admin_user_bind` ab WHERE ab.user_id = " . $ucol . ')';
            $parts[] = "EXISTS (SELECT 1 FROM `fa_user_address` la WHERE la.id = " . $lcol . ' AND '
                . implode(' AND ', $conds) . ') AND ' . $unbound;
        }
        if ($parts === []) {
            $query->where('id', -1);

            return;
        }
        $query->whereRaw('(' . implode(' OR ', $parts) . ')', $bind);
    }

    /**
     * 按加盟商名称关键词搜索并过滤订单（用于订单管理「所属加盟商」搜索）。
     * 命中加盟商（含其二级子树）的订单范围。
     *
     * @param \think\db\Query $query
     */
    public static function applyFranchiseOrderScopeByKeyword($query, string $keyword, string $tableExpr = '`fa_order`'): void
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return;
        }
        // 按名称关键词匹配加盟商（正常状态）
        $hitFranchiseIds = Db::name('franchise')
            ->where('status', 'normal')
            ->where('name', 'like', '%' . $keyword . '%')
            ->column('id');
        $hitFranchiseIds = array_values(array_unique(array_map('intval', $hitFranchiseIds ?: [])));
        if ($hitFranchiseIds === []) {
            $query->where('id', -1);

            return;
        }
        // 展开为所有命中的加盟商（含各自下级子树）
        $scopeIds = [];
        foreach ($hitFranchiseIds as $fid) {
            foreach (self::getSubtreeFranchiseIds($fid) as $sid) {
                $scopeIds[] = (int)$sid;
            }
        }
        $scopeIds = array_values(array_unique($scopeIds));

        $memberIds = Db::name('franchise_member')
            ->where('franchise_id', 'in', $scopeIds)
            ->column('user_id');
        $memberIds = array_values(array_unique(array_map('intval', $memberIds ?: [])));
        // 仅剔除绑定到“总部或其它加盟商后台”的用户；绑定到“本加盟商自己后台”的员工应保留可见
        if ($memberIds !== []) {
            $memberIds = self::excludeForeignBoundUsers($memberIds, $scopeIds);
        }

        $regionIds = [];
        foreach ($scopeIds as $sid) {
            $rids = json_decode((string)Db::name('franchise')->where('id', $sid)->value('region_ids'), true) ?: [];
            foreach ($rids as $rid) {
                $regionIds[] = (int)$rid;
            }
        }
        $regionIds = array_values(array_unique($regionIds));

        $ucol = $tableExpr . '.`userid`';
        $lcol = $tableExpr . '.`loading`';
        $parts = [];
        $bind = [];
        $seq = 0;
        if ($memberIds !== []) {
            $parts[] = $ucol . ' IN (' . implode(',', array_map('intval', $memberIds)) . ')';
        }
        foreach ($regionIds as $rid) {
            $names = self::getOrderRegionMatchSegments($rid);
            if ($names === []) {
                continue;
            }
            $conds = [];
            foreach ($names as $nm) {
                $k1 = 'frKw_v' . (++$seq);
                $k2 = 'frKw_v' . (++$seq);
                $conds[] = "(la.detailed_address LIKE :{$k1} OR IFNULL(la.address,'') LIKE :{$k2})";
                $bind[$k1] = '%' . $nm . '%';
                $bind[$k2] = '%' . $nm . '%';
            }
            $unbound = "NOT EXISTS (SELECT 1 FROM `fa_franchise_member` fm WHERE fm.user_id = " . $ucol . ')'
                . " AND NOT EXISTS (SELECT 1 FROM `fa_admin_user_bind` ab WHERE ab.user_id = " . $ucol . ')';
            $parts[] = "EXISTS (SELECT 1 FROM `fa_user_address` la WHERE la.id = " . $lcol . ' AND '
                . implode(' AND ', $conds) . ') AND ' . $unbound;
        }
        if ($parts === []) {
            $query->where('id', -1);

            return;
        }
        $query->whereRaw('(' . implode(' OR ', $parts) . ')', $bind);
    }

    /**
     * 解析订单所属加盟商（用于订单管理展示）：
     *  - 订单会员已绑定加盟商 -> 该加盟商；
     *  - 未绑定任何加盟商/管理员，且装货地命中某加盟商区域 -> 该加盟商；
     *  - 否则视为总部。
     *
     * @return array{franchise_id:int,franchise_name:string,franchise_level:int,franchise_detail:string}
     */
    public static function resolveOrderFranchiseInfo(array $order): array
    {
        $userId = (int)($order['userid'] ?? 0);
        if ($userId > 0) {
            if (!array_key_exists($userId, self::$boundUserFranchiseCache)) {
                self::$boundUserFranchiseCache[$userId] = self::resolveBoundUserFranchise($userId);
            }
            $boundFranchiseInfo = self::$boundUserFranchiseCache[$userId];
            if ($boundFranchiseInfo !== null) {
                return $boundFranchiseInfo;
            }
        }
        // 未绑定：按装货地命中加盟商区域（规则已预展开，逐单匹配不再查库）
        foreach (self::franchiseRegionRules() as $rule) {
            if (\app\admin\library\AdminUserBind::orderRowMatchesRegionalNames($order, $rule['names'])) {
                return self::franchiseInfoForId($rule['franchise_id']);
            }
        }

        return ['franchise_id' => 0, 'franchise_name' => '', 'franchise_level' => 0, 'franchise_detail' => '总部'];
    }

    /**
     * 请求内缓存：下单人 ID => 绑定加盟商信息（null 表示未绑定任何加盟商，需继续按装货区域判断）
     *
     * @var array<int,array{franchise_id:int,franchise_name:string,franchise_level:int,franchise_detail:string}|null>
     */
    protected static $boundUserFranchiseCache = [];

    /**
     * 请求内缓存：「加盟商 × 区域名称段」规则表
     *
     * @var array<int,array{franchise_id:int,names:string[]}>|null
     */
    protected static $franchiseRegionRulesCache = null;

    /**
     * 请求内缓存：区域 ID => 用于匹配的名称段
     *
     * @var array<int,string[]>
     */
    protected static $regionMatchSegmentsCache = [];

    /**
     * 请求内缓存：加盟商 ID => 展示信息
     *
     * @var array<int,array{franchise_id:int,franchise_name:string,franchise_level:int,franchise_detail:string}>
     */
    protected static $franchiseInfoCache = [];

    /**
     * 已绑定加盟商的下单人解析（结果请求内缓存，避免同一列表里重复查库）
     *
     * @return array{franchise_id:int,franchise_name:string,franchise_level:int,franchise_detail:string}|null
     */
    protected static function resolveBoundUserFranchise(int $userId): ?array
    {
        // 已绑定后台账号（admin_user_bind）优先：该账号是某加盟商管理员则归该加盟商，否则视为总部
        $adminBind = Db::name('admin_user_bind')->where('user_id', $userId)->find();
        if ($adminBind) {
            $boundFranchiseId = (int)Db::name('franchise')->where('admin_id', (int)$adminBind['admin_id'])->value('id');
            if ($boundFranchiseId > 0) {
                return self::franchiseInfoForId($boundFranchiseId);
            }

            return ['franchise_id' => 0, 'franchise_name' => '', 'franchise_level' => 0, 'franchise_detail' => '总部'];
        }
        $fm = Db::name('franchise_member')->where('user_id', $userId)->find();
        if ($fm) {
            return self::franchiseInfoForId((int)$fm['franchise_id']);
        }

        return null;
    }

    /**
     * 批量预取一批下单人的加盟商归属（列表页在逐行解析前调用，把每行 2 次查询压成整页 3 次）
     *
     * @param int[] $userIds
     */
    public static function prefetchBoundUserFranchise(array $userIds): void
    {
        $todo = [];
        foreach ($userIds as $userId) {
            $userId = (int)$userId;
            if ($userId > 0 && !array_key_exists($userId, self::$boundUserFranchiseCache)) {
                $todo[$userId] = $userId;
            }
        }
        if ($todo === []) {
            return;
        }
        $todo = array_values($todo);

        $bindRows = [];
        foreach (Db::name('admin_user_bind')->whereIn('user_id', $todo)->field('user_id,admin_id')->select() as $row) {
            $uid = (int)$row['user_id'];
            if (!isset($bindRows[$uid])) {
                $bindRows[$uid] = $row;
            }
        }
        $memberRows = [];
        foreach (Db::name('franchise_member')->whereIn('user_id', $todo)->field('user_id,franchise_id')->select() as $row) {
            $uid = (int)$row['user_id'];
            if (!isset($memberRows[$uid])) {
                $memberRows[$uid] = $row;
            }
        }
        $franchiseByAdminId = [];
        $boundAdminIds = array_values(array_unique(array_map('intval', array_column($bindRows, 'admin_id'))));
        if ($boundAdminIds !== []) {
            foreach (Db::name('franchise')->whereIn('admin_id', $boundAdminIds)->field('id,admin_id')->select() as $row) {
                $aid = (int)$row['admin_id'];
                if (!isset($franchiseByAdminId[$aid])) {
                    $franchiseByAdminId[$aid] = (int)$row['id'];
                }
            }
        }

        foreach ($todo as $userId) {
            if (isset($bindRows[$userId])) {
                // 与单行逻辑一致：绑定到某加盟商管理员则归该加盟商，否则视为总部
                $franchiseId = $franchiseByAdminId[(int)$bindRows[$userId]['admin_id']] ?? 0;
                self::$boundUserFranchiseCache[$userId] = $franchiseId > 0
                    ? self::franchiseInfoForId($franchiseId)
                    : ['franchise_id' => 0, 'franchise_name' => '', 'franchise_level' => 0, 'franchise_detail' => '总部'];
            } elseif (isset($memberRows[$userId])) {
                self::$boundUserFranchiseCache[$userId] = self::franchiseInfoForId((int)$memberRows[$userId]['franchise_id']);
            } else {
                // 未绑定任何加盟商：需要按装货区域判断，交给 resolveOrderFranchiseInfo 处理
                self::$boundUserFranchiseCache[$userId] = null;
            }
        }
    }

    /**
     * 把「加盟商 -> region_ids」预展开成「加盟商 + 匹配名称段」列表（保持原有 franchise、region_ids 顺序）
     *
     * @return array<int,array{franchise_id:int,names:string[]}>
     */
    protected static function franchiseRegionRules(): array
    {
        if (self::$franchiseRegionRulesCache !== null) {
            return self::$franchiseRegionRulesCache;
        }
        $franchiseRows = Db::name('franchise')->field('id,region_ids')->select();
        // 先把区域及上级名称批量读入内存，避免下面逐条查 area 表
        $allRegionIds = [];
        foreach ($franchiseRows as $f) {
            foreach ((json_decode((string)($f['region_ids'] ?? '[]'), true) ?: []) as $rid) {
                $rid = (int)$rid;
                if ($rid > 0) {
                    $allRegionIds[$rid] = $rid;
                }
            }
        }
        self::preloadAreaRowsWithAncestors($allRegionIds);
        $rules = [];
        foreach ($franchiseRows as $f) {
            foreach ((json_decode((string)($f['region_ids'] ?? '[]'), true) ?: []) as $rid) {
                $names = self::getOrderRegionMatchSegments((int)$rid);
                if ($names === []) {
                    continue;
                }
                $rules[] = ['franchise_id' => (int)$f['id'], 'names' => $names];
            }
        }
        self::$franchiseRegionRulesCache = $rules;

        return $rules;
    }

    /**
     * 加盟商展示信息（名称 + 层级 + 二级时的一级）
     *
     * @return array{franchise_id:int,franchise_name:string,franchise_level:int,franchise_detail:string}
     */
    private static function franchiseInfoForId(int $franchiseId): array
    {
        if (array_key_exists($franchiseId, self::$franchiseInfoCache)) {
            return self::$franchiseInfoCache[$franchiseId];
        }
        $f = Db::name('franchise')->where('id', $franchiseId)->find();
        if (!$f) {
            return self::$franchiseInfoCache[$franchiseId] = ['franchise_id' => 0, 'franchise_name' => '', 'franchise_level' => 0, 'franchise_detail' => '总部'];
        }
        $level = (int)($f['level'] ?? 0);
        $name = trim((string)($f['name'] ?? ''));
        $parent = '';
        if ($level === 2 && (int)($f['parent_id'] ?? 0) > 0) {
            $parent = trim((string)Db::name('franchise')->where('id', (int)$f['parent_id'])->value('name'));
        }
        $detail = $level === 1
            ? ($name . '（一级）')
            : ($level === 2
                ? ($name . '（二级' . ($parent !== '' ? '，一级：' . $parent : '') . '）')
                : $name);

        return self::$franchiseInfoCache[$franchiseId] = ['franchise_id' => $franchiseId, 'franchise_name' => $name, 'franchise_level' => $level, 'franchise_detail' => $detail];
    }

    /**
     * 判断某个订单是否属于某加盟商的可见范围（用于“抢单/可抢”单条校验）
     * 范围 = 绑定会员订单（$includeSubtree 时含下级） 或 区域内未被任何加盟商绑定的普通用户订单
     */
    public static function canSeeOrder(int $franchiseId, array $order, bool $includeSubtree): bool
    {
        $scopeIds = $includeSubtree ? self::getSubtreeFranchiseIds($franchiseId) : [$franchiseId];
        $userid = (int)($order['userid'] ?? 0);
        if ($userid > 0) {
            $memberIds = Db::name('franchise_member')
                ->where('franchise_id', 'in', $scopeIds)
                ->column('user_id');
            $memberIds = array_map('intval', $memberIds ?: []);
            if (in_array($userid, $memberIds, true)) {
                return true;
            }
            // 已绑定任一加盟商的会员，不走区域规则
            if (Db::name('franchise_member')->where('user_id', $userid)->value('id')) {
                return false;
            }
            // 已绑定任一后台管理员(admin_user_bind)的会员（如总部绑定），也不走区域规则
            if (Db::name('admin_user_bind')->where('user_id', $userid)->value('id')) {
                return false;
            }
        }

        $regionIds = [];
        foreach ($scopeIds as $sid) {
            $rids = json_decode((string)Db::name('franchise')->where('id', $sid)->value('region_ids'), true) ?: [];
            foreach ($rids as $rid) {
                $regionIds[] = (int)$rid;
            }
        }
        if ($regionIds === []) {
            return false;
        }
        $loading = (int)($order['loading'] ?? 0);
        if ($loading <= 0) {
            return false;
        }
        $addr = Db::name('user_address')->where('id', $loading)->field('detailed_address,address')->find();
        if (!$addr) {
            return false;
        }
        foreach ($regionIds as $rid) {
            $names = self::getOrderRegionMatchSegments($rid);
            $ok = true;
            foreach ($names as $nm) {
                $inDet = mb_strpos((string)($addr['detailed_address'] ?? ''), $nm) !== false;
                $inAdr = mb_strpos((string)($addr['address'] ?? ''), $nm) !== false;
                if (!$inDet && !$inAdr) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                // 区域内订单：已被本加盟商体系之外的账号（如总部线路/调度）抢过的不再属于本加盟商范围
                return !self::orderGrabbedOutsideFranchiseScope($scopeIds, (int)($order['id'] ?? 0));
            }
        }

        return false;
    }

    /**
     * 是否“总部直属的线路/调度账号”：
     *  - 不属于任何加盟商（fa_franchise 链路里没有它）；
     *  - 向上也没有「代理(identity=1)」角色组可继承区域/绑定范围（加盟商根组就是 identity=1，已被上一条排除）。
     *
     * 满足这两点的线路/调度都按总部口径取数：总部绑定会员的订单 ＋ 未绑定且装货地不在任何加盟商区域内的订单。
     * 注意：不再要求角色组必须正好挂在 Admin group(id=1) 下——否则总部把线路/调度组建成顶级分组(pid=0)时，
     * 会被当成“无代理组的子后台”，既没有绑定会员也没有区域，导致谁也看不到这类单。
     */
    public static function isHqLineDispatch(int $adminId): bool
    {
        if (array_key_exists($adminId, self::$hqLineDispatchCache)) {
            return self::$hqLineDispatchCache[$adminId];
        }

        return self::$hqLineDispatchCache[$adminId] = self::doIsHqLineDispatch($adminId);
    }

    /**
     * 请求内缓存：管理员 ID => 是否按总部直属线路/调度口径
     *
     * @var array<int,bool>
     */
    protected static $hqLineDispatchCache = [];

    /**
     * 总部直属线路/调度判定逻辑（结果由 isHqLineDispatch 缓存）
     */
    protected static function doIsHqLineDispatch(int $adminId): bool
    {
        if (self::resolveFranchiseForAdmin($adminId) !== null) {
            return false;
        }
        $gid = (int)\app\admin\library\AdminUserBind::getPrimaryBusinessGroupIdForAdmin($adminId);
        if ($gid <= 0) {
            return false;
        }
        $identity = (int)\app\admin\library\AdminUserBind::resolveEffectiveOrderRoleIdentity($gid);
        if (!in_array($identity, [2, 3], true)) {
            return false;
        }
        // 有上级「代理(identity=1)」组：仍按该代理组（加盟商或旧代理）的绑定/区域口径，不走总部口径。
        // 注意：Admin group(id=1) 自己的 identity 也是 1，它不算代理组，否则总部直属的线路/调度
        // 会被当成“挂在代理组下”，退化成只看该组绑定会员（没绑会员时就一条都看不到）。
        $agentGroupId = (int)\app\admin\library\AdminUserBind::getOrderScopeAgentGroupId($gid, $identity);
        if ($agentGroupId > 0 && $agentGroupId !== \app\admin\library\AdminUserBind::SUPER_ADMIN_AUTH_GROUP_ID) {
            return false;
        }

        return true;
    }

    /**
     * 总部直属线路/调度的订单范围：
     *  - 总部（超级管理组下管理员）绑定用户的订单；
     *  - 未绑定任何加盟商/管理员，且装货地“不在任何加盟商区域内”的普通用户订单。
     */
    public static function applyHqOrderScope($query, int $adminId): void
    {
        $scope = self::hqOrderScopeSql($adminId);
        if ($scope['parts'] === []) {
            $query->where('id', -1);

            return;
        }
        $query->whereRaw('(' . implode(' OR ', $scope['parts']) . ')', $scope['bind']);
    }

    /**
     * 请求内缓存：管理员 ID => 总部范围 SQL 片段，列表页 count/select 两次构造查询时复用
     *
     * @var array<int,array{parts:string[],bind:array<string,string>}>
     */
    protected static $hqOrderScopeCache = [];

    /**
     * 构造总部直属线路/调度的订单范围 SQL 片段（结果请求内缓存，避免重复查库）
     *
     * @return array{parts:string[],bind:array<string,string>}
     */
    protected static function hqOrderScopeSql(int $adminId): array
    {
        if (array_key_exists($adminId, self::$hqOrderScopeCache)) {
            return self::$hqOrderScopeCache[$adminId];
        }

        $ucol = '`fa_order`.`userid`';
        $lcol = '`fa_order`.`loading`';
        $hqAdminIds = Db::name('auth_group_access')->where('group_id', 1)->column('uid');
        $hqUserIds = Db::name('admin_user_bind')->where('admin_id', 'in', $hqAdminIds ?: [0])->column('user_id');
        // 也包含“当前账号自己绑定”的会员
        foreach (Db::name('admin_user_bind')->where('admin_id', $adminId)->column('user_id') as $uid) {
            $hqUserIds[] = (int)$uid;
        }
        $hqUserIds = array_values(array_unique(array_map('intval', $hqUserIds ?: [])));

        $parts = [];
        $bind = [];
        $seq = 0;
        if ($hqUserIds !== []) {
            $parts[] = $ucol . ' IN (' . implode(',', $hqUserIds) . ')';
        }

        // 收集所有加盟商占用的区域ID
        $franchiseRegionIds = [];
        foreach (Db::name('franchise')->select() as $f) {
            foreach ((json_decode((string)($f['region_ids'] ?? '[]'), true) ?: []) as $rid) {
                $franchiseRegionIds[(int)$rid] = true;
            }
        }
        // 区域及上级名称一次读入内存，避免逐个区域查 area 表
        self::preloadAreaRowsWithAncestors(array_keys($franchiseRegionIds));
        $claimedParts = [];
        foreach (array_keys($franchiseRegionIds) as $rid) {
            $names = self::getOrderRegionMatchSegments((int)$rid);
            if ($names === []) {
                continue;
            }
            $conds = [];
            foreach ($names as $nm) {
                $k1 = 'hqScope_v' . (++$seq);
                $k2 = 'hqScope_v' . (++$seq);
                $conds[] = "(la.detailed_address LIKE :{$k1} OR IFNULL(la.address,'') LIKE :{$k2})";
                $bind[$k1] = '%' . $nm . '%';
                $bind[$k2] = '%' . $nm . '%';
            }
            $claimedParts[] = "EXISTS (SELECT 1 FROM `fa_user_address` la WHERE la.id = " . $lcol . ' AND '
                . implode(' AND ', $conds) . ')';
        }

        $unbound = "NOT EXISTS (SELECT 1 FROM `fa_franchise_member` fm WHERE fm.user_id = " . $ucol . ')'
            . " AND NOT EXISTS (SELECT 1 FROM `fa_admin_user_bind` ab WHERE ab.user_id = " . $ucol . ')';
        if ($claimedParts !== []) {
            $parts[] = '(' . $unbound . ' AND NOT (' . implode(' OR ', $claimedParts) . '))';
        } else {
            $parts[] = $unbound;
        }

        return self::$hqOrderScopeCache[$adminId] = ['parts' => $parts, 'bind' => $bind];
    }

    /**
     * 总部直属线路/调度抢单时，判断单个订单是否在其范围内（与 applyHqOrderScope 一致）：
     *  - 订单会员属于“总部绑定用户”（超级管理组下管理员绑定 + 当前账号自己绑定）；
     *  - 或：订单会员未绑定任何加盟商/管理员，且装货地“不在任何加盟商区域内”。
     */
    public static function orderInHqScope(array $orderRow, int $adminId): bool
    {
        $uid = (int)($orderRow['userid'] ?? 0);
        if ($uid <= 0) {
            return false;
        }
        $hqAdminIds = Db::name('auth_group_access')->where('group_id', 1)->column('uid');
        $hqUserIds = Db::name('admin_user_bind')->where('admin_id', 'in', $hqAdminIds ?: [0])->column('user_id');
        foreach (Db::name('admin_user_bind')->where('admin_id', $adminId)->column('user_id') as $uid2) {
            $hqUserIds[] = (int)$uid2;
        }
        $hqUserIds = array_values(array_unique(array_map('intval', $hqUserIds ?: [])));
        if (in_array($uid, $hqUserIds, true)) {
            return true;
        }

        // 已绑定任何加盟商或管理员的会员，不走“未绑定且区域未占用”分支
        $bound = Db::name('franchise_member')->where('user_id', $uid)->value('id')
            || Db::name('admin_user_bind')->where('user_id', $uid)->value('id');
        if ($bound) {
            return false;
        }

        // 未绑定：装货地必须“不在任何加盟商区域内”
        $franchiseRegionIds = [];
        foreach (Db::name('franchise')->select() as $f) {
            foreach ((json_decode((string)($f['region_ids'] ?? '[]'), true) ?: []) as $rid) {
                $franchiseRegionIds[(int)$rid] = true;
            }
        }
        foreach (array_keys($franchiseRegionIds) as $rid) {
            $names = self::getOrderRegionMatchSegments((int)$rid);
            if ($names === []) {
                continue;
            }
            if (\app\admin\library\AdminUserBind::orderRowMatchesRegionalNames($orderRow, $names)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 取某行政区用于订单区域匹配的名称段：区/县用「市+区」，市用「省+市」，与现有区域口径一致
     *
     * @return string[]
     */
    protected static function getOrderRegionMatchSegments(int $areaId): array
    {
        if (array_key_exists($areaId, self::$regionMatchSegmentsCache)) {
            return self::$regionMatchSegmentsCache[$areaId];
        }
        $names = [];
        $cur = self::areaRow($areaId);
        $guard = 0;
        while ($cur && $guard < 3) {
            array_unshift($names, (string)$cur['name']);
            $pid = (int)($cur['pid'] ?? 0);
            $cur = $pid > 0 ? self::areaRow($pid) : null;
            $guard++;
        }
        if (count($names) >= 3) {
            return self::$regionMatchSegmentsCache[$areaId] = array_slice($names, -2); // 省/市/区 -> 市+区
        }

        return self::$regionMatchSegmentsCache[$areaId] = $names; // 省/市 -> 省+市
    }

    /**
     * 请求内缓存：行政区 ID => row（只取 id/pid/name 三个字段）
     *
     * @var array<int,array<string,mixed>>
     */
    protected static $areaRowCache = [];

    /**
     * 读取行政区（命中请求内缓存则不再查库）
     *
     * @return array<string,mixed>|null
     */
    protected static function areaRow(int $areaId): ?array
    {
        if ($areaId <= 0) {
            return null;
        }
        if (!array_key_exists($areaId, self::$areaRowCache)) {
            $row = Db::name('area')->where('id', $areaId)->field('id,pid,name')->find();
            self::$areaRowCache[$areaId] = $row ?: [];
        }

        return self::$areaRowCache[$areaId] ?: null;
    }

    /**
     * 批量把行政区（含最多两级上级）读入内存，把「加盟商区域规则」展开时的 N 次查询压成几次
     *
     * @param int[] $areaIds
     */
    protected static function preloadAreaRowsWithAncestors(array $areaIds): void
    {
        $level = [];
        foreach ($areaIds as $areaId) {
            $areaId = (int)$areaId;
            if ($areaId > 0 && !array_key_exists($areaId, self::$areaRowCache)) {
                $level[$areaId] = $areaId;
            }
        }
        for ($depth = 0; $depth < 3 && $level !== []; $depth++) {
            $missing = array_values(array_diff_key($level, self::$areaRowCache));
            if ($missing !== []) {
                foreach (Db::name('area')->whereIn('id', $missing)->field('id,pid,name')->select() as $row) {
                    self::$areaRowCache[(int)$row['id']] = $row;
                }
                // 不存在的 id 也标空，避免后续反复查库
                foreach ($missing as $id) {
                    if (!array_key_exists((int)$id, self::$areaRowCache)) {
                        self::$areaRowCache[(int)$id] = [];
                    }
                }
            }
            $next = [];
            foreach (array_keys($level) as $id) {
                $pid = (int)(self::$areaRowCache[(int)$id]['pid'] ?? 0);
                if ($pid > 0 && !array_key_exists($pid, self::$areaRowCache)) {
                    $next[$pid] = $pid;
                }
            }
            $level = $next;
        }
    }

    public static function canOperate(int $franchiseId, ?string &$reason = null): bool
    {
        if ($franchiseId <= 0) {
            $reason = '加盟商信息缺失';

            return false;
        }
        foreach (self::getAncestorFranchiseIds($franchiseId) as $id) {
            $row = Db::name('franchise')->where('id', $id)->find();
            if (!$row) {
                $reason = '加盟商信息缺失';

                return false;
            }
            if (($row['status'] ?? 'normal') !== 'normal') {
                $reason = '加盟商已被禁用';

                return false;
            }
            if ((float)$row['wallet_balance'] <= 0) {
                $name = $row['name'] ?? ('#' . $id);
                $reason = '加盟商「' . $name . '」钱包余额不足，禁止操作';

                return false;
            }
        }

        return true;
    }

    public static function canManageMember(int $franchiseId, int $userId): bool
    {
        $ids = self::getSubtreeFranchiseIds($franchiseId);
        if ($ids === []) {
            return false;
        }

        return (bool)Db::name('franchise_member')
            ->where('user_id', $userId)
            ->where('franchise_id', 'in', $ids)
            ->value('id');
    }

    public static function findUserByMobile(string $mobile): ?array
    {
        if ($mobile === '') {
            return null;
        }
        // 仅普通用户(identity=1)可绑定；司机(2)/专线(3)不参与
        $row = Db::name('user')->where('mobile', $mobile)->where('identity', '1')->find();

        return $row ?: null;
    }

    public static function bindMember(int $franchiseId, int $userId, int $operatorAdminId = 0): array
    {
        if (!self::canOperate($franchiseId, $reason)) {
            return ['success' => false, 'msg' => $reason];
        }
        if ($userId <= 0) {
            return ['success' => false, 'msg' => '会员参数错误'];
        }
        if (!Db::name('user')->where('id', $userId)->value('id')) {
            return ['success' => false, 'msg' => '会员不存在'];
        }
        $bindUserIdentity = (string)Db::name('user')->where('id', $userId)->value('identity');
        if ($bindUserIdentity !== '1') {
            return ['success' => false, 'msg' => '仅普通用户可绑定，司机/专线不能绑定'];
        }
        // 清理“孤儿” franchise_member：其对应的后台绑定(admin_user_bind)已不存在，视为未绑定，允许重新绑定
        $existing = Db::name('franchise_member')->where('user_id', $userId)->find();
        if ($existing) {
            $fr = Db::name('franchise')->where('id', (int)$existing['franchise_id'])->find();
            $stillBound = $fr && (bool)Db::name('admin_user_bind')
                ->where('user_id', $userId)
                ->where('admin_id', (int)$fr['admin_id'])
                ->value('id');
            if (!$stillBound) {
                Db::name('franchise_member')->where('user_id', $userId)->delete();
                $existing = null;
            }
        }
        if ($existing) {
            if ((int)$existing['franchise_id'] === $franchiseId) {
                return ['success' => false, 'msg' => '该会员已绑定到本加盟商'];
            }

            return ['success' => false, 'msg' => '该会员已被其他加盟商绑定，无法重复绑定'];
        }

        // 会员若已绑定到其它后台账号（如总部），需先解除原绑定，避免“一个用户绑两个后台”
        $relBind = Db::name('admin_user_bind')->where('user_id', $userId)->find();
        if ($relBind) {
            $relAdminFranchiseId = (int)Db::name('franchise')->where('admin_id', (int)$relBind['admin_id'])->value('id');
            if ($relAdminFranchiseId > 0 && $relAdminFranchiseId === $franchiseId) {
                return ['success' => false, 'msg' => '该会员已绑定到本加盟商'];
            }
            if ($relAdminFranchiseId > 0) {
                return ['success' => false, 'msg' => '该会员已被其他加盟商绑定，无法重复绑定'];
            }

            return ['success' => false, 'msg' => '该会员已绑定到其他后台账号，请先解除原绑定'];
        }

        $franchise = Db::name('franchise')->where('id', $franchiseId)->find();
        Db::startTrans();
        try {
            Db::name('franchise_member')->insert([
                'franchise_id'      => $franchiseId,
                'user_id'           => $userId,
                'commission_percent'=> '0.00',
                'createtime'        => time(),
            ]);
            $bind = Db::name('admin_user_bind')->where('user_id', $userId)->find();
            if (!$bind && !empty($franchise['admin_id'])) {
                Db::name('admin_user_bind')->insert([
                    'admin_id'   => (int)$franchise['admin_id'],
                    'user_id'    => $userId,
                    'createtime' => time(),
                ]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();

            return ['success' => false, 'msg' => '绑定失败：' . $e->getMessage()];
        }

        return ['success' => true, 'msg' => '绑定成功'];
    }

    /**
     * 修改会员：username / membertype / member_time / commission_percent
     * 扣费规则：
     *  - 仅改 username：不扣费
     *  - 设为“正式员工(membertype=3)”：免费（走正式员工名额）
     *  - 其余（改到期时间 / 设为普通/兼职/会展等）：按月扣费（不足一月按一月）
     */
    public static function changeMemberIdentityTime(int $franchiseId, int $userId, array $params): array
    {
        if (!self::canOperate($franchiseId, $reason)) {
            return ['success' => false, 'msg' => $reason];
        }
        if (!self::canManageMember($franchiseId, $userId)) {
            return ['success' => false, 'msg' => '无权限操作该会员'];
        }
        // 加盟商可改：用户名、平台抽佣、职位、会员到期时间
        $allow = ['username', 'platform_commission', 'membertype', 'member_time'];
        $update = array_intersect_key($params, array_flip($allow));
        $update = array_filter($update, static function ($v) {
            return $v !== null && $v !== '';
        });
        if ($update === []) {
            return ['success' => false, 'msg' => '请至少设置一项要修改的内容'];
        }
        $user = Db::name('user')->where('id', $userId)->find();
        if (!$user) {
            return ['success' => false, 'msg' => '会员不存在'];
        }

        // 到期时间：先规范化，格式不对直接返回提示
        $newTimeTs = null;
        if (isset($update['member_time'])) {
            $newTimeTs = self::normalizeTime($update['member_time']);
            if ($newTimeTs === null) {
                return ['success' => false, 'msg' => '会员到期时间格式不正确，请重新选择到期日期'];
            }
        }
        $oldTimeTs = self::normalizeTime($user['member_time'] ?? '');
        // 只对“真正有改动”的字段计费/更新（避免已兼职会员没改动却再扣150）
        $typeChange  = isset($update['membertype']) && (string)$update['membertype'] !== (string)($user['membertype'] ?? '');
        // 到期时间“按天”比较：填同一天视为未改动（避开库里带时分秒、表单只有日期导致的重复扣费）
        $timeChange  = $newTimeTs !== null
            && ($oldTimeTs === null || date('Y-m-d', $newTimeTs) !== date('Y-m-d', $oldTimeTs));
        $usernameChange = isset($update['username']) && (string)$update['username'] !== (string)($user['username'] ?? '');
        $platformChange = isset($update['platform_commission']) && (string)$update['platform_commission'] !== (string)($user['platform_commission'] ?? '');

        $realUpdate = [];
        if ($usernameChange) { $realUpdate['username'] = $update['username']; }
        if ($platformChange) { $realUpdate['platform_commission'] = $update['platform_commission']; }
        if ($typeChange) { $realUpdate['membertype'] = $update['membertype']; }
        if ($timeChange) { $realUpdate['member_time'] = $newTimeTs; }

        if ($realUpdate === []) {
            return ['success' => true, 'msg' => '未做任何修改（不扣费）', 'fee' => 0, 'months' => 0];
        }

        $setFormal = $typeChange && (string)$update['membertype'] === self::MEMBERTYPE_FORMAL;
        $franchise = Db::name('franchise')->where('id', $franchiseId)->find();
        $fee = 0.0;
        $months = 0;
        $feeBase = 0.0;
        $chargeDetail = null;

        // 正式员工：免费但需名额
        if ($setFormal) {
            $quota = (int)($franchise['formal_employee_quota'] ?? 0);
            if ($quota > 0) {
                $current = self::getFormalEmployeeCount($franchiseId);
                $isAlreadyFormal = (string)($user['membertype'] ?? '') === self::MEMBERTYPE_FORMAL;
                if (!$isAlreadyFormal && $current >= $quota) {
                    return ['success' => false, 'msg' => '正式员工名额已满（上限 ' . $quota . '），无法设为正式员工'];
                }
            }
            // 设为正式员工不扣费，但到期时间已过期时系统会自动降级，先提示修改到期时间
            $formalTime = $timeChange ? (int)$newTimeTs : self::normalizeTime($user['member_time'] ?? '');
            if ($formalTime !== null && self::memberMonthsDetail(time(), $formalTime)['expired']) {
                return [
                    'success' => false,
                    'msg'     => '会员到期时间（' . date('Y-m-d', $formalTime) . '）已早于当前日期，请先修改会员到期时间，再设为正式员工',
                    'expired' => true,
                    'months'  => 0,
                    'fee'     => 0,
                ];
            }
        } else {
            // 计费：仅当改了“到期时间”或“职位(非正式)”才收费
            if ($timeChange || $typeChange) {
                $cfg = self::getGlobalConfig();
                $feeBase = (float)$cfg['member_month_fee'];
                $now = time();
                if ($timeChange) {
                    $newTime = (int)$newTimeTs;
                    $chargeDetail = self::memberMonthsDetail($now, $newTime);
                    // 到期时间比现在早（含今天）：先提示修改到期时间，避免“已经过期还在扣费”
                    if ($chargeDetail['expired']) {
                        return [
                            'success' => false,
                            'msg'     => '会员到期时间（' . date('Y-m-d', $newTime) . '）不晚于当前日期（' . date('Y-m-d', $now) . '），请先修改会员到期时间',
                            'expired' => true,
                            'months'  => 0,
                            'fee'     => 0,
                        ];
                    }
                    $months = $chargeDetail['months'];
                } else {
                    // 只改职位：按月 1 个月计费；但会员原到期时间已过期时，先提示修改到期时间
                    $existTime = self::normalizeTime($user['member_time'] ?? '');
                    if ($existTime !== null && self::memberMonthsDetail($now, $existTime)['expired']) {
                        return [
                            'success' => false,
                            'msg'     => '该会员的到期时间（' . date('Y-m-d', $existTime) . '）已早于当前日期（' . date('Y-m-d', $now) . '），请先修改会员到期时间后再修改职位',
                            'expired' => true,
                            'months'  => 0,
                            'fee'     => 0,
                        ];
                    }
                    $months = 1;
                }
                $fee = round($months * $feeBase, 2);
            }
        }

        Db::startTrans();
        try {
            if ($fee > 0) {
                $locked = Db::name('franchise')->where('id', $franchiseId)->lock(true)->find();
                $before = round((float)$locked['wallet_balance'], 2);
                $after = round($before - $fee, 2);
                if ($after < 0) {
                    throw new Exception('加盟商钱包余额不足（需扣 ' . $fee . ' 元），无法修改会员');
                }
                Db::name('franchise')->where('id', $franchiseId)->update([
                    'wallet_balance' => $after,
                    'updatetime'     => time(),
                ]);
                Db::name('franchise_wallet_log')->insert([
                    'franchise_id'      => $franchiseId,
                    'type'              => 'expense',
                    'amount'            => $fee,
                    'balance_before'    => $before,
                    'balance_after'     => $after,
                    'related_type'      => self::RELATED_MEMBER,
                    'related_id'        => $userId,
                    'remark'            => '修改会员身份/到期，扣费 ' . $months . ' 个月 × ' . $feeBase,
                    'operator_admin_id' => 0,
                    'operator_name'     => '',
                    'createtime'        => time(),
                ]);
            }
            $realUpdate['updatetime'] = time();
            Db::name('user')->where('id', $userId)->update($realUpdate);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();

            return ['success' => false, 'msg' => $e->getMessage()];
        }

        return [
            'success'     => true,
            'msg'         => $fee > 0 ? ('修改成功，已扣费 ' . $fee . ' 元') : '修改成功（免费/不涉及扣费）',
            'fee'         => $fee,
            'months'      => $months,
            'fee_base'    => $feeBase,
            'whole'       => $chargeDetail ? $chargeDetail['whole'] : true,
            'whole_months'=> $chargeDetail ? $chargeDetail['whole_months'] : $months,
            'extra_days'  => $chargeDetail ? $chargeDetail['extra_days'] : 0,
        ];
    }

    protected static function normalizeTime($value): ?int
    {
        if (is_numeric($value)) {
            $t = (int)$value;

            return $t > 0 ? $t : null;
        }
        $t = strtotime((string)$value);

        return ($t === false || $t <= 0) ? null : $t;
    }

    /**
     * 会员套餐时长换算成“月数”（与后台改会员到期“不足一月按一月”的口径一致）
     *  - 月：数量即月数（3 个月 → 3）
     *  - 年：× 12（1 年 → 12）
     *  - 周：× 7 ÷ 30 向上取整（不足一月按一月）
     */
    public static function memberMonthsFromPackage(int $duration, string $unit): int
    {
        $duration = max(1, $duration);
        switch ($unit) {
            case 'year':
                return $duration * 12;
            case 'week':
                return (int)ceil($duration * 7 / self::DAYS_PER_MONTH);
            case 'month':
            default:
                return $duration;
        }
    }

    /**
     * 小程序端“开通/续费会员”支付成功后，给绑定了该会员的加盟商钱包入账
     *
     * 规则：
     *  - 会员在小程序付款开通会员成功后，若该用户已被某加盟商绑定（fa_franchise_member），
     *    则该加盟商钱包自动增加一笔收入 = 会员月费单价（总部设置，默认 150 元）× 本次开通月数。
     *  - 未绑定加盟商的会员不产生入账。
     *  - 幂等：同一笔会员充值订单（fa_memberorder.id）只会入账一次，避免微信重复回调重复加钱。
     *
     * @param int        $userId  充值会员的用户ID
     * @param int        $orderId 会员充值订单ID（fa_memberorder.id）
     * @param float|null $amount  每月单价；null = 取总部设置的“改会员单价 member_month_fee”
     * @param string     $orderNo 会员充值订单号（仅用于流水备注）
     * @param int        $months  本次开通的月数（季付=3、年付=12；不足一月按一月），入账总额 = 每月单价 × 月数
     *
     * @return array{success:bool,msg:string,franchise_id:int,amount:float,months:int,fee_base:float}
     */
    public static function rewardMemberRecharge(int $userId, int $orderId, ?float $amount = null, string $orderNo = '', int $months = 1): array
    {
        if ($userId <= 0 || $orderId <= 0) {
            return ['success' => false, 'msg' => '参数错误', 'franchise_id' => 0, 'amount' => 0.0, 'months' => 0, 'fee_base' => 0.0];
        }
        $months = max(1, $months);
        $bind = Db::name('franchise_member')->where('user_id', $userId)->find();
        if (!$bind) {
            return ['success' => true, 'msg' => '该会员未绑定加盟商，无需入账', 'franchise_id' => 0, 'amount' => 0.0, 'months' => $months, 'fee_base' => 0.0];
        }
        $franchiseId = (int)$bind['franchise_id'];
        if (!Db::name('franchise')->where('id', $franchiseId)->value('id')) {
            return ['success' => true, 'msg' => '加盟商不存在，跳过入账', 'franchise_id' => 0, 'amount' => 0.0, 'months' => $months, 'fee_base' => 0.0];
        }
        if ($amount === null) {
            $cfg = self::getGlobalConfig();
            $amount = (float)$cfg['member_month_fee'];
        }
        $feeBase = round((float)$amount, 2);
        if ($feeBase <= 0) {
            return ['success' => true, 'msg' => '入账金额为0，无需入账', 'franchise_id' => $franchiseId, 'amount' => 0.0, 'months' => $months, 'fee_base' => 0.0];
        }
        // 入账总额 = 每月单价 × 月数（季付=3 个月、年付=12 个月）
        $amount = round($feeBase * $months, 2);

        $user = Db::name('user')->where('id', $userId)->field('username,mobile')->find();
        $userName = $user ? (string)($user['username'] ?? '') : '';
        $userMobile = $user ? (string)($user['mobile'] ?? '') : '';
        $userText = $userName !== '' ? $userName : ($userMobile !== '' ? $userMobile : ('#' . $userId));
        $remark = '会员「' . $userText . '」小程序端开通会员'
            . ($orderNo !== '' ? '（订单' . $orderNo . '）' : '')
            . '，' . $months . ' 个月 × ' . $feeBase . ' 元，自动入账';

        Db::startTrans();
        try {
            // 先锁加盟商行：同一笔/同一加盟商的并发回调在这里串行，配合下面的流水判重保证只入账一次
            $row = Db::name('franchise')->where('id', $franchiseId)->lock(true)->find();
            if (!$row) {
                throw new Exception('加盟商不存在');
            }
            if (Db::name('franchise_wallet_log')
                ->where('franchise_id', $franchiseId)
                ->where('related_type', self::RELATED_MEMBER_RECHARGE)
                ->where('related_id', $orderId)
                ->value('id')) {
                Db::commit();

                return ['success' => true, 'msg' => '该会员充值订单已入账', 'franchise_id' => $franchiseId, 'amount' => 0.0, 'months' => $months, 'fee_base' => $feeBase];
            }
            $before = round((float)$row['wallet_balance'], 2);
            $after = round($before + $amount, 2);
            Db::name('franchise')->where('id', $franchiseId)->update([
                'wallet_balance' => $after,
                'updatetime'     => time(),
            ]);
            Db::name('franchise_wallet_log')->insert([
                'franchise_id'      => $franchiseId,
                'type'              => 'income',
                'amount'            => $amount,
                'balance_before'    => $before,
                'balance_after'     => $after,
                'related_type'      => self::RELATED_MEMBER_RECHARGE,
                'related_id'        => $orderId,
                'remark'            => $remark,
                'operator_admin_id' => 0,
                'operator_name'     => '系统',
                'createtime'        => time(),
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();

            return ['success' => false, 'msg' => $e->getMessage(), 'franchise_id' => $franchiseId, 'amount' => 0.0, 'months' => $months, 'fee_base' => $feeBase];
        }

        return [
            'success'      => true,
            'msg'          => '加盟商「' . ($row['name'] ?? ('#' . $franchiseId)) . '」钱包已入账 ' . $amount . ' 元（' . $months . ' 个月 × ' . $feeBase . ' 元）',
            'franchise_id' => $franchiseId,
            'amount'       => $amount,
            'months'       => $months,
            'fee_base'     => $feeBase,
        ];
    }

    /**
     * 某加盟商绑定会员中“正式员工(membertype=3)”数量
     */
    public static function getFormalEmployeeCount(int $franchiseId): int
    {
        return (int)Db::name('franchise_member')
            ->alias('fm')
            ->join('user u', 'u.id = fm.user_id', 'LEFT')
            ->where('fm.franchise_id', $franchiseId)
            ->where('u.membertype', self::MEMBERTYPE_FORMAL)
            ->count();
    }

    public static function adjustWallet(int $franchiseId, float $delta, string $remark, int $operatorAdminId = 0): array
    {
        if ($franchiseId <= 0 || abs($delta) < 0.005) {
            return ['success' => false, 'msg' => '金额必须大于0'];
        }
        $delta = round($delta, 2);
        Db::startTrans();
        try {
            $row = Db::name('franchise')->where('id', $franchiseId)->lock(true)->find();
            if (!$row) {
                throw new Exception('加盟商不存在');
            }
            $before = round((float)$row['wallet_balance'], 2);
            $after = round($before + $delta, 2);
            if ($after < 0) {
                throw new Exception('钱包余额不足，无法扣减');
            }
            Db::name('franchise')->where('id', $franchiseId)->update([
                'wallet_balance' => $after,
                'updatetime'     => time(),
            ]);
            $opName = $operatorAdminId > 0 ? (string)Db::name('admin')->where('id', $operatorAdminId)->value('nickname') : '';
            Db::name('franchise_wallet_log')->insert([
                'franchise_id'      => $franchiseId,
                'type'              => $delta >= 0 ? 'income' : 'expense',
                'amount'            => abs($delta),
                'balance_before'    => $before,
                'balance_after'     => $after,
                'related_type'      => self::RELATED_ADJUST,
                'related_id'        => $franchiseId,
                'remark'            => $remark,
                'operator_admin_id' => $operatorAdminId,
                'operator_name'     => $opName,
                'createtime'        => time(),
            ]);
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();

            return ['success' => false, 'msg' => $e->getMessage()];
        }

        return ['success' => true, 'msg' => '调整成功'];
    }

    /**
     * 订单完成扣费
     */
    public static function settleOrderFranchiseFee(int $orderId): array
    {
        $order = Db::name('order')->where('id', $orderId)->find();
        if (!$order) {
            return ['success' => false, 'msg' => '订单不存在', 'deducted' => 0];
        }
        $userId = (int)($order['userid'] ?? 0);
        if ($userId <= 0) {
            return ['success' => true, 'msg' => '订单无会员，无需扣费', 'deducted' => 0];
        }
        $memberBind = Db::name('franchise_member')->where('user_id', $userId)->find();
        if (!$memberBind) {
            return ['success' => true, 'msg' => '会员未绑定加盟商，无需扣费', 'deducted' => 0];
        }
        $franchiseId = (int)$memberBind['franchise_id'];
        $franchise = Db::name('franchise')->where('id', $franchiseId)->find();
        if (!$franchise) {
            return ['success' => true, 'msg' => '加盟商不存在，跳过扣费', 'deducted' => 0];
        }

        $base = round((float)($order[self::ORDER_BASE_FIELD] ?? 0), 2);
        if ($base <= 0) {
            return ['success' => true, 'msg' => '订单金额为0，无需扣费', 'deducted' => 0];
        }
        $targets = [
            ['franchise_id' => $franchiseId, 'percent' => (float)$franchise['order_fee_percent']],
        ];
        if ((int)$franchise['level'] === self::LEVEL_2 && (int)$franchise['parent_id'] > 0) {
            $parent = Db::name('franchise')->where('id', (int)$franchise['parent_id'])->find();
            if ($parent) {
                $targets[] = ['franchise_id' => (int)$parent['id'], 'percent' => (float)$parent['order_fee_percent']];
            }
        }
        foreach ($targets as $t) {
            if (Db::name('franchise_wallet_log')->where('franchise_id', $t['franchise_id'])
                ->where('related_type', self::RELATED_ORDER)->where('related_id', $orderId)->value('id')) {
                return ['success' => true, 'msg' => '该订单已扣费', 'deducted' => 0];
            }
        }

        Db::startTrans();
        try {
            $total = 0;
            foreach ($targets as $t) {
                $fee = round($base * ($t['percent'] / 100), 2);
                if ($fee <= 0) {
                    continue;
                }
                $row = Db::name('franchise')->where('id', $t['franchise_id'])->lock(true)->find();
                if (!$row) {
                    throw new Exception('加盟商不存在');
                }
                if ((float)$row['wallet_balance'] < $fee) {
                    throw new Exception('加盟商「' . ($row['name'] ?? '#' . $t['franchise_id']) . '」钱包余额不足，无法完成订单');
                }
                $before = round((float)$row['wallet_balance'], 2);
                $after = round($before - $fee, 2);
                Db::name('franchise')->where('id', $t['franchise_id'])->update([
                    'wallet_balance' => $after, 'updatetime' => time(),
                ]);
                Db::name('franchise_wallet_log')->insert([
                    'franchise_id'      => $t['franchise_id'],
                    'type'              => 'expense',
                    'amount'            => $fee,
                    'balance_before'    => $before,
                    'balance_after'     => $after,
                    'related_type'      => self::RELATED_ORDER,
                    'related_id'        => $orderId,
                    'remark'            => '订单' . $order['orderid'] . '完成，按总运费扣' . $t['percent'] . '%',
                    'operator_admin_id' => 0,
                    'operator_name'     => '系统',
                    'createtime'        => time(),
                ]);
                $total += $fee;
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();

            return ['success' => false, 'msg' => $e->getMessage(), 'deducted' => 0];
        }

        return ['success' => true, 'msg' => '订单扣费完成，共扣 ' . round($total, 2) . ' 元', 'deducted' => round($total, 2)];
    }

    /**
     * 校验区域并返回 {"ok":true,"names":[...]} 或 {"ok":false,"msg":...}
     * level1 存市级 id（area.level=2），level2 存区县 id（area.level=3）且必须属于上级所选城市。
     *
     * @param int[] $regionIds
     * @param int[] $parentRegionIds 上级加盟商区域（一级=城市）
     */
    public static function validateRegion(int $level, array $regionIds, array $parentRegionIds, int $excludeId = 0): array
    {
        $regionIds = array_values(array_filter(array_unique(array_map('intval', $regionIds))));
        if ($regionIds === []) {
            return ['ok' => false, 'msg' => '请选择区域'];
        }
        if ($level === self::LEVEL_1) {
            // 校验都是市级（level=2）
            $cnt = Db::name('area')->where('id', 'in', $regionIds)->where('level', 2)->count();
            if ($cnt !== count($regionIds)) {
                return ['ok' => false, 'msg' => '一级加盟商区域只能是“市”'];
            }
        } else {
            if ($parentRegionIds === []) {
                return ['ok' => false, 'msg' => '未找到上级加盟商区域'];
            }
            // 区县必须是 parent 城市的子级
            $districtRows = Db::name('area')->where('id', 'in', $regionIds)->field('id,pid')->select();
            if (count($districtRows) !== count($regionIds)) {
                return ['ok' => false, 'msg' => '二级加盟商区域只能是“区/县”'];
            }
            foreach ($districtRows as $d) {
                if (!in_array((int)$d['pid'], $parentRegionIds, true)) {
                    return ['ok' => false, 'msg' => '二级加盟商区域必须位于其上级加盟商所选城市内'];
                }
            }
        }

        // 区域唯一：该市/区县不能被其他加盟商占用
        $occupied = [];
        $others = Db::name('franchise')->where('id', '<>', $excludeId)->select();
        foreach ($others as $f) {
            $rids = json_decode((string)($f['region_ids'] ?? '[]'), true) ?: [];
            foreach ($rids as $rid) {
                $occupied[(int)$rid] = true;
            }
        }
        foreach ($regionIds as $rid) {
            if (isset($occupied[$rid])) {
                $areaName = Db::name('area')->where('id', $rid)->value('name');

                return ['ok' => false, 'msg' => '区域「' . $areaName . '」已被其他加盟商占用，请更换'];
            }
        }

        return ['ok' => true, 'region_ids' => $regionIds];
    }

    /**
     * 把区域写入角色组 city（取第一个区域的“省/市(/区)”路径，用于现有单区域订单范围；完整多区域由 franchise.region_ids 驱动）
     */
    public static function writeRegionToGroupCity(int $group_id, array $regionIds): void
    {
        if (!$group_id || $regionIds === []) {
            Db::name('auth_group')->where('id', $group_id)->update(['city' => '']);

            return;
        }
        $first = (int)$regionIds[0];
        $area = Db::name('area')->where('id', $first)->find();
        if (!$area) {
            return;
        }
        $names = [];
        $cur = $area;
        $guard = 0;
        while ($cur && $guard < 3) {
            $names[] = $cur['name'];
            $cur = (int)$cur['pid'] > 0 ? Db::name('area')->where('id', (int)$cur['pid'])->find() : null;
            $guard++;
        }
        $names = array_reverse($names); // 省/市/区
        Db::name('auth_group')->where('id', $group_id)->update(['city' => implode('/', $names)]);
    }

    public static function getAuthGroupIdsInSubtree(int $rootGroupId): array
    {
        if ($rootGroupId <= 0) {
            return [];
        }
        $rows = Db::name('auth_group')->field('id,pid')->select();
        $children = [];
        foreach ($rows as $r) {
            $children[(int)$r['pid']][] = (int)$r['id'];
        }
        $ids = [$rootGroupId];
        $stack = $children[$rootGroupId] ?? [];
        while ($stack !== []) {
            $id = (int)array_pop($stack);
            $ids[] = $id;
            if (!empty($children[$id])) {
                foreach ($children[$id] as $c) {
                    $stack[] = $c;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * 创建加盟商（一级：总部创建；二级：一级创建）
     *
     * @param array $data name,contact,mobile,login_username,login_password,region_ids[],formal_employee_quota,order_fee_percent
     * @param \app\common\library\Auth|null $auth
     */
    public static function createFranchise(int $parentFranchiseId, array $data, $auth = null): array
    {
        if (empty($data['name']) || empty($data['login_username']) || empty($data['login_password'])) {
            return ['success' => false, 'msg' => '请填写完整信息'];
        }
        if (isset($data['mobile']) && !preg_match('/^1[3-9]\d{9}$/', $data['mobile'])) {
            return ['success' => false, 'msg' => '电话格式错误'];
        }
        $level = self::LEVEL_1;
        $parentGroupId = 0;
        $parentRegionIds = [];
        if ($parentFranchiseId > 0) {
            $parent = Db::name('franchise')->where('id', $parentFranchiseId)->find();
            if (!$parent) {
                return ['success' => false, 'msg' => '上级加盟商不存在'];
            }
            if ((int)$parent['level'] !== self::LEVEL_1) {
                return ['success' => false, 'msg' => '最多支持二级，二级加盟商不能再发展下级'];
            }
            $level = self::LEVEL_2;
            $parentGroupId = (int)$parent['group_id'];
            $parentRegionIds = json_decode((string)$parent['region_ids'], true) ?: [];
            if (!self::canOperate($parentFranchiseId, $reason)) {
                return ['success' => false, 'msg' => $reason];
            }
        } else {
            $parentGroupId = (int)Db::name('auth_group')->where('id', 1)->value('id');
            if ($parentGroupId <= 0) {
                return ['success' => false, 'msg' => '未找到总部角色组'];
            }
        }

        $regionIds = isset($data['region_ids']) && is_array($data['region_ids'])
            ? array_values(array_filter(array_map('intval', $data['region_ids']))) : [];
        $vr = self::validateRegion($level, $regionIds, $parentRegionIds);
        if (!$vr['ok']) {
            return ['success' => false, 'msg' => $vr['msg']];
        }
        $regionIds = $vr['region_ids'];

        if ($level === self::LEVEL_2 && $parentRegionIds !== []) {
            // 已由 validateRegion 校验区县属于上级城市
        }

        if (Db::name('admin')->where('username', $data['login_username'])->value('id')) {
            return ['success' => false, 'msg' => '登录名已存在'];
        }
        $now = time();
        // 默认权限：复制上级角色组规则（后续在“角色组”中按需裁剪）
        $parentRules = (string)Db::name('auth_group')->where('id', $parentGroupId)->value('rules');
        $tplRoot = self::getTemplateFranchise();
        $tplRootRules = $tplRoot
            ? (string)Db::name('auth_group')->where('id', (int)$tplRoot['group_id'])->value('rules')
            : '';
        // 优先复制“模板加盟商”根组权限（用户已配置好的菜单），否则回退到精简默认
        $groupRules = $tplRootRules !== '' ? $tplRootRules : self::getFranchiseDefaultRuleIds();
        if ($groupRules === '') {
            $groupRules = $parentRules === '' ? '*' : $parentRules;
        }
        $salt = \fast\Random::alnum();
        $password = $auth ? $auth->getEncryptPassword($data['login_password'], $salt)
            : \app\common\library\Auth::instance()->getEncryptPassword($data['login_password'], $salt);

        Db::startTrans();
        try {
            $group = [
                'pid'           => $parentGroupId,
                'name'          => $data['name'] . ($level === self::LEVEL_2 ? '（二级加盟商）' : '（加盟商）'),
                'identity'      => '1',
                'rules'         => $groupRules,
                'createtime'    => $now,
                'updatetime'    => $now,
                'status'        => 'normal',
                'city'          => '',
                'province'      => '',
                'district'      => '',
            ];
            $groupId = (int)Db::name('auth_group')->insertGetId($group);
            self::writeRegionToGroupCity($groupId, $regionIds);
            $adminId = (int)Db::name('admin')->insertGetId([
                'username'    => $data['login_username'],
                'nickname'    => $data['name'],
                'password'    => $password,
                'salt'        => $salt,
                'avatar'      => '/assets/img/avatar.png',
                'email'       => '',
                'mobile'      => $data['mobile'] ?? '',
                'createtime'  => $now,
                'updatetime'  => $now,
                'status'      => 'normal',
                'loginfailure'=> 0,
                'logintime'   => 0,
                'loginip'     => '',
                'token'       => '',
                'city'        => '',
            ]);
            Db::name('auth_group_access')->insert(['uid' => $adminId, 'group_id' => $groupId]);

            $defaultPercent = $level === self::LEVEL_2 ? '1.500' : '1.000';
            $franchiseId = (int)Db::name('franchise')->insertGetId([
                'parent_id'         => $parentFranchiseId,
                'level'             => $level,
                'name'              => $data['name'],
                'contact'           => $data['contact'] ?? '',
                'mobile'            => $data['mobile'] ?? '',
                'admin_id'          => $adminId,
                'group_id'          => $groupId,
                'region_ids'        => json_encode($regionIds),
                'wallet_balance'    => '0.00',
                'formal_employee_quota' => (int)($data['formal_employee_quota'] ?? 0),
                'line_quota'        => (int)($data['line_quota'] ?? 0),
                'dispatch_quota'    => (int)($data['dispatch_quota'] ?? 0),
                'order_fee_percent' => isset($data['order_fee_percent']) && $data['order_fee_percent'] !== ''
                    ? (string)$data['order_fee_percent'] : $defaultPercent,
                'logistics_audit'   => (int)($data['logistics_audit'] ?? 0),
                'contract'          => (string)($data['contract'] ?? ''),
                'status'            => 'normal',
                'remark'            => $data['remark'] ?? '',
                'createtime'        => $now,
                'updatetime'        => $now,
            ]);

            if ($level === self::LEVEL_2) {
                $cfg = self::getGlobalConfig();
                $parent = Db::name('franchise')->where('id', $parentFranchiseId)->lock(true)->find();
                $fee = (float)$cfg['add_level2_fee'];
                $before = round((float)$parent['wallet_balance'], 2);
                $after = round($before - $fee, 2);
                if ($after < 0) {
                    throw new Exception('上级加盟商钱包余额不足，无法新增二级加盟商（需扣 ' . $fee . ' 元）');
                }
                Db::name('franchise')->where('id', $parentFranchiseId)->update(['wallet_balance' => $after, 'updatetime' => $now]);
                Db::name('franchise_wallet_log')->insert([
                    'franchise_id'      => $parentFranchiseId,
                    'type'              => 'expense',
                    'amount'            => $fee,
                    'balance_before'    => $before,
                    'balance_after'     => $after,
                    'related_type'      => self::RELATED_ADD_FRANCHISEE,
                    'related_id'        => $franchiseId,
                    'remark'            => '新增二级加盟商「' . $data['name'] . '」扣费',
                    'operator_admin_id' => 0,
                    'operator_name'     => '',
                    'createtime'        => $now,
                ]);
            }
            // 自动创建 线路/调度/财务 子角色组（供加盟商添加员工）
            self::ensureFranchiseChildGroups((int)$franchiseId);
            Db::commit();
        } catch (\Exception | PDOException $e) {
            Db::rollback();

            return ['success' => false, 'msg' => $e->getMessage()];
        }

        return ['success' => true, 'msg' => '加盟商创建成功', 'franchise_id' => $franchiseId];
    }
}
