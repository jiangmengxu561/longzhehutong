-- order 表增加预计到货时间（时间戳）：当天订单物流未发车、点出发时推迟一天写入
ALTER TABLE `fa_order`
  ADD COLUMN `estimated_arrival_time` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '预计到货时间(时间戳)，当天未发车点出发时推迟一天写入' AFTER `arrivaltime`;
