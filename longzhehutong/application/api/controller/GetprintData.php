<?php
/**
 * 获取打印数据接口（GBK编码）
 * 接口路径：Placeorder/getPrintData
 * 请求方式：POST
 * 参数：
 *   - order_id: 订单ID
 *   - print_type: 打印类型（waybill=运单, receipt=签收单）
 */

// 假设你的项目有统一的入口文件和数据库连接
// 这里提供一个独立的示例，你需要根据你的项目结构调整

header('Content-Type: application/json;charset=utf-8');

// 获取请求参数
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$print_type = isset($_POST['print_type']) ? trim($_POST['print_type']) : 'waybill';

// 参数验证
if (empty($order_id)) {
    echo json_encode([
        'code' => 0,
        'msg' => '订单ID不能为空',
        'data' => null
    ]);
    exit;
}

// TODO: 这里需要根据你的项目结构连接数据库
// 示例：假设你已经有了数据库连接对象 $db
// $order = $db->query("SELECT * FROM orders WHERE orderid = {$order_id}")->fetch_assoc();

// 为了演示，这里使用模拟数据（你需要替换为真实的数据库查询）
// 假设订单数据结构如下：
$order = [
    'orderid' => $order_id,
    'createtime' => '2024-01-01 12:00:00',
    'pay_status' => 2,
    'pay_price' => '100.00',
    'goods_name' => '货物名称',
    'weight' => '10',
    'volume' => '1.5',
    'num' => '1',
    'loading_address' => [
        'name' => '张三',
        'mobile' => '13800138000',
        'address' => '北京市朝阳区xxx街道xxx号',
        'company' => '发货公司'
    ],
    'unload_address' => [
        'name' => '李四',
        'mobile' => '13900139000',
        'address' => '上海市浦东新区xxx路xxx号',
        'company' => '收货公司'
    ]
];

// 生成打印内容
$printContent = '';
if ($print_type === 'waybill') {
    $printContent = generateWaybillContent($order);
} else if ($print_type === 'receipt') {
    $printContent = generateReceiptContent($order);
} else {
    echo json_encode([
        'code' => 0,
        'msg' => '打印类型错误',
        'data' => null
    ]);
    exit;
}

// 将UTF-8字符串转换为GBK编码
$gbkContent = mb_convert_encoding($printContent, 'GBK', 'UTF-8');

// 将GBK字节流转换为base64
$base64Data = base64_encode($gbkContent);

// 返回结果
echo json_encode([
    'code' => 1,
    'msg' => '获取成功',
    'data' => [
        'print_data' => $base64Data
    ]
]);


?>
