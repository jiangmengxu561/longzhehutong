-- 会员充值：菜单/权限规则（幂等，可重复执行）
-- 套餐表名请替换为你实际建的（此处按 fa_member_package / 模型名 member_package 编写）

-- 一级菜单：会员套餐
INSERT INTO `fa_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', 0, 'memberpackage', '会员套餐', 'fa fa-cny', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 30, 'normal'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_auth_rule` WHERE `name` = 'memberpackage' AND `ismenu` = 1);

-- 一级菜单：会员充值记录
INSERT INTO `fa_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', 0, 'memberorder', '会员充值记录', 'fa fa-list-alt', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 31, 'normal'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `fa_auth_rule` WHERE `name` = 'memberorder' AND `ismenu` = 1);

SET @pkg_id = (SELECT id FROM `fa_auth_rule` WHERE `name` = 'memberpackage' AND `ismenu` = 1 LIMIT 1);
SET @ord_id = (SELECT id FROM `fa_auth_rule` WHERE `name` = 'memberorder' AND `ismenu` = 1 LIMIT 1);

-- 套餐子规则
INSERT INTO `fa_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', @pkg_id, n.name, n.title, n.icon, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), n.weigh, 'normal'
FROM (
  SELECT 'memberpackage/index' name, '查看' title, 'fa fa-circle-o' icon, 1 weigh
  UNION ALL SELECT 'memberpackage/add', '添加', 'fa fa-circle-o', 2
  UNION ALL SELECT 'memberpackage/edit', '编辑', 'fa fa-circle-o', 3
  UNION ALL SELECT 'memberpackage/del', '删除', 'fa fa-circle-o', 4
) n
WHERE NOT EXISTS (SELECT 1 FROM `fa_auth_rule` WHERE `name` = n.name);

-- 充值记录子规则
INSERT INTO `fa_auth_rule` (`type`, `pid`, `name`, `title`, `icon`, `ismenu`, `createtime`, `updatetime`, `weigh`, `status`)
SELECT 'file', @ord_id, n.name, n.title, n.icon, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), n.weigh, 'normal'
FROM (
  SELECT 'memberorder/index' name, '查看' title, 'fa fa-circle-o' icon, 1 weigh
) n
WHERE NOT EXISTS (SELECT 1 FROM `fa_auth_rule` WHERE `name` = n.name);
