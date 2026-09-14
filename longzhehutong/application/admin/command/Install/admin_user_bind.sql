-- 子后台绑定前台会员：执行本文件创建数据表
-- 菜单请在后台「权限管理 → 菜单规则」中手动添加（或使用下方示例 SQL，注意修改 pid 为实际「会员管理」父菜单 id）

CREATE TABLE IF NOT EXISTS `fa_admin_user_bind` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '后台管理员ID',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '前台会员用户ID',
  `createtime` bigint(16) DEFAULT NULL COMMENT '绑定时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_id` (`user_id`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员与会员绑定';

-- 以下为可选菜单（请将 @user_menu_id 替换为 fa_auth_rule 中 name=user 且 ismenu=1 的记录的 id）
/*
SET @user_menu_id = (SELECT id FROM fa_auth_rule WHERE `name` = 'user' AND ismenu = 1 LIMIT 1);
INSERT INTO `fa_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
VALUES ('file', @user_menu_id, 'user/userbind', '用户绑定', 'fa fa-link', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
SET @bind_id = LAST_INSERT_ID();
INSERT INTO `fa_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`) VALUES
('file', @bind_id, 'user/userbind/index', '查看', 'fa fa-circle-o', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @bind_id, 'user/userbind/add', '添加', 'fa fa-circle-o', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @bind_id, 'user/userbind/edit', '编辑', 'fa fa-circle-o', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @bind_id, 'user/userbind/del', '删除', 'fa fa-circle-o', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal'),
('file', @bind_id, 'user/userbind/selectpage_user', '选择会员', 'fa fa-circle-o', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0, 'normal');
*/
