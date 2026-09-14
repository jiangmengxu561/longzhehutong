<?php
$dsn = 'mysql:host=127.0.0.1;dbname=longzhehutong;charset=utf8mb4';
$user = 'longzhehutong';
$pass = 'longzhehutong';
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $prefix = 'fa_';
    $authRule = $prefix . 'auth_rule';

    $stmt = $pdo->prepare("SELECT id FROM {$authRule} WHERE name = ? LIMIT 1");
    $stmt->execute(['finance']);
    $financeId = (int)$stmt->fetchColumn();
    if ($financeId <= 0) {
        $stmt = $pdo->prepare("INSERT INTO {$authRule} (pid,name,title,icon,ismenu,status,weigh,createtime,updatetime) VALUES (0,'finance','财务管理','fa fa-money',1,'normal',0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP())");
        $stmt->execute();
        $financeId = (int)$pdo->lastInsertId();
    }

    $nodes = [
        ['finance/cost_ledger', '订单费用台账', 'fa fa-list-alt'],
        ['finance/cost_ledger_dispatch', '调度费用汇总', 'fa fa-sitemap'],
        ['finance/cost_ledger_detail', '订单费用明细', 'fa fa-file-text-o'],
        ['finance/reserve_fund', '调度备用金', 'fa fa-cny'],
        ['finance/reserve_fund_log', '备用金流水', 'fa fa-list'],
    ];

    $check = $pdo->prepare("SELECT id FROM {$authRule} WHERE name = ? LIMIT 1");
    $insert = $pdo->prepare("INSERT INTO {$authRule} (pid,name,title,icon,ismenu,status,weigh,createtime,updatetime) VALUES (?,?,?,?,1,'normal',0,UNIX_TIMESTAMP(),UNIX_TIMESTAMP())");
    $added = 0;
    foreach ($nodes as [$name, $title, $icon]) {
        $check->execute([$name]);
        if (!$check->fetchColumn()) {
            $insert->execute([$financeId, $name, $title, $icon]);
            $added++;
        }
    }

    echo "installed={$added}\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
