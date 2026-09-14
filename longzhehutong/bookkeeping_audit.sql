-- 财务记账修改/删除审核表
-- 子后台的修改/删除操作先写入此表，等待总后台审核确认
CREATE TABLE IF NOT EXISTS `fa_bookkeeping_audit` (
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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='财务记账修改/删除审核表';

-- 添加记账审核菜单（挂在财务记账 bookkeeping 菜单下）
-- 请先确认 fa_auth_rule 表中已存在 bookkeeping 菜单，且其 id 作为 pid
-- 如果执行报错，可以手动设置 pid 值
INSERT INTO `fa_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', t.id, 'bookkeepingaudit', '记账审核', 'fa fa-check-square-o', '', '', 1, NULL, '', 'jzsj', 'jizhangshenji', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'
FROM `fa_auth_rule` t
WHERE t.`name` = 'bookkeeping' AND t.`ismenu` = 1
LIMIT 1;

-- 添加记账审核权限节点（查看）
INSERT INTO `fa_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', t.id, 'bookkeepingaudit/index', '查看', 'fa fa-circle-o', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1, 'normal'
FROM `fa_auth_rule` t
WHERE t.`name` = 'bookkeepingaudit' AND t.`ismenu` = 1
LIMIT 1;

-- 添加记账审核权限节点（审核）
INSERT INTO `fa_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `condition`, `remark`, `ismenu`, `menutype`, `extend`, `py`, `pinyin`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', t.id, 'bookkeepingaudit/edit', '审核', 'fa fa-pencil', '', '', 0, NULL, '', '', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 2, 'normal'
FROM `fa_auth_rule` t
WHERE t.`name` = 'bookkeepingaudit' AND t.`ismenu` = 1
LIMIT 1;