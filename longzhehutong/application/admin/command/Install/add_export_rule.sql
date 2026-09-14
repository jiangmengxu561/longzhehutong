-- 将「导出」功能添加到菜单（权限规则），使订单列表的导出按钮可显示并可分配权限
-- 执行方式：在数据库中执行本 SQL，或通过 权限管理 -> 菜单规则 -> 添加 手动添加：
--   规则名(name): order/export  标题(title): 导出  父级: 订单  是否菜单: 否

INSERT INTO `fa_auth_rule` (type, pid, name, title, icon, url, `condition`, remark, ismenu, menutype, extend, py, pinyin, createtime, updatetime, weigh, status)
SELECT 'file',
       CASE WHEN name = 'order' THEN id ELSE pid END,
       'order/export',
       '导出',
       'fa fa-download',
       '',
       '',
       '',
       0,
       NULL,
       '',
       '',
       '',
       UNIX_TIMESTAMP(),
       UNIX_TIMESTAMP(),
       99,
       'normal'
FROM fa_auth_rule
WHERE name IN ('order', 'order/index')
ORDER BY (name = 'order') DESC
LIMIT 1;
