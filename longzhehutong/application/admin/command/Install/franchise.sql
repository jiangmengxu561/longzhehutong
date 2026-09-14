-- 加盟商功能（一级/二级）
-- =====================================================================
-- 设计要点（按已确认需求）：
--   1) 加盟商 = 独立代理角色组（identity=1），区域写角色组 city，member 绑定写 admin_user_bind
--   2) fa_franchise            加盟商档案：层级、区域(region_ids)、钱包、正式员工名额等
--   3) fa_franchise_member     加盟商与绑定会员（含兼职抽成百分比）
--   4) fa_franchise_wallet_log 加盟商钱包流水
--   5) fa_franchise_config     全局费用（改会员 150 / 新增二级 500，总部统一设置，全加盟商共用）
--   6) 调度备用金由加盟商管理员给自己名下调度/线路充值，资金从加盟商钱包扣除
--   7) 财务记账表 fa_bookkeeping 需新增列 admin_id（创建人管理员ID），用于加盟商范围隔离：
--      ALTER TABLE `fa_bookkeeping` ADD COLUMN `admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人管理员ID' AFTER `id`;
--      ALTER TABLE `fa_bookkeeping` ADD KEY `idx_admin_id` (`admin_id`);


-- ============ 加盟商全局费用配置表 ============
CREATE TABLE IF NOT EXISTS `fa_franchise_config` (
  `id` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '固定为1',
  `member_month_fee` decimal(12,2) NOT NULL DEFAULT '150.00' COMMENT '修改会员身份/到期,每月扣费(总部统一设置)',
  `add_level2_fee` decimal(12,2) NOT NULL DEFAULT '500.00' COMMENT '新增一个二级加盟商扣费(总部统一设置)',
  `template_franchise_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '规则模板加盟商ID(0=自动取最小一级加盟商)',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='加盟商全局费用配置';

INSERT INTO `fa_franchise_config` (`id`,`member_month_fee`,`add_level2_fee`,`template_franchise_id`,`updatetime`)
SELECT 1,'150.00','500.00',0,UNIX_TIMESTAMP()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM fa_franchise_config WHERE id=1);


-- ============ 加盟商档案表 ============
CREATE TABLE IF NOT EXISTS `fa_franchise` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '加盟商ID',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '上级加盟商ID(0=总部直属一级)',
  `level` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '层级:1=一级加盟商,2=二级加盟商',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '加盟商名称',
  `contact` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '联系电话',
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '登录后台管理员ID(fa_admin.id)',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '后台代理角色组ID(fa_auth_group.id, identity=1)',
  `region_ids` text COMMENT '区域ID(JSON数组):一级=市(area level=2),二级=区/县(area level=3),必须属于上级所选城市',
  `wallet_balance` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '线上钱包余额',
  `formal_employee_quota` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '正式员工名额上限(0=不限制;总部给一级设,一级给二级设)',
  `order_fee_percent` decimal(6,3) NOT NULL DEFAULT '1.000' COMMENT '本钱包按订单总运费扣费百分比(一级默认1,上级可改;二级默认1.5,一级可改)',
  `status` enum('normal','hidden','disabled') NOT NULL DEFAULT 'normal' COMMENT '状态',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='加盟商档案表';


-- ============ 加盟商钱包流水表 ============
CREATE TABLE IF NOT EXISTS `fa_franchise_wallet_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '流水ID',
  `franchise_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '加盟商ID',
  `type` enum('income','expense') NOT NULL DEFAULT 'income' COMMENT '方向:income=收入,expense=支出',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '变动金额(正数)',
  `balance_before` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '变动前余额',
  `balance_after` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT '变动后余额',
  `related_type` varchar(30) NOT NULL DEFAULT '' COMMENT '关联类型:order/member/add_franchisee/adjust/reserve_fund',
  `related_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '关联ID(订单ID/会员ID/加盟商ID/调度管理员ID等)',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `operator_admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作管理员ID',
  `operator_name` varchar(64) NOT NULL DEFAULT '' COMMENT '操作人昵称',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_franchise_id` (`franchise_id`),
  KEY `idx_related` (`related_type`,`related_id`),
  KEY `idx_createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='加盟商钱包流水表';


-- ============ 加盟商-会员 绑定表 ============
CREATE TABLE IF NOT EXISTS `fa_franchise_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `franchise_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '加盟商ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '前台会员ID(fa_user.id)',
  `commission_percent` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '兼职抽成百分比(加盟商可对每个绑定用户修改)',
  `createtime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '绑定时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_id` (`user_id`),
  KEY `idx_franchise_id` (`franchise_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='加盟商与会员绑定表';


-- ============ 菜单/权限规则（幂等，可重复执行） ============
INSERT INTO `fa_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', 0, 'franchise', '加盟商管理', 'fa fa-handshake-o', '', '', 1, NULL, '', 'jms', 'jiashangshang', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `fa_auth_rule` WHERE `name`='franchise' AND `ismenu`=1);

SET @franchise_menu_id = (SELECT id FROM fa_auth_rule WHERE `name` = 'franchise' AND `ismenu` = 1 LIMIT 1);

INSERT INTO `fa_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', @franchise_menu_id, n.name, n.title, n.icon, '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), n.weigh, 'normal'
FROM (
  SELECT 'franchise/index' name, '查看' title, 'fa fa-circle-o' icon, 1 weigh
  UNION ALL SELECT 'franchise/add', '新增加盟商', 'fa fa-circle-o', 2
  UNION ALL SELECT 'franchise/edit', '编辑加盟商', 'fa fa-circle-o', 3
  UNION ALL SELECT 'franchise/del', '删除加盟商', 'fa fa-circle-o', 4
  UNION ALL SELECT 'franchise/wallet', '钱包流水', 'fa fa-circle-o', 5
  UNION ALL SELECT 'franchise/adjustwallet', '调整钱包', 'fa fa-circle-o', 6
  UNION ALL SELECT 'franchise/member', '绑定会员', 'fa fa-circle-o', 7
  UNION ALL SELECT 'franchise/memberedit', '改会员身份/到期', 'fa fa-circle-o', 8
  UNION ALL SELECT 'franchise/memberselect', '按手机号搜会员', 'fa fa-circle-o', 9
  UNION ALL SELECT 'franchise/bind', '绑定会员', 'fa fa-circle-o', 10
  UNION ALL SELECT 'franchise/config', '加盟商全局设置', 'fa fa-circle-o', 11
  UNION ALL SELECT 'franchise/staff', '员工管理', 'fa fa-circle-o', 12
  UNION ALL SELECT 'franchise/staffadd', '添加员工', 'fa fa-circle-o', 13
) n
WHERE NOT EXISTS (SELECT 1 FROM `fa_auth_rule` WHERE `name` = n.name);
