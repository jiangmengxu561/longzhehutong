# 物流系统加盟商（一级/二级）功能设计说明

## 一、总体思路

加盟商**复用现有后台角色组体系**：一级加盟商 = 挂在总部（`fa_auth_group.id=1`）下的一棵“代理”组（`identity=1`），二级加盟商 = 挂在一级加盟商代理组子树下的另一棵“代理”组。因此，现有订单/会员的**范围裁剪**（按 `identity=1/2/3` + 管理员-会员绑定 `fa_admin_user_bind`）可天然把数据限制在“自己 + 下级加盟商”内，无需另写一套隔离逻辑。

新增的核心是：加盟商**档案**、**钱包及流水**、**会员按手机号搜索绑定与扣费改身份**、**订单完成时按比例扣费**。

## 二、新增数据表（见 `application/admin/command/Install/franchise.sql`）

| 表 | 说明 |
|----|------|
| `fa_franchise` | 加盟商档案：`parent_id`、`level`(1/2)、`admin_id`(登录后台管理员)、`group_id`(代理角色组)、`wallet_balance`、费率/费用、状态 |
| `fa_franchise_wallet_log` | 钱包流水：收/支、变动前后余额、关联订单/会员/加盟商、操作人 |
| `fa_franchise_member` | 加盟商与其绑定会员（会员唯一绑定） |

费率/费用由上级设置并各加盟商自持：

- `member_month_fee`：修改会员身份/职位/到期，**每月**扣费（默认 150，由总部统一设置，全加盟商共用）
- `add_level2_fee`：新增一个二级加盟商扣费（默认 500，由总部统一设置，全加盟商共用）
- `region_ids`：区域（一级=市多选；二级=父城市的区/县）
- `formal_employee_quota`：正式员工名额上限
- `fa_franchise_member.commission_percent`：兼职抽成百分比
- `fa_dispatch_reserve_fund`：调度/线路备用金（加盟商管理员直接充值给名下调度/线路，不与加盟商钱包联动）
- `order_fee_percent`：本钱包按订单“总运费(`pay_price`) ”扣费百分比（一级默认 1，二级默认 1.5）

## 三、扣费规则

1. **改会员身份/到期**：仅改 `username` 不扣费；设为“正式员工(membertype=3)”免费（走正式员工名额）；其余（改到期/设为普通/兼职/会展）按“月数向上取整 × 全局 `member_month_fee`”扣费。
2. **新增二级加盟商**：由一级加盟商操作，从一级加盟商钱包扣全局 `add_level2_fee`。
3. **订单扣费**：订单完成时（两条链路都会触发，服务已判重）：
   - **送货司机完成**：`app/api/controller/Dricer.php::confirmorder()`，`dricerorder.type=3`
   - **后台确认收货/专车到达**：`app/admin/controller/admin/Order.php`（`logistics_status=7`）
   - 会员归属一级加盟商 → 扣该一级加盟商钱包 `pay_price × order_fee_percent`（默认 1%）
   - 会员归属二级加盟商 → 扣该二级加盟商钱包（其 `order_fee_percent`，默认 1.5%）＋ 扣其上级一级加盟商钱包（其一 `order_fee_percent`，默认 1%）
   - 每个订单对同一加盟商只扣一次（以 `franchise_wallet_log` 的 `related_type=order` + `related_id=订单id` 判重）
4. **小程序端开通会员（入账）**：会员在小程序付款开通/续费会员成功后（`app/api/controller/Memberrecharge.php::memnotify()`），若该会员已被某加盟商绑定，则给该加盟商钱包**入账** `会员月费单价(member_month_fee，默认150) × 本次开通月数`。
   - 月数口径（`FranchiseService::memberMonthsFromPackage()`，与“改会员到期”一致）：月付=套餐月数（季付 3、半年 6）、年付 ×12、周付 ×7÷30 向上取整（不足一月按一月）
   - 服务：`FranchiseService::rewardMemberRecharge($userId, $memberorderId, $amount = null, $orderNo = '', $months = 1)`
   - 幂等：以 `related_type=member_recharge` + `related_id=会员充值订单id` 判重，微信重复回调不会重复入账
   - 未绑定加盟商的会员不入账；只入账被绑定的那家加盟商，与其上级一级加盟商无关；已支付订单重复通知会补一次入账（上次失败时不丢单）
5. **余额不足/无权限/重复绑定**：一律**禁止并给出提示**；任一加盟商（含上级）余额 `<=0` 时，其名下及下级所有账号禁止操作（`FranchiseService::canOperate()`）。

## 四、数据隔离

- 加盟商绑定会员时，会**同时**写入 `fa_franchise_member` 和现有 `fa_admin_user_bind`（管理员-会员绑定）。这样后台“会员管理/订单列表”的现有范围逻辑即自动只显示该加盟商（及其下级）绑定的会员与其订单。
- 一级加盟商若需展示**二级加盟商**绑定的会员/订单：后台范围需从“仅当前管理员绑定”扩展为“加盟商子树全部绑定”。已提供 `FranchiseService::getScopeMemberIds($franchiseId)` 接口，可在相关列表控制器并入。
- 新建加盟商时，其角色组 `rules` 使用**精简默认权限**（`FranchiseService::franchiseDefaultMenuNames()`：首页/会员/用户绑定/订单/我的订单/物流/记账/财务/加盟商），不再直接继承总后台的 `*`，避免加盟商看到系统管理类菜单。

## 五、新增/改动文件

**新增**

- `application/admin/command/Install/franchise.sql`（建表 + 菜单权限）
- `application/admin/model/Franchise.php`
- `application/admin/model/FranchiseWalletLog.php`
- `application/admin/model/FranchiseMember.php`
- `application/admin/library/FranchiseService.php`
- `application/admin/controller/Franchise.php`
- `application/admin/view/franchise/index.html`、`add.html`、`edit.html`、`wallet.html`、`adjustwallet.html`、`member.html`、`memberedit.html`、`memberselect.html`
- `public/assets/js/backend/franchise.js`

**改动**

- `application/api/controller/Dricer.php`：在 `confirmorder()` 送货司机完成时，调用 `FranchiseService::settleOrderFranchiseFee()`。
- `application/admin/controller/admin/Order.php`：在“确认收货(专车到达)”时，同样调用 `FranchiseService::settleOrderFranchiseFee()`。

## 六、上线步骤

1. 在数据库执行 `application/admin/command/Install/franchise.sql`（建 3 张表 + 写入 `fa_auth_rule` 菜单）。
2. 通过本模块“加盟商管理 → 新增”创建加盟商（它会一并创建登录管理员＋代理角色组＋加盟商档案，二级会自动扣上级费用）。
3. 给加盟商角色组勾选合适权限（建议只勾订单/调度/财务/会员/加盟商相关菜单，不要给 `auth`、`general` 等系统管理项）。
4. 总部给一级加盟商钱包充值（线下收款后，用“调整钱包”加余额）；一级给二级钱包充值同理。
5. 加盟商登录后，用“按手机号绑定会员→改身份/到期”把会员绑到自己名下；之后即可在订单/会员列表查看。

## 七、注意事项 / 待真库验证

- 本模块基于真实库 `fa_order`/`fa_user`/`fa_auth_group`/`fa_admin_user_bind` 字段设计，但未在线上环境跑过端到端。建议先在测试库执行 SQL 并自测“新增→绑定→改身份扣费→订单完成扣费”链路。
- 订单“总运费”取 `fa_order.pay_price`；如你希望取 `shipping_cost` 或含其它费用的金额，改 `FranchiseService::ORDER_BASE_FIELD` 即可。
- 会员“到期时间”当前以 `member_time` 存储（与现有后台一致）；月份按“30 天/月”向上取整。
- 订单完成扣费只挂了**送货司机完成（type=3）**这一条链；若个别订单（如仅专线、无送货司机）也需扣费，需再在后台“确认收货（logistics_status=7）”处补一处调用（服务已判重，可安全重复调用）。
