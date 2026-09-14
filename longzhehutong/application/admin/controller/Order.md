# Order 控制器 API 文档

## 概述

`Order` 控制器是物流系统后台管理模块中的订单管理控制器，继承自 `Backend` 基类。该控制器负责处理订单的增删改查、抢单、路线规划、金额计算、打印、退款等核心业务功能。

**文件路径**: `application/admin/controller/Order.php`

**命名空间**: `app\admin\controller`

**继承类**: `app\common\controller\Backend`

---

## 类属性

### 公共属性

- `$noNeedLogin = ['*']` - 不需要登录验证的方法（所有方法）
- `$noNeedRight = ['*']` - 不需要权限验证的方法（所有方法）
- `$model` - Order 模型对象实例

---

## 初始化方法

### `_initialize()`

控制器初始化方法，在每次请求时自动调用。

**功能**:
- 初始化 Order 模型
- 向视图分配各种下拉列表数据（找车类型、发票、支付方式、配送方式等）

**分配的视图变量**:
- `findCarTypeList` - 找车类型列表
- `isinvoiceList` - 发票列表
- `payTypeList` - 支付方式列表
- `deliveryList` - 配送方式列表
- `isrequirementsList` - 需求列表
- `serviceList` - 服务列表
- `controlList` - 控制列表
- `textMessageList` - 短信列表
- `payStatusList` - 支付状态列表
- `logisticsStatusList` - 物流状态列表

---

## 公共方法

### 1. `index()` - 订单列表

订单列表查询方法，支持分页、搜索、权限过滤和统计。

**请求方式**: GET/POST (支持 AJAX)

**功能特性**:
- 支持按用户名、手机号搜索（需要 JOIN user 表）
- 根据管理员角色组过滤订单
- 超级管理员可查看所有订单（排除已取消订单）
- 非超级管理员只能查看未抢单且符合权限的订单
- 计算订单利润
- 超级管理员可查看统计数据（总收入、总支出、利润、总吨数、总方数、总单量）

**权限控制**:
- 超级管理员（group_id = 1）：查看所有订单（pay_status <> 5）
- 路线/规划身份（identity = 2）：查看 backend_status = 1 的订单
- 调度身份（identity = 3）：查看 backend_status = 2 的订单

**返回数据**:
```json
{
    "total": 100,
    "rows": [...],
    "statistics": {
        "total_income": 10000.00,
        "total_expense": 8000.00,
        "total_profit": 2000.00,
        "total_weight": 500.00,
        "total_volume": 300.00,
        "total_orders": 50
    }
}
```

**特殊处理**:
- 自动格式化装货/卸货地址信息
- 计算订单超时状态（15分钟）
- 关联查询货物类型、包装、车型等关联数据

---

### 2. `order_grabbing()` - 抢单

订单抢单功能，允许非超级管理员抢单。

**请求方式**: POST

**请求参数**:
- `ids` (int) - 订单ID

**功能逻辑**:
1. 验证管理员身份（超级管理员不能抢单）
2. 检查订单是否存在
3. 根据管理员角色组确定 backend_status
4. 检查订单是否已被当前组抢单
5. 插入抢单记录到 `admin_order` 表

**返回**:
- 成功: `success('抢单成功')`
- 失败: `error('错误信息')`

**注意事项**:
- 同一订单可以被不同组抢单
- 已抢单的订单不会在列表中显示给其他组

---

### 3. `add()` - 添加订单

创建新订单。

**请求方式**: GET (显示表单) / POST (提交数据)

**POST 参数**:
- `row` (array) - 订单数据数组
- `charge` (array, 可选) - 代收货款信息（当 delivery = 1 时）

**功能特性**:
- 自动生成订单号（如果未提供）
- 自动设置创建时间
- 配车类型订单自动计算金额
- 支持代收货款信息保存

**订单号生成规则**:
```
格式: YmdHis + IP后2位 + 4位随机数
示例: 202401011200001270123
```

**金额计算**:
- 仅当 `find_car_type = '配车'` 时自动计算
- 需要提供: loading, unload, car_type_id
- 自动匹配物流专线并计算费用

**返回**:
- GET: 返回添加订单表单视图
- POST: 成功返回 `success()`，失败返回 `error()`

---

### 4. `edit($ids)` - 编辑订单

编辑现有订单。

**请求方式**: GET (显示表单) / POST (提交数据)

**参数**:
- `ids` (int) - 订单ID

**功能特性**:
- 权限验证（数据权限）
- 支持更新代收货款信息
- 自动处理装货/卸货地址格式化

**POST 参数**:
- `row` (array) - 订单数据数组
- `charge` (array, 可选) - 代收货款信息

**返回**:
- GET: 返回编辑订单表单视图（包含代收货款信息）
- POST: 成功返回 `success()`，失败返回 `error()`

---

### 5. `view($ids)` - 查看订单详情

查看订单详细信息。

**请求方式**: GET

**参数**:
- `ids` (int) - 订单ID

**功能特性**:
- 权限验证
- 格式化地址信息
- 加载关联数据（车型、货物类型、包装等）
- 加载尺寸信息（dimensions）
- 加载代收货款信息

**返回**: 订单详情视图

---

### 6. `print($ids)` - 打印订单

打印订单托运单（针式打印机）。

**请求方式**: GET

**参数**:
- `ids` (int) - 订单ID

**功能说明**:
- 使用 EPSON LQ-630K 等针式打印机
- 打印模板: `application/admin/view/order/print.html`
- 包含订单基本信息、地址信息、代收货款信息

**返回**: 打印视图

---

### 7. `logistics()` - 规划路线

为订单规划物流路线并重新计算金额。

**请求方式**: POST

**请求参数**:
- `ids` (int) - admin_order 表的主键ID
- `selected_row` (int) - 选中的物流路线ID

**功能逻辑**:
1. 根据 admin_order.id 获取订单ID
2. 验证订单和路线是否存在
3. 根据路线手机号创建或查找用户（如果不存在）
4. 更新订单的 logistics_id
5. 重新计算订单金额
6. 更新订单费用字段

**用户创建规则**:
- 如果路线手机号对应的用户不存在，自动创建用户
- 用户身份设置为 3（司机）
- 默认密码随机生成

**返回**:
- 成功: `success('路线规划成功，金额已重新计算')`
- 失败: `error('错误信息')`

---

### 8. `logistics_detail($ids)` - 查看物流轨迹

查看订单的物流轨迹信息。

**请求方式**: GET

**参数**:
- `ids` (int) - 订单ID

**功能**:
- 查询订单信息
- 查询关联的物流路线信息
- 格式化装货/卸货地址

**返回**: 物流轨迹视图

---

### 9. `cancel_order()` - 取消订单

取消订单。

**请求方式**: POST

**请求参数**:
- `ids` (int) - 订单ID

**功能**:
- 将订单状态更新为已取消（pay_status = 4）

**返回**:
- 成功: `success('取消订单成功')`
- 失败: `error('取消订单失败')`

---

### 10. `refund()` - 退款

订单退款功能（微信原路返回）。

**请求方式**: POST

**请求参数**:
- `ids` (int) - 订单ID

**功能逻辑**:
1. 验证订单是否存在
2. 检查订单状态（未支付、已取消的订单不能退款）
3. 验证订单是否已支付
4. 调用微信退款接口
5. 更新订单状态为已取消
6. 记录退款账单

**退款单号生成规则**:
```
格式: RF + YmdHis + 4位随机数
示例: RF202401011200001234
```

**返回**:
- 成功: `success('退款成功，金额将通过微信原路返回')`
- 失败: `error('退款失败：错误信息')`

**注意事项**:
- 使用 `addons\epay\library\Service::refund()` 进行退款
- 退款金额 = 订单支付金额（pay_price）
- 退款后订单状态变为已取消（pay_status = 4）

---

## 私有方法

### `generateOrderNumber()` - 生成订单号

生成唯一的订单号。

**返回**: string - 订单号

**格式**: `YmdHis + IP后2位 + 4位随机数`

---

### `calculateOrderAmount($params)` - 计算订单金额

计算配车类型订单的金额。

**参数**:
- `params` (array) - 订单参数数组

**返回**: array|false - 金额计算结果数组，失败返回 false

**要求参数**:
- `loading` - 装货地址ID
- `unload` - 卸货地址ID
- `car_type_id` - 车型ID

---

### `matchLogistics($loadingId, $unloadId, $carTypeId, $params)` - 匹配物流专线

根据装货地址、卸货地址和车型匹配合适的物流专线。

**参数**:
- `loadingId` (int) - 装货地址ID
- `unloadId` (int) - 卸货地址ID
- `carTypeId` (int) - 车型ID
- `params` (array) - 订单参数

**匹配规则**:
1. 装货地址到物流起点距离 ≤ 200km
2. 卸货地址到物流终点距离 ≤ 200km
3. 方向匹配（物流终点应比起点更接近订单终点）

**返回**: array|false - 匹配结果，包含物流ID、距离等信息

---

### `checkDistanceMatch($lat1, $lng1, $lat2, $lng2, $maxDistance)` - 检查距离匹配

检查两点之间的距离是否在允许范围内。

**参数**:
- `lat1, lng1` - 点1的经纬度
- `lat2, lng2` - 点2的经纬度
- `maxDistance` - 最大允许距离（km）

**返回**: bool - 是否在范围内

---

### `calculateLogisticsCostForAdd($logisticsInfo, $carType, $distances, $params)` - 计算物流费用（添加订单时）

计算添加订单时的物流费用。

**参数**:
- `logisticsInfo` (array) - 物流信息
- `carType` (array) - 车型信息
- `distances` (array) - 距离数组
- `params` (array) - 订单参数

**返回**: array - 费用计算结果

**包含字段**:
- `logistics_id` - 物流ID
- `logistics_cost` - 物流费用（含加成）
- `logistics_driver_cost` - 物流司机费用（不含加成）
- `pickup_driver_fee` - 取货司机费用
- `shipment_driver_fee` - 送货司机费用
- `pickup_fee` - 取货费用（含加成）
- `shipment_fee` - 送货费用（含加成）
- `pay_price` - 支付总价
- `total_amount` - 总金额

---

### `recalculateLogisticsCost($orderId, $logisticsId)` - 重新计算物流费用

重新计算订单的物流费用（用于路线规划后）。

**参数**:
- `orderId` (int) - 订单ID
- `logisticsId` (int) - 物流路线ID

**返回**: bool - 是否成功

**功能**:
- 根据新的物流路线重新计算订单各项费用
- 更新订单的费用字段

---

### `calculateSegmentDistances($loading, $unload, $logistics)` - 计算三段距离

计算订单的三段距离。

**参数**:
- `loading` (array) - 装货地址信息
- `unload` (array) - 卸货地址信息
- `logistics` (array) - 物流路线信息

**返回**: array - 距离数组

**包含字段**:
- `loading_to_start` - 装货地址到物流起点距离
- `logistics_line` - 物流专线距离
- `end_to_unload` - 物流终点到卸货地址距离

---

### `calculateLogisticsCost($order, $logistics, $carType, $distances, $loading, $unload)` - 计算物流费用

计算订单的完整物流费用。

**参数**:
- `order` (array) - 订单信息
- `logistics` (array) - 物流路线信息
- `carType` (array) - 车型信息
- `distances` (array) - 距离数组
- `loading` (array, 可选) - 装货地址信息
- `unload` (array, 可选) - 卸货地址信息

**计算逻辑**:
1. 根据重量和体积计算物流基础费用
2. 根据省份查询取货/送货价格
3. 计算取货费用（起步价 + 超出5km部分）
4. 计算送货费用（起步价 + 超出5km部分）
5. 应用司机费用加成百分比
6. 应用货物类型价格上浮百分比

**返回**: array - 费用计算结果

---

### `getGoodsTypePercentage($goodsTypeId)` - 获取货物类型价格上浮百分比

获取货物类型的价格上浮百分比。

**参数**:
- `goodsTypeId` (int) - 货物类型ID

**返回**: float - 百分比值（0表示无上浮）

---

### `getProvincePrice($carTypeType, $province)` - 获取省份价格

根据车型类型和省份查询价格。

**参数**:
- `carTypeType` (int) - 车型的type值
- `province` (string) - 省份名称

**返回**: array|false - 包含 startingfare 和 price 的数组，未找到返回 false

**数据来源**: `provinceprice` 表

---

### `extractProvinceFromAddress($address)` - 从地址中提取省份

从完整地址字符串中提取省份名称。

**参数**:
- `address` (string) - 完整地址

**返回**: string - 省份名称

**处理规则**:
- 包含"省"的地址：提取省名 + "省"
- 直辖市：返回直辖市名称（不带"市"）
- 其他：返回空字符串

---

## 数据库表关联

### 主要关联表

- `order` - 订单主表
- `user` - 用户表（通过 order.userid 关联）
- `user_address` - 用户地址表（通过 order.loading/unload 关联）
- `admin_order` - 管理员抢单表
- `auth_group` - 权限组表
- `auth_group_access` - 管理员权限组关联表
- `logistics` - 物流路线表
- `car_type` - 车型表
- `goods_type` - 货物类型表
- `packaging` - 包装表
- `provinceprice` - 省份价格表
- `charge` - 代收货款表
- `bill` - 账单表

---

## 配置项

### 系统配置（Config）

- `site.PickUpDriverFreight` - 取货司机费用加成百分比
- `site.LogisticsDriverFreight` - 物流司机费用加成百分比
- `site.DeliveryDriverFreight` - 送货司机费用加成百分比

---

## 业务规则

### 订单状态

- `pay_status = 1` - 未支付
- `pay_status = 2` - 已支付（进行中）
- `pay_status = 3` - 已完成
- `pay_status = 4` - 已取消
- `pay_status = 5` - 已取消（超级管理员过滤用）

### 订单类型

- `find_car_type = '配车'` - 配车类型，需要自动计算金额
- 其他类型 - 手动填写金额

### 权限身份

- `identity = 2` - 路线/规划身份，对应 `backend_status = 1`
- `identity = 3` - 调度身份，对应 `backend_status = 2`

### 超时判断

- 订单创建时间超过 15 分钟视为超时

---

## 注意事项

1. **权限控制**: 所有方法都设置了 `$noNeedLogin` 和 `$noNeedRight`，实际使用时可能需要根据业务需求调整。

2. **金额计算**: 配车类型订单在添加时会自动计算金额，但需要确保相关数据完整（地址、车型、物流路线等）。

3. **抢单机制**: 不同组可以抢同一个订单，已抢单的订单不会在列表中显示给其他组。

4. **退款功能**: 退款功能依赖微信支付插件（epay），需要确保插件正确配置。

5. **距离计算**: 使用 `calculateDistance()` 和 `calculateDrivingDistance()` 函数计算距离，需要确保这些函数可用。

6. **价格计算**: 价格计算涉及多个因素（重量、体积、距离、省份、货物类型等），需要确保相关配置和数据完整。

---

## 更新日志

- 支持按用户名、手机号搜索订单
- 支持订单统计功能（仅超级管理员）
- 支持自动匹配物流专线并计算金额
- 支持代收货款功能
- 支持订单打印功能
- 支持微信退款功能
- 支持路线规划后自动重新计算金额

---

## 作者

物流系统开发团队

## 版本

1.0.0

## 最后更新

2024年
