-- 用户银行卡四要素实名认证，绑定 fa_user
CREATE TABLE IF NOT EXISTS `fa_user_bankcard_auth` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID，关联 fa_user.id',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '姓名',
  `id_card` varchar(32) NOT NULL DEFAULT '' COMMENT '身份证号',
  `account_no` varchar(64) NOT NULL DEFAULT '' COMMENT '银行卡号',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '银行预留手机号',
  `status` varchar(10) NOT NULL DEFAULT '' COMMENT '核验状态 01通过',
  `msg` varchar(255) NOT NULL DEFAULT '' COMMENT '核验说明',
  `bank` varchar(100) NOT NULL DEFAULT '' COMMENT '开户行',
  `card_name` varchar(100) NOT NULL DEFAULT '' COMMENT '卡名称',
  `card_type` varchar(50) NOT NULL DEFAULT '' COMMENT '卡类型',
  `trace_id` varchar(64) NOT NULL DEFAULT '' COMMENT '上游流水号',
  `createtime` bigint(16) DEFAULT NULL COMMENT '创建时间',
  `updatetime` bigint(16) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uid` (`uid`),
  KEY `idx_id_card` (`id_card`),
  KEY `idx_account_no` (`account_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户银行卡四要素实名认证';
