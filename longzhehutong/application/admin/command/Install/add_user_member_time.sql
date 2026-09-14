-- user 表增加会员到期时间
ALTER TABLE `fa_user`
  ADD COLUMN `member_time` bigint(16) DEFAULT NULL COMMENT '会员到期时间' AFTER `jointime`;
