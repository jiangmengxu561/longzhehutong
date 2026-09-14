-- Run once for existing installations that already have fa_price_calc_profit.
ALTER TABLE `fa_price_calc_profit`
  ADD COLUMN `is_chaxun` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已查询：0否，1是' AFTER `shipmenty_distance`,
  ADD KEY `idx_profit_chaxun` (`is_chaxun`, `profit`);
