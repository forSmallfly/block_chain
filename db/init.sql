CREATE TABLE `filter_transaction` (
	`id` INT ( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT,
	`block_number` INT ( 11 ) UNSIGNED DEFAULT '0' COMMENT '交易所属区块编号',
	`tx_hash` CHAR ( 66 ) NOT NULL DEFAULT '' COMMENT '交易hash',
	`tx_index` INT ( 11 ) UNSIGNED NOT NULL DEFAULT '0' COMMENT '交易在区块中的索引',
	`tx_value` VARCHAR ( 50 ) NOT NULL DEFAULT '' COMMENT '交易金额(单位：Ether)',
	`tx_status` TINYINT ( 1 ) UNSIGNED NOT NULL DEFAULT '1' COMMENT '交易状态：0失败，1成功',
	`add_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
	`update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
	PRIMARY KEY ( `id` ) USING BTREE,
	UNIQUE KEY `tx_hash` ( `tx_hash` ),
KEY `block_number` ( `block_number` )
) ENGINE = INNODB DEFAULT CHARSET = utf8mb4 COMMENT = '筛选交易记录表';

CREATE TABLE `mint_token_log` (
	`id` INT ( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT,
	`tx_hash` CHAR ( 66 ) NOT NULL DEFAULT '' COMMENT '交易hash',
	`user_address` CHAR ( 42 ) NOT NULL DEFAULT '' COMMENT '用户地址',
	`amount` VARCHAR ( 50 ) NOT NULL DEFAULT '0' COMMENT '铸造金额（单位：Ether）',
	`status` TINYINT ( 1 ) UNSIGNED NOT NULL DEFAULT '1' COMMENT '交易状态：0失败，1成功',
	`add_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
	`update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
	PRIMARY KEY ( `id` ) USING BTREE,
	KEY `status` ( `status` ) USING BTREE,
	KEY `tx_hash` ( `tx_hash` ) USING BTREE,
KEY `user_address` ( `user_address` ) USING BTREE
) ENGINE = INNODB DEFAULT CHARSET = utf8mb4 COMMENT = '铸造token记录表';

CREATE TABLE `mint_token_task` (
	`id` INT ( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT,
	`tx_hash` CHAR ( 66 ) NOT NULL DEFAULT '' COMMENT '交易hash',
	`user_address` CHAR ( 44 ) NOT NULL DEFAULT '' COMMENT '用户地址',
	`amount` VARCHAR ( 50 ) NOT NULL DEFAULT '0' COMMENT '铸造金额（单位：Ether）',
	`status` enum ( '待处理', '处理中', '已发送', '处理失败', '处理成功' ) NOT NULL DEFAULT '待处理' COMMENT '任务状态',
	`retry_count` TINYINT ( 3 ) UNSIGNED NOT NULL DEFAULT '0' COMMENT '已经重试次数',
	`next_retry_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '下一次重试时间',
	`remark` text COMMENT '失败备注',
	`add_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
	`update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
	PRIMARY KEY ( `id` ) USING BTREE,
	KEY `status` ( `status` ) USING BTREE,
	KEY `tx_hash` ( `tx_hash` ) USING BTREE,
KEY `user_address` ( `user_address` ) USING BTREE
) ENGINE = INNODB DEFAULT CHARSET = utf8mb4 COMMENT = '铸造token任务表';

CREATE TABLE `transfer` (
	`id` INT ( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT,
	`tx_hash` CHAR ( 66 ) NOT NULL COMMENT '交易hash',
	`user_address` CHAR ( 44 ) NOT NULL DEFAULT '' COMMENT '用户地址',
	`amount` VARCHAR ( 50 ) NOT NULL DEFAULT '0' COMMENT '转账金额（单位：Ether）',
	`status` TINYINT ( 1 ) UNSIGNED NOT NULL DEFAULT '1' COMMENT '交易状态：0失败，1成功',
	`add_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '添加时间',
	`update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
	PRIMARY KEY ( `id` ) USING BTREE,
	KEY `status` ( `status` ) USING BTREE,
	KEY `tx_hash` ( `tx_hash` ) USING BTREE,
KEY `user_address` ( `user_address` ) USING BTREE
) ENGINE = INNODB DEFAULT CHARSET = utf8mb4 COMMENT = '转账交易表';