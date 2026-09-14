<?php
/**
 * 安装「公司利润报表」菜单 + 权限节点
 *
 * 用法（选择其一）：
 * 1. 命令行：cd longzhehutong && php install_profit_menu.php
 * 2. 浏览器：将该文件复制到 longzhehutong/public 后访问
 *
 * 安装完成后请立即删除本文件！
 */

if (PHP_SAPI !== 'cli') {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    if (!in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)) {
        header('Content-Type: text/plain; charset=utf-8');
        exit("仅允许本机访问。\n请使用命令行执行：php install_profit_menu.php");
    }
}

define('APP_PATH', __DIR__ . '/application/');
require __DIR__ . '/thinkphp/base.php';

\think\Config::set(include APP_PATH . 'database.php', 'database');
$prefix = (string)\think\Config::get('database.prefix');

$messages = [];
$errors = [];

try {
    $authRuleTable = $prefix . 'auth_rule';
    $columns = [];
    $colRows = \think\Db::query("SHOW COLUMNS FROM `{$authRuleTable}`");
    foreach ($colRows as $col) {
        $columns[] = $col['Field'];
    }

    // 找到父菜单 finance（财务）
    $parentId = \think\Db::name('auth_rule')
        ->where('name', 'finance')
        ->where('ismenu', 1)
        ->value('id');

    if (!$parentId) {
        $errors[] = "未找到父菜单 finance（name='finance' AND ismenu=1），无法挂载「利润报表」菜单。";
        $errors[] = "请先在后台「权限管理-菜单规则」中确认存在「财务」菜单，或把下方菜单的 pid 改为实际父菜单 id 后手动插入。";
    }

    $menuId = null;
    $menus = [
        [
            'name'    => 'profit',
            'title'   => '公司利润报表',
            'icon'    => 'fa fa-bar-chart',
            'ismenu'  => 1,
            'weigh'   => 0,
            'pid'     => $parentId,
        ],
        [
            'name'    => 'profit/index',
            'title'   => '查看',
            'icon'    => 'fa fa-eye',
            'ismenu'  => 0,
            'weigh'   => 1,
            'pid'     => null,
        ],
        [
            'name'    => 'profit/export',
            'title'   => '导出',
            'icon'    => 'fa fa-download',
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
        $data = array_intersect_key($data, array_flip($columns));
        $newId = \think\Db::name('auth_rule')->insertGetId($data);
        if ($menu['ismenu']) {
            $menuId = $newId;
            $messages[] = "菜单「{$menu['title']}」创建成功（id={$newId}）";
        } else {
            if ($menuId) {
                \think\Db::name('auth_rule')->where('id', $newId)->update(['pid' => $menuId]);
                $messages[] = "权限节点 {$menu['name']} 创建成功，已挂到菜单（id={$newId}）";
            } else {
                \think\Db::name('auth_rule')->where('id', $newId)->update(['pid' => $parentId]);
                $messages[] = "权限节点 {$menu['name']} 创建成功，已挂到父菜单（id={$newId}）";
            }
        }
    }
} catch (\Throwable $e) {
    $errors[] = "安装失败：" . $e->getMessage();
}

echo "========== 安装结果 ==========\n";
foreach ($messages as $m) {
    echo "[OK] {$m}\n";
}
if ($errors) {
    foreach ($errors as $e) {
        echo "[WARN] {$e}\n";
    }
} else {
    echo "完成。请刷新后台「财务」菜单，确认出现「公司利润报表」；如为子账号，请为其分配 profit 相关权限。\n";
}
