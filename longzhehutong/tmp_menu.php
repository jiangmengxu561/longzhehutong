<?php
// 检查现有的 finance 菜单
try {
    $db = new PDO('mysql:host=127.0.0.1;dbname=longzhehutong;charset=utf8', 'root', 'root');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $rows = $db->query("SELECT id,pid,name,title,ismenu FROM fa_auth_rule WHERE name LIKE '%finance%' ORDER BY id");
    echo "=== Existing finance menu nodes ===\n";
    foreach ($rows as $r) {
        echo $r['id'] . '|pid=' . $r['pid'] . '|' . $r['name'] . '|' . $r['title'] . '|menu=' . $r['ismenu'] . "\n";
    }

    // 查找 finance 父节点
    $stmt = $db->query("SELECT id,pid,name,title,ismenu FROM fa_auth_rule WHERE name='finance' OR name LIKE 'finance/%' ORDER BY id LIMIT 20");
    echo "\n=== finance nodes ===\n";
    foreach ($stmt as $r) {
        echo $r['id'] . '|pid=' . $r['pid'] . '|' . $r['name'] . '|' . $r['title'] . '|menu=' . $r['ismenu'] . "\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
