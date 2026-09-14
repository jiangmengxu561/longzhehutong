<?php
/**
 * 财务记账「修改/删除审核」一键安装脚本
 *
 * 用法（二选一）：
 * 1. 命令行（推荐）：
 *    cd longzhehutong
 *    php install_bookkeeping_audit.php
 * 2. 浏览器：将本文件复制到 longzhehutong/public 目录后访问
 *    http://你的域名/install_bookkeeping_audit.php
 *
 * 安装完成后请立即删除本文件！
 */

// 浏览器访问时仅允许本机，命令行不受影响
if (PHP_SAPI !== 'cli') {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    if (!in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)) {
        header('Content-Type: text/plain; charset=utf-8');
        exit("仅允许本机访问。\n请使用命令行执行：php install_bookkeeping_audit.php");
    }
}

// 引导框架（仅加载基础类与环境变量，不执行路由）
define('APP_PATH', __DIR__ . '/application/');
require __DIR__ . '/thinkphp/base.php';

// 加载应用数据库配置（与后台使用完全相同的连接信息）
\think\Config::set(include APP_PATH . 'database.php', 'database');
$prefix = (string)\think\Config::get('database.prefix');

$messages = [];
$errors = [];

try {
    // ========== 1. 创建审核数据表 ==========
    $tableName = $prefix . 'bookkeeping_audit';
    $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bookkeeping_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '关联记账ID',
  `type` varchar(10) NOT NULL DEFAULT '' COMMENT '操作类型:edit=修改,del=删除',
  `old_data` text COMMENT '修改前数据(JSON)',
  `new_data` text COMMENT '修改后数据(JSON)',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '提交人ID',
  `admin_name` varchar(64) NOT NULL DEFAULT '' COMMENT '提交人名称',
  `audit_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '审核状态:0=待审核,1=已通过,2=已拒绝',
  `audit_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核人ID',
  `audit_remark` varchar(500) NOT NULL DEFAULT '' COMMENT '审核备注',
  `audit_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '审核时间',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_bookkeeping_id` (`bookkeeping_id`),
  KEY `idx_audit_status` (`audit_status`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='财务记账修改/删除审核表';";
    \think\Db::execute($sql);
    $messages[] = "数据表 {$tableName} 创建成功（已存在则跳过）";

    // ========== 2. 创建后台菜单/权限节点 ==========
    $authRuleTable = $prefix . 'auth_rule';
    // 获取 auth_rule 实际存在的字段，避免不同 FastAdmin 版本差异导致插入失败
    $columns = [];
    try {
        $colRows = \think\Db::query("SHOW COLUMNS FROM `{$authRuleTable}`");
        foreach ($colRows as $col) {
            $columns[] = $col['Field'];
        }
    } catch (\Exception $e) {
        $errors[] = "读取 fa_auth_rule 表结构失败: " . $e->getMessage();
        $errors[] = "请确认数据库前缀与 fastadmin 表名是否正确（当前前缀: {$prefix}）";
    }

    if ($columns) {
        // 查找父菜单「财务记账 bookkeeping」
        $parentId = \think\Db::name('auth_rule')
            ->where('name', 'bookkeeping')
            ->where('ismenu', 1)
            ->value('id');

        if (!$parentId) {
            $errors[] = "未找到父菜单 bookkeeping（name='bookkeeping' AND ismenu=1），无法挂载「记账审核」菜单。";
            $errors[] = "请先在后台 权限管理->菜单规则 中确认存在「财务记账」菜单，或把下方菜单的 pid 改为实际父菜单 id 后手动插入。";
        }

        $menuId = null;
        // 菜单定义：isMenu=true 为菜单，false 为权限节点
        $menus = [
            [
                'name'    => 'bookkeepingaudit',
                'title'   => '记账审核',
                'icon'    => 'fa fa-check-square-o',
                'ismenu'  => 1,
                'weigh'   => 0,
                'pid'     => $parentId,
            ],
            [
                'name'    => 'bookkeepingaudit/index',
                'title'   => '查看',
                'icon'    => 'fa fa-circle-o',
                'ismenu'  => 0,
                'weigh'   => 1,
                'pid'     => null,
            ],
            [
                'name'    => 'bookkeepingaudit/edit',
                'title'   => '审核',
                'icon'    => 'fa fa-pencil',
                'ismenu'  => 0,
                'weigh'   => 2,
                'pid'     => null,
            ],
        ];

        foreach ($menus as $menu) {
            $existsId = \think\Db::name('auth_rule')->where('name', $menu['name'])->value('id');
            if ($existsId) {
                $messages[] = "权限节点 {$menu['name']} 已存在，跳过（id={$existsId}）";
                if ($menu['ismenu']) {
                    $menuId = $existsId;
                }
                continue;
            }
            $data = [
                'type'       => 'file',
                'pid'        => (int)$menu['pid'],
                'name'       => $menu['name'],
                'title'      => $menu['title'],
                'icon'       => $menu['icon'],
                'condition'  => '',
                'remark'     => '',
                'ismenu'     => $menu['ismenu'],
                'menutype'   => null,
                'extend'     => '',
                'py'         => '',
                'pinyin'     => '',
                'createtime' => time(),
                'updatetime' => time(),
                'weigh'      => $menu['weigh'],
                'status'     => 'normal',
            ];
            // 只保留表中真实存在的字段
            $data = array_intersect_key($data, array_flip($columns));
            $newId = \think\Db::name('auth_rule')->insertGetId($data);
            if ($menu['ismenu']) {
                $menuId = $newId;
                $messages[] = "菜单「{$menu['title']}」创建成功（id={$newId}）";
            } else {
                // 子节点挂在菜单下
                if ($menuId) {
                    \think\Db::name('auth_rule')->where('id', $newId)->update(['pid' => $menuId]);
                }
                $messages[] = "权限节点 {$menu['name']} 创建成功（id={$newId}）";
            }
        }
    }

    // ========== 3. 清除菜单缓存 ==========
    try {
        \think\Cache::rm('__menu__');
        $messages[] = "菜单缓存已清除";
    } catch (\Exception $e) {
        // 缓存不存在也不影响
        $messages[] = "菜单缓存清除完成";
    }

} catch (\Exception $e) {
    $errors[] = "执行失败: " . $e->getMessage();
}

// ========== 输出结果 ==========
if (PHP_SAPI === 'cli') {
    foreach ($messages as $msg) {
        echo "[OK] " . $msg . PHP_EOL;
    }
    foreach ($errors as $msg) {
        echo "[FAIL] " . $msg . PHP_EOL;
    }
    if (!$errors) {
        echo PHP_EOL . "安装成功！请刷新后台页面查看「财务记账」下的「记账审核」菜单。" . PHP_EOL;
        echo "（如菜单未显示，请删除 runtime/cache 目录下的缓存文件，并清除浏览器缓存）" . PHP_EOL;
    } else {
        echo PHP_EOL . "存在错误，请根据提示处理。" . PHP_EOL;
    }
} else {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html lang='zh-CN'><head><meta charset='utf-8'><title>记账审核安装结果</title></head><body style='font-family:Microsoft YaHei;margin:40px;'>";
    echo "<h3>财务记账「修改/删除审核」安装结果</h3>";
    echo "<div style='background:#f5f5f5;padding:15px;border-radius:4px;'>";
    foreach ($messages as $msg) {
        echo "<p style='color:#2e7d32;margin:4px 0;'>✔ " . htmlspecialchars($msg) . "</p>";
    }
    foreach ($errors as $msg) {
        echo "<p style='color:#c62828;margin:4px 0;'>✘ " . htmlspecialchars($msg) . "</p>";
    }
    if (!$errors) {
        echo "<p style='color:#2e7d32;margin-top:12px;'><b>安装成功！</b>请刷新后台页面查看「财务记账」下的「记账审核」菜单。若未显示请清除浏览器缓存。</p>";
    } else {
        echo "<p style='color:#c62828;margin-top:12px;'><b>存在错误</b>，请根据提示处理。</p>";
    }
    echo "<p style='margin-top:20px;color:#999;'>请立即删除本文件（install_bookkeeping_audit.php），避免安全风险！</p>";
    echo "</div></body></html>";
}
