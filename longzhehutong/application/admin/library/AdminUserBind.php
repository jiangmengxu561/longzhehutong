<?php

namespace app\admin\library;

use think\Config;
use think\Db;

/**
 * 子后台管理员与前台会员的绑定关系（用于非超级管理员仅能管理已绑定会员）
 */
class AdminUserBind
{
    /** 与订单模块 fa_auth_group.identity 一致：1=代理，2=线路，3=调度 */
    public const AUTH_GROUP_IDENTITY_AGENT = 1;

    /** 与后台约定一致：fa_auth_group.id=1 为超级管理员角色组（见 Order 控制器 in_array(1, getGroupIds())） */
    public const SUPER_ADMIN_AUTH_GROUP_ID = 1;

    /**
     * 线路/调度：沿 fa_auth_group.pid 向上，祖先链中是否包含超级管理组（通常为 id=1）。
     * 超管树下任意层级的线路/调度订单列表与总后台一致（全国、不按绑定/区域裁剪）；抢单仍走线路/调度逻辑。
     */
    public static function lineDispatchParentIsSuperAdminGroup(int $businessGroupId, int $effectiveIdentity): bool
    {
        // 需求变更：总部直属的线路/调度也应“只看绑定用户”，不再因“总后台直属”而看全量。
        return false;
    } 

    /**
     * fa_admin_order 中已出现的订单 id（任意角色组抢单），子后台待抢列表与抢单「未被任何人抢」一致。
     *
     * @return int[]
     */
    public static function getAllGrabbedOrderIds(): array
    {
        $ids = Db::name('admin_order')->column('order_id');

        return array_values(array_unique(array_map('intval', $ids ?: [])));
    }

    /**
     * admin_order 中抢单方角色组解析为调度(identity=3)的订单 id。
     * 调度公海：仅排除「已被调度抢过」；线路抢单写入 admin_order 不算。调度列表须 backend_status=2（线路在子后台确认配车单后写入）。
     *
     * @return int[]
     */
    public static function getOrderIdsGrabbedByDispatchRole(): array
    {
        $rows = Db::name('admin_order')->field('order_id,group_id')->select();
        if (!$rows) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $gid = (int)($r['group_id'] ?? 0);
            if ($gid <= 0) {
                continue;
            }
            if (self::resolveEffectiveOrderRoleIdentity($gid) === 3) {
                $out[] = (int)$r['order_id'];
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * 订单列表是否按「绑定会员 + 装货区域」限制可见范围。
     * 注意：不能用 isSuperAdmin()（rules 含 *）判断，否则代理组若配了 * 会误看全站订单。
     */ 
    public static function orderListUsesBindRegionalScope(int $groupIdentity): bool
    {
        return in_array($groupIdentity, [1, 2, 3], true);
    }

    /**
     * 沿 pid 向上解析订单业务身份：子级组未填 identity 时继承父组（避免县级子组 identity 为空被当成总后台看全站）
     */
    public static function resolveEffectiveOrderRoleIdentity(int $groupId): int
    {
        $groupId = (int)$groupId;
        if ($groupId <= 0) {
            return 0;
        }
        $gid = $groupId;
        $seen = [];
        while ($gid > 0 && !isset($seen[$gid])) {
            $seen[$gid] = true;
            $row = Db::name('auth_group')->where('id', $gid)->field('id,pid,identity')->find();
            if (!$row) {
                break;
            }
            $id = (int)($row['identity'] ?? 0);
            if (in_array($id, [1, 2, 3], true)) {
                return $id;
            }
            $gid = (int)($row['pid'] ?? 0);
        }

        return (int)Db::name('auth_group')->where('id', $groupId)->value('identity');
    }

    /**
     * 订单范围用「代理组 id」：线路/调度同 getScopeAgentGroupId；代理若挂在子组且子组未标 identity=1，则取向上最近的 identity=1 组（绑定挂在该代理组上）
     */
    public static function getOrderScopeAgentGroupId(int $memberGroupId, int $effectiveIdentity): int
    {
        $memberGroupId = (int)$memberGroupId;
        $effectiveIdentity = (int)$effectiveIdentity;
        if ($effectiveIdentity === 2 || $effectiveIdentity === 3) {
            return self::getScopeAgentGroupId($memberGroupId, $effectiveIdentity);
        }
        if ($effectiveIdentity !== self::AUTH_GROUP_IDENTITY_AGENT) {
            return 0;
        }
        $gid = $memberGroupId;
        $seen = [];
        while ($gid > 0 && !isset($seen[$gid])) {
            $seen[$gid] = true;
            $row = Db::name('auth_group')->where('id', $gid)->field('id,pid,identity')->find();
            if (!$row) {
                break;
            }
            if ((int)($row['identity'] ?? 0) === self::AUTH_GROUP_IDENTITY_AGENT) {
                return (int)$row['id'];
            }
            $gid = (int)($row['pid'] ?? 0);
        }

        return $memberGroupId;
    } 

    /**
     * 当前管理员已绑定的会员 ID 列表
     */ 
    public static function getBoundUserIds($adminId): array
    {
        $adminId = (int)$adminId; 
        if ($adminId <= 0) {
            return [];
        }
        return Db::name('admin_user_bind')->where('admin_id', $adminId)->column('user_id');
    }

    /**
     * 已被任意管理员绑定的会员 ID（用于「添加绑定」时选人）
     */
    public static function getAllBoundUserIds(): array
    {
        return Db::name('admin_user_bind')->column('user_id');
    }

    /**
     * 「用户绑定」添加/编辑时可选择的目标管理员：
     *  - 总后台：超级管理组（fa_auth_group.id = 1）下的管理员；
     *  - 各加盟商的总管理员：fa_franchise.admin_id（已停用加盟商不展示）。
     * 不再把加盟商的线路/调度/财务等子账号列进去。
     *
     * @param int $keepAdminId 编辑时必须保留的当前绑定管理员（避免下拉里没有原值时被误改绑）
     * @return array<int,string> [admin_id => 下拉显示名]
     */
    public static function getBindTargetAdminOptions(int $keepAdminId = 0): array
    {
        $options = [];

        // 1) 总后台：超级管理组下的管理员
        $hqAdminIds = Db::name('auth_group_access')
            ->where('group_id', self::SUPER_ADMIN_AUTH_GROUP_ID)
            ->column('uid');
        $hqAdminIds = array_values(array_unique(array_map('intval', $hqAdminIds ?: [])));
        if ($hqAdminIds !== []) {
            $hqAdmins = Db::name('admin')
                ->where('id', 'in', $hqAdminIds)
                ->where('status', 'normal')
                ->field('id,username,nickname')
                ->select();
            foreach ($hqAdmins as $admin) {
                $options[(int)$admin['id']] = '总后台 - ' . self::adminDisplayName($admin);
            }
        }

        // 2) 各加盟商（一级/二级）的总管理员
        $franchises = Db::name('franchise')
            ->where('status', '<>', 'disabled')
            ->order('level', 'asc')
            ->order('id', 'asc')
            ->field('id,name,level,admin_id')
            ->select();
        foreach ($franchises as $fr) {
            $adminId = (int)($fr['admin_id'] ?? 0);
            if ($adminId <= 0 || isset($options[$adminId])) {
                continue;
            }
            $admin = Db::name('admin')
                ->where('id', $adminId)
                ->where('status', 'normal')
                ->field('id,username,nickname')
                ->find();
            if (!$admin) {
                continue;
            }
            $options[$adminId] = ((int)($fr['level'] ?? 1) === 2 ? '二级加盟商：' : '一级加盟商：')
                . $fr['name'] . '（总管理员 ' . self::adminDisplayName($admin) . '）';
        }

        // 3) 兜底：编辑页原绑定不在候选范围内时仍保留该选项，避免保存时被悄悄改绑
        if ($keepAdminId > 0 && !isset($options[$keepAdminId])) {
            $admin = Db::name('admin')
                ->where('id', $keepAdminId)
                ->field('id,username,nickname')
                ->find();
            if ($admin) {
                $options[$keepAdminId] = '当前绑定 - ' . self::adminDisplayName($admin);
            }
        }

        return $options;
    }

    /**
     * 管理员在下拉里的显示名：昵称优先，其次登录名
     */
    protected static function adminDisplayName(array $admin): string
    {
        $nickname = trim((string)($admin['nickname'] ?? ''));
        if ($nickname !== '') {
            return $nickname;
        }
        $username = trim((string)($admin['username'] ?? ''));

        return $username !== '' ? $username : ('#' . (int)($admin['id'] ?? 0));
    }

    public static function canManageMember($auth, $userId): bool
    {
        if ($auth->isSuperAdmin()) {
            return true;
        }
        $userId = (int)$userId;
        if ($userId <= 0) {
            return false;
        }
        $ids = self::getBoundUserIds($auth->id);
        return in_array($userId, array_map('intval', $ids), true);
    }

    /**
     * 线路、调度：沿当前角色组 pid 向上找到最近的代理分组（identity=1），合并该组下所有管理员在 admin_user_bind 中绑定的会员 user_id。
     * 未找到代理组则无可见绑定会员，返回 []。
     * 非线路/调度返回 null，表示不按「代理绑定会员」限制订单范围。
     *
     * @return int[]|null
     */
    public static function getBoundUserIdsForLineDispatchRole(int $groupId, int $groupIdentity, int $adminId = 0): ?array
    {
        if (!in_array($groupIdentity, [2, 3], true)) {
            return null;
        }
        $ids = [];
        // 同时包含“该账号自己绑定”的会员（如总部线路/调度绑定到自己）
        if ($adminId > 0) {
            foreach (Db::name('admin_user_bind')->where('admin_id', $adminId)->column('user_id') as $uid) {
                $ids[] = (int)$uid;
            }
        }
        $gid = $groupId;
        $seen = [];
        while ($gid > 0 && !isset($seen[$gid])) {
            $seen[$gid] = true;
            $row = Db::name('auth_group')->where('id', $gid)->field('id,pid,identity')->find();
            if (!$row) {
                break;
            }
            if ((int)($row['identity'] ?? 0) === self::AUTH_GROUP_IDENTITY_AGENT) {
                $agentAdminIds = Db::name('auth_group_access')->where('group_id', (int)$row['id'])->column('uid');
                $agentAdminIds = array_values(array_unique(array_map('intval', $agentAdminIds ?: [])));
                if ($agentAdminIds !== []) {
                    foreach (Db::name('admin_user_bind')->where('admin_id', 'in', $agentAdminIds)->column('user_id') as $uid) {
                        $ids[] = (int)$uid;
                    }
                }
                break;
            }
            $gid = (int)($row['pid'] ?? 0);
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * 指定代理角色组下，所有管理员在「用户绑定」中的会员 user_id（去重）
     */
    public static function getBoundUserIdsForAgentGroup(int $agentGroupId): array
    {
        $agentAdminIds = Db::name('auth_group_access')->where('group_id', $agentGroupId)->column('uid');
        $agentAdminIds = array_values(array_unique(array_map('intval', $agentAdminIds ?: [])));
        if ($agentAdminIds === []) {
            return [];
        }
        $uids = Db::name('admin_user_bind')->where('admin_id', 'in', $agentAdminIds)->column('user_id');

        return array_values(array_unique(array_map('intval', $uids ?: [])));
    }

    /**
     * 解析 admin.city JSON：1 个 id=省级，2 个=市级，3 个=县级/区级；返回按顺序对应的 area.name，非法则 null
     *
     * @return string[]|null
     */
    public static function getRegionalAreaNamesFromAdminCityField(?string $cityJson): ?array
    {
        if ($cityJson === null || trim((string)$cityJson) === '') {
            return null;
        }
        $arr = json_decode($cityJson, true);
        if (!is_array($arr) || $arr === []) {
            return null;
        }
        $ids = array_values(array_filter(array_map('intval', $arr)));
        $n = count($ids);
        if ($n < 1 || $n > 3) {
            return null;
        }
        $names = [];
        foreach ($ids as $id) {
            $name = Db::name('area')->where('id', $id)->value('name');
            if ($name === null || $name === '') {
                return null;
            }
            $names[] = $name;
        }

        return $names;
    }

    /**
     * fa_auth_group.city：「省/市」= 市代理（2 段），「省/市/区」= 区或县代理（3 段）；可选单段省代。
     *
     * @return string[]|null
     */
    public static function parseAuthGroupSlashCityNames(?string $raw): ?array
    {
        if ($raw === null || trim((string)$raw) === '') {
            return null;
        }
        $parts = preg_split('#[/\\\\]+#u', (string)$raw, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts) || $parts === []) {
            return null;
        }
        $parts = array_values(array_filter(array_map('trim', $parts), static function ($s) {
            return $s !== '';
        }));
        if ($parts === []) {
            return null;
        }
        if (count($parts) > 3) {
            $parts = array_slice($parts, 0, 3);
        }

        return $parts;
    }

    /**
     * 区县级（省市区 3 段）时，装货地址常省略省名，地理匹配用市+区两段。
     *
     * @param string[] $names parseAuthGroupSlashCityNames 等与「段数=代理层级」一致的名称序列
     * @return string[]
     */
    public static function regionalGeoNamesForOrderScope(array $names): array
    {
        if (count($names) === 3) {
            return array_slice($names, -2);
        }

        return $names;
    }

    /**
     * 代理(1)：本组管理员；线路/调度(2/3)：向上最近的代理组下的管理员
     *
     * @return int[]
     */
    public static function getRegionalSourceAdminIds(int $groupId, int $groupIdentity): array
    {
        if ($groupIdentity === self::AUTH_GROUP_IDENTITY_AGENT) {
            $uids = Db::name('auth_group_access')->where('group_id', $groupId)->column('uid');

            return array_values(array_unique(array_map('intval', $uids ?: [])));
        }
        if (!in_array($groupIdentity, [2, 3], true)) {
            return [];
        }
        $gid = $groupId;
        $seen = [];
        while ($gid > 0 && !isset($seen[$gid])) {
            $seen[$gid] = true;
            $row = Db::name('auth_group')->where('id', $gid)->field('id,pid,identity')->find();
            if (!$row) {
                break;
            }
            if ((int)($row['identity'] ?? 0) === self::AUTH_GROUP_IDENTITY_AGENT) {
                $uids = Db::name('auth_group_access')->where('group_id', (int)$row['id'])->column('uid');

                return array_values(array_unique(array_map('intval', $uids ?: [])));
            }
            $gid = (int)($row['pid'] ?? 0);
        }

        return [];
    }

    /**
     * 区域以 fa_auth_group.city 为准，不再读 admin.city；本方法仍返回组内管理员 id，供其它逻辑使用（订单范围 SQL 已改用角色组 city）。
     *
     * @return int[]
     */
    public static function getRegionalSourceAdminIdsForOrderScope(int $groupId, int $groupIdentity, int $viewerAdminId): array
    {
        if ($groupIdentity === self::AUTH_GROUP_IDENTITY_AGENT) {
            $viewerAdminId = (int)$viewerAdminId;
            if ($viewerAdminId <= 0) {
                return [];
            }
            $memberIds = Db::name('auth_group_access')->where('group_id', $groupId)->column('uid');
            $memberIds = array_values(array_unique(array_map('intval', $memberIds ?: [])));
            if (!in_array($viewerAdminId, $memberIds, true)) {
                return [];
            }

            return [$viewerAdminId];
        }

        return self::getRegionalSourceAdminIds($groupId, $groupIdentity);
    }

    /**
     * 当前视角所属的「代理角色组」id：代理为本组 id；线路/调度为向上最近的 identity=1 组 id
     */
    public static function getScopeAgentGroupId(int $groupId, int $groupIdentity): int
    {
        if ($groupIdentity === self::AUTH_GROUP_IDENTITY_AGENT) {
            return $groupId;
        }
        if (!in_array($groupIdentity, [2, 3], true)) {
            return 0;
        }
        $gid = $groupId;
        $seen = [];
        while ($gid > 0 && !isset($seen[$gid])) {
            $seen[$gid] = true;
            $row = Db::name('auth_group')->where('id', $gid)->field('id,pid,identity')->find();
            if (!$row) {
                break;
            }
            if ((int)($row['identity'] ?? 0) === self::AUTH_GROUP_IDENTITY_AGENT) {
                return (int)$row['id'];
            }
            $gid = (int)($row['pid'] ?? 0);
        }

        return 0;
    }

    /**
     * 从 auth_group_access 解析订单/抢单/绑定范围所用的「业务角色组」。
     * 多组并存时，避免 ORM 随机取到 group_id=1 而被误判为总后台全量数据范围；
     * 优先选择 fa_auth_group.identity 为 1（代理）/2（线路）/3（调度）的组。
     */
    public static function getPrimaryBusinessGroupIdForAdmin(?int $adminId): int
    {
        $adminId = (int)$adminId;
        if ($adminId <= 0) {
            return 0;
        }
        $gids = Db::name('auth_group_access')->where('uid', $adminId)->column('group_id');
        $gids = array_values(array_unique(array_map('intval', $gids ?: [])));
        if ($gids === []) {
            return 0;
        }
        // 与 Dashboard、订单统计等处一致：group_id=1 视为总管理组。若与其它业务组并存，
        // 必须固定用该组解析订单身份，否则会优先命中 identity=1/2/3 的子组，总后台只能看到绑定/区域订单。
        if (in_array(1, $gids, true)) {
            return 1;
        } 
        $bestGid = 0;
        $bestPri = 99;
        foreach ($gids as $gid) {
            if ($gid <= 0) {
                continue; 
            }
            $identity = (int)Db::name('auth_group')->where('id', $gid)->value('identity');
            if (in_array($identity, [1, 2, 3], true) && $identity < $bestPri) {
                $bestPri = $identity;
                $bestGid = $gid;
            } 
        } 
        if ($bestGid > 0) {
            return $bestGid;
        }
        $nonOne = array_values(array_filter($gids, static function ($g) {
            return (int)$g !== 1;
        }));
        if ($nonOne !== []) {
            return (int)$nonOne[0];
        }

        return (int)$gids[0];
    }

    /**
     * 代理根组 id 及 fa_auth_group 树下全部子孙组 id（含自身），用于判断「绑定是否在本代理体系内」
     *
     * @return int[]
     */
    public static function getAuthGroupIdsInAgentSubtree(int $agentRootGroupId): array
    {
        $agentRootGroupId = (int)$agentRootGroupId;
        if ($agentRootGroupId <= 0) {
            return [];
        }
        $rows = Db::name('auth_group')->field('id,pid')->select();
        $childrenMap = [];
        foreach ($rows as $r) {
            $pid = (int)($r['pid'] ?? 0);
            $childrenMap[$pid][] = (int)$r['id'];
        }
        $ids = [$agentRootGroupId];
        $stack = $childrenMap[$agentRootGroupId] ?? [];
        while ($stack !== []) {
            $id = (int)array_pop($stack);
            $ids[] = $id;
            if (!empty($childrenMap[$id])) {
                foreach ($childrenMap[$id] as $cid) {
                    $stack[] = $cid;
                }
            }
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * 按装货区域可见时：会员未绑定任何人，或绑定管理员所属组落在当前代理子树内。
     * 已被其它代理体系绑定的会员，即使装货在本区域也不可见。
     */
    public static function orderUserIsUnboundOrBoundToScopeAgentGroup(int $userId, int $scopeAgentGroupId): bool
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return false;
        }
        $row = Db::name('admin_user_bind')->where('user_id', $userId)->find();
        if (!$row) {
            return true;
        }
        if ($scopeAgentGroupId <= 0) {
            return false;
        }
        $treeIds = self::getAuthGroupIdsInAgentSubtree($scopeAgentGroupId);
        if ($treeIds === []) {
            return false;
        }
        $adminId = (int)($row['admin_id'] ?? 0);

        return (bool)Db::name('auth_group_access')
            ->where('uid', $adminId)
            ->where('group_id', 'in', $treeIds)
            ->value('uid');
    }

    /**
     * 仅装货地址：是否同时包含给定行政区名称（省 / 市+省 / 县+市+省）。
     * 与 regionalExistsOneSideSql 一致：每个地名在 detailed_address 或 address 任一命中即可，避免列表能搜到、抢单 PHP 校验失败。
     */
    public static function orderRowMatchesRegionalNames(array $orderRow, array $names): bool
    {
        if ($names === [] || empty($orderRow['loading'])) {
            return false;
        }
        $addr = Db::name('user_address')->where('id', (int)$orderRow['loading'])->field('detailed_address,address')->find();
        if (!$addr) {
            return false;
        }
        $det = trim((string)($addr['detailed_address'] ?? ''));
        $adr = trim((string)($addr['address'] ?? ''));
        foreach ($names as $nm) {
            if ($nm === '') {
                return false;
            }
            $inDet = $det !== '' && mb_strpos($det, $nm) !== false;
            $inAdr = $adr !== '' && mb_strpos($adr, $nm) !== false;
            if (!$inDet && !$inAdr) {
                return false;
            }
        }

        return true;
    }

    public static function orderMatchesAnyAgentRegionalScopes(array $orderRow, array $agentAdminIds, int $scopeAgentGroupId): bool
    {
        unset($agentAdminIds);
        $uid = (int)($orderRow['userid'] ?? 0);
        if ($uid <= 0 || $scopeAgentGroupId <= 0) {
            return false;
        }
        $groupCity = Db::name('auth_group')->where('id', $scopeAgentGroupId)->value('city');
        $names = self::parseAuthGroupSlashCityNames($groupCity);
        if ($names === null) {
            return false;
        }
        $geoNames = self::regionalGeoNamesForOrderScope($names);
        if (!self::orderRowMatchesRegionalNames($orderRow, $geoNames)) {
            return false;
        }

        return self::orderUserIsUnboundOrBoundToScopeAgentGroup($uid, $scopeAgentGroupId);
    }

    protected static function dbPrefix(): string
    {
        return (string)Config::get('database.prefix');
    }

    /**
     * ThinkPHP 在 buildparams 等场景会使用命名占位符；同一查询里再传数字下标的 whereRaw 绑定会与 PDO 混用触发 HY093。
     * 将片段 SQL 中的 ? 依次换成 :ordScope_vN，并返回字符串键的 bind 数组。
     *
     * @param  array<int, mixed> $positionalValues
     * @return array{0:string,1:array<string,mixed>}
     */
    protected static function convertPositionalSqlToNamedBinds(string $sql, array $positionalValues, string $prefix, int &$seq): array
    {
        $named = [];
        foreach ($positionalValues as $val) {
            $seq++;
            $name = $prefix . '_v' . $seq;
            $sql = preg_replace('/\?/', ':' . $name, $sql, 1);
            $named[$name] = $val;
        }

        return [$sql, $named];
    }

    /**
     * @return array{sql:string,bind:array}
     */
    protected static function regionalExistsOneSideSql(array $names, string $orderSideExpr): array
    {
        $ut = '`' . self::dbPrefix() . 'user_address`';
        $bind = [];
        $conds = [];
        foreach ($names as $nm) {
            $conds[] = '(la.detailed_address LIKE ? OR IFNULL(la.address,\'\') LIKE ?)';
            $bind[] = '%' . $nm . '%';
            $bind[] = '%' . $nm . '%';
        }
        $condSql = implode(' AND ', $conds);
        $sql = "EXISTS (SELECT 1 FROM {$ut} la WHERE la.id = {$orderSideExpr} AND {$condSql})";

        return ['sql' => $sql, 'bind' => $bind];
    }

    /**
     * 仅装货地址（loading）命中区域；区域来自 fa_auth_group.city（省/市 或 省/市/区）。
     * 区/县（3 段）地理匹配仍用市+区两段；会员须未绑定或绑定落在当前代理子树内（已绑至外代理的会员即使装货在本区也不走区域，仅可走本组绑定 OR）。
     *
     * @return array{sql:string,bind:array}|null
     */
    protected static function buildRegionalSqlForAuthGroupCityField(
        ?string $authGroupCitySlash,
        string $loadingExpr,
        string $useridExpr,
        int $scopeAgentGroupId
    ): ?array {
        $names = self::parseAuthGroupSlashCityNames($authGroupCitySlash);
        if ($names === null) {
            return null;
        }
        $geoNames = self::regionalGeoNamesForOrderScope($names);
        $load = self::regionalExistsOneSideSql($geoNames, $loadingExpr);
        $p = self::dbPrefix();
        $bt = '`' . $p . 'admin_user_bind`';
        $at = '`' . $p . 'auth_group_access`';
        $onlyUnboundSql = "( NOT EXISTS (SELECT 1 FROM {$bt} bub WHERE bub.user_id = {$useridExpr}) )";
        if ($scopeAgentGroupId <= 0) {
            $sql = '(' . $load['sql'] . ' AND ' . $onlyUnboundSql . ')';

            return ['sql' => $sql, 'bind' => $load['bind']];
        }
        $treeIds = self::getAuthGroupIdsInAgentSubtree($scopeAgentGroupId);
        if ($treeIds === []) {
            $sql = '(' . $load['sql'] . ' AND ' . $onlyUnboundSql . ')';

            return ['sql' => $sql, 'bind' => $load['bind']];
        }
        $placeholders = implode(',', array_fill(0, count($treeIds), '?'));
        $scopeSql = "( NOT EXISTS (SELECT 1 FROM {$bt} bub WHERE bub.user_id = {$useridExpr}) OR EXISTS (SELECT 1 FROM {$bt} b2 INNER JOIN {$at} aga ON aga.uid = b2.admin_id WHERE b2.user_id = {$useridExpr} AND aga.group_id IN ({$placeholders})) )";
        $sql = '(' . $load['sql'] . ' AND ' . $scopeSql . ')';
        $bind = array_merge($load['bind'], $treeIds);

        return ['sql' => $sql, 'bind' => $bind];
    }

    /**
     * 主订单 Model 查询：无表别名，表名 fa_order
     */
    public static function applyBoundOrRegionalScopeToOrderModelQuery(
        $query,
        array $boundUserIds,
        array $regionalSourceAdminIds,
        int $scopeAgentGroupId
    ): void {
        $p = self::dbPrefix();
        $ot = '`' . $p . 'order`';
        $loadingExpr = $ot . '.`loading`';
        $useridExpr = $ot . '.`userid`';
        self::applyBoundOrRegionalScopeInner($query, $boundUserIds, $regionalSourceAdminIds, $loadingExpr, $useridExpr, $scopeAgentGroupId);
    }

    /**
     * Db::name('order')->alias('o') 等带别名的查询
     */
    public static function applyBoundOrRegionalScopeToAliasedOrderQuery(
        $query,
        string $alias,
        array $boundUserIds,
        array $regionalSourceAdminIds,
        int $scopeAgentGroupId
    ): void {
        $a = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $alias) ? $alias : 'o';
        $loadingExpr = $a . '.loading';
        $useridExpr = $a . '.userid';
        self::applyBoundOrRegionalScopeInner($query, $boundUserIds, $regionalSourceAdminIds, $loadingExpr, $useridExpr, $scopeAgentGroupId);
    }

    /**
     * @param \think\db\Query|\think\Model $query
     */
    protected static function applyBoundOrRegionalScopeInner(
        $query,
        array $boundUserIds,
        array $regionalSourceAdminIds,
        string $loadingExpr,
        string $useridExpr,
        int $scopeAgentGroupId
    ): void {
        unset($regionalSourceAdminIds);
        $regionalChunks = [];
        if ($scopeAgentGroupId > 0) {
            $groupCitySlash = Db::name('auth_group')->where('id', $scopeAgentGroupId)->value('city');
            $chunk = self::buildRegionalSqlForAuthGroupCityField($groupCitySlash, $loadingExpr, $useridExpr, $scopeAgentGroupId);
            if ($chunk !== null) {
                $regionalChunks[] = $chunk;
            }
        }
        $hasBound = $boundUserIds !== [];
        $hasRegional = $regionalChunks !== [];
        if (!$hasBound && !$hasRegional) {
            $query->where('id', -1);

            return;
        }
        $parts = [];
        $bindNamed = [];
        $seq = 0;
        $bindPrefix = 'ordScope';
        if ($hasBound) {
            $inNames = [];
            foreach ($boundUserIds as $uid) {
                $seq++;
                $name = $bindPrefix . '_v' . $seq;
                $inNames[] = ':' . $name;
                $bindNamed[$name] = $uid;
            }
            $parts[] = $useridExpr . ' IN (' . implode(',', $inNames) . ')';
        }
        foreach ($regionalChunks as $chunk) {
            [$sql, $named] = self::convertPositionalSqlToNamedBinds($chunk['sql'], $chunk['bind'], $bindPrefix, $seq);
            $parts[] = $sql;
            $bindNamed = array_merge($bindNamed, $named);
        }
        $query->whereRaw('(' . implode(' OR ', $parts) . ')', $bindNamed);
    }

    /**
     * 从代理组向下遍历子组，收集 identity 为线路(2)、调度(3) 的组下所有管理员 uid（去重）
     * 用于代理查看下级线路/调度在 admin_order 中的抢单数据
     *
     * @return int[]
     */
    public static function getLineDispatchStaffAdminIdsUnderAgent(int $agentGroupId): array
    {
        $rows = Db::name('auth_group')->field('id,pid')->select();
        $childrenMap = [];
        foreach ($rows as $r) {
            $pid = (int)($r['pid'] ?? 0);
            $childrenMap[$pid][] = (int)$r['id'];
        }
        $desc = [];
        $stack = $childrenMap[$agentGroupId] ?? [];
        while ($stack !== []) {
            $id = (int)array_pop($stack);
            if (isset($desc[$id])) {
                continue;
            }
            $desc[$id] = true;
            if (!empty($childrenMap[$id])) {
                foreach ($childrenMap[$id] as $cid) {
                    $stack[] = $cid;
                }
            }
        }
        $descIds = array_keys($desc);
        if ($descIds === []) {
            return [];
        }
        $lineDispatchGroupIds = Db::name('auth_group')
            ->where('id', 'in', $descIds)
            ->where('identity', 'in', [2, 3])
            ->column('id');
        if ($lineDispatchGroupIds === []) {
            return [];
        }
        $uids = Db::name('auth_group_access')->where('group_id', 'in', $lineDispatchGroupIds)->column('uid');

        return array_values(array_unique(array_map('intval', $uids ?: [])));
    }
}
