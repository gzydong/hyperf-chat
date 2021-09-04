# lar_users_chat_list 数据表同步SQL
ALTER TABLE `lar_users_chat_list` RENAME `lar_talk_list`;
ALTER TABLE `lar_talk_list` DROP INDEX `idx_uid_type_friendid_group_id`;
ALTER TABLE `lar_talk_list` MODIFY `is_top` tinyint(4) unsigned DEFAULT '0' COMMENT '是否置顶[0:否;1:是;]';
ALTER TABLE `lar_talk_list` CHANGE `type` `talk_type` tinyint(3) unsigned DEFAULT '1' COMMENT '聊天类型[1:私信;2:群聊;]';
ALTER TABLE `lar_talk_list` CHANGE `uid` `user_id` int(11) DEFAULT '0' COMMENT '用户ID';
ALTER TABLE `lar_talk_list` CHANGE `not_disturb` `is_disturb` tinyint(4) unsigned DEFAULT '0' COMMENT '消息免打扰[0:否;1:是;]';
ALTER TABLE `lar_talk_list` ADD `receiver_id` int(11) unsigned DEFAULT '0' COMMENT '接收者ID（用户ID 或 群ID）';
ALTER TABLE `lar_talk_list` ADD `is_delete` tinyint(4) unsigned DEFAULT '0' COMMENT '是否删除[0:否;1:是;]';
ALTER TABLE `lar_talk_list` ADD `is_robot` tinyint(4) unsigned DEFAULT '0' COMMENT '是否机器人[0:否;1:是;]';
ALTER TABLE `lar_talk_list` ADD INDEX idx_user_id_receiver_id_talk_type (`user_id`,`receiver_id`,`talk_type`);
UPDATE `lar_talk_list` set `receiver_id` = if(`talk_type` = 1,`friend_id`,`group_id`),`is_delete` = if(`status` = 1,0,1);
ALTER TABLE `lar_talk_list` DROP COLUMN `friend_id`;
ALTER TABLE `lar_talk_list` DROP COLUMN `group_id`;
ALTER TABLE `lar_talk_list` DROP COLUMN `status`;

# lar_emoticon 数据表同步SQL
ALTER TABLE `lar_emoticon` MODIFY `name` varchar(50) NOT NULL DEFAULT '' COMMENT '分组名称';
ALTER TABLE `lar_emoticon` CHANGE `url` `icon` varchar(255) DEFAULT '' COMMENT '分组图标';
ALTER TABLE `lar_emoticon` ADD `status` tinyint(4) DEFAULT '0' COMMENT '分组状态[-1:已删除;0:正常;1:已禁用;]';
ALTER TABLE `lar_emoticon` ADD `updated_at` datetime DEFAULT NULL COMMENT '更新时间';
ALTER TABLE `lar_emoticon` DROP INDEX `name`;
ALTER TABLE `lar_emoticon` ADD UNIQUE uk_name (`name`);


# lar_emoticon_details 数据表同步SQL
ALTER TABLE `lar_emoticon_details` RENAME `lar_emoticon_item`;
ALTER TABLE `lar_emoticon_item` MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '表情包详情ID';
ALTER TABLE `lar_emoticon_item` MODIFY `describe` varchar(20) DEFAULT '' COMMENT '表情描述';
ALTER TABLE `lar_emoticon_item` MODIFY `url` varchar(255) DEFAULT '' COMMENT '图片链接';
ALTER TABLE `lar_emoticon_item` DROP COLUMN `created_at`;
ALTER TABLE `lar_emoticon_item` ADD `created_at` datetime DEFAULT NULL COMMENT '创建时间';
ALTER TABLE `lar_emoticon_item` ADD `updated_at` datetime DEFAULT NULL COMMENT '更新时间';
ALTER TABLE `lar_emoticon_item` comment '表情包详情表';

# lar_users 数据表同步SQL
ALTER TABLE `lar_users` ADD `is_robot` tinyint(4) unsigned DEFAULT '0' COMMENT '是否机器人[0:否;1:是;]';
ALTER TABLE `lar_users` ADD `updated_at` datetime DEFAULT NULL COMMENT '更新时间';

# lar_chat_records 数据表同步SQL
ALTER TABLE `lar_chat_records` RENAME `lar_talk_records`;
ALTER TABLE `lar_talk_records` DROP INDEX `idx_userid_receiveid`;
ALTER TABLE `lar_talk_records` CHANGE `source` `talk_type` tinyint(3) unsigned DEFAULT '1' COMMENT '对话类型[1:私信;2:群聊;]';
ALTER TABLE `lar_talk_records` MODIFY `msg_type` tinyint(3) unsigned DEFAULT '1' COMMENT '消息类型[1:文本消息;2:文件消息;3:会话消息;4:代码消息;5:投票消息;6:群公告;7:好友申请;8:登录通知;9:入群消息/退群消息;]';
ALTER TABLE `lar_talk_records` CHANGE `receive_id` `receiver_id` int(11) unsigned DEFAULT '0' COMMENT '接收者ID（用户ID 或 群ID）';
ALTER TABLE `lar_talk_records` MODIFY `is_revoke` tinyint(4) unsigned DEFAULT '0' COMMENT '是否撤回消息[0:否;1:是;]';
ALTER TABLE `lar_talk_records` MODIFY `content` text CHARACTER SET utf8mb4 COMMENT '文本消息 {@nickname@}';
ALTER TABLE `lar_talk_records` ADD `is_mark` tinyint(4) unsigned DEFAULT '0' COMMENT '是否重要消息[0:否;1:是;]';
ALTER TABLE `lar_talk_records` ADD `is_read` tinyint(4) DEFAULT '0' COMMENT '是否已读[0:否;1:是;]';
ALTER TABLE `lar_talk_records` ADD `quote_id` int(11) unsigned DEFAULT '0' COMMENT '引用消息ID';
ALTER TABLE `lar_talk_records` ADD `warn_users` varchar(200) NOT NULL DEFAULT '' COMMENT '@好友 、 多个用英文逗号 “,” 拼接 (0:代表所有人)';
ALTER TABLE `lar_talk_records` ADD `updated_at` datetime DEFAULT NULL COMMENT '更新时间';
ALTER TABLE `lar_talk_records` ADD INDEX idx_user_id_receiver_id (`user_id`,`receiver_id`);
UPDATE `lar_talk_records` SET `msg_type` = CASE WHEN msg_type = 3 THEN 9 WHEN msg_type = 4 THEN 3 WHEN msg_type = 5 THEN 4 ELSE msg_type END;

ALTER TABLE `lar_chat_records_code` RENAME `lar_talk_records_code`;
ALTER TABLE `lar_chat_records_delete` RENAME `lar_talk_records_delete`;
ALTER TABLE `lar_chat_records_forward` RENAME `lar_talk_records_forward`;
ALTER TABLE `lar_chat_records_invite` RENAME `lar_talk_records_invite`;
ALTER TABLE `lar_chat_records_file` RENAME `lar_talk_records_file`;

# lar_users_friends 数据表同步SQL
DELETE from `lar_users_friends` where `status` = 0;
ALTER TABLE `lar_users_friends` CHANGE `user1` `user_id` int(11) unsigned DEFAULT '0' COMMENT '用户id';
ALTER TABLE `lar_users_friends` CHANGE `user2` `friend_id` int(11) unsigned DEFAULT '0' COMMENT '好友id';
ALTER TABLE `lar_users_friends` CHANGE `user1_remark` `remark` varchar(20) DEFAULT '' COMMENT '好友的备注';
ALTER TABLE `lar_users_friends` MODIFY `status` tinyint(3) unsigned DEFAULT '0' COMMENT '好友状态 [0:否;1:是]';
ALTER TABLE `lar_users_friends` MODIFY `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间';
ALTER TABLE `lar_users_friends` CHANGE `agree_time` `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间';
ALTER TABLE `lar_users_friends` DROP COLUMN `active`;
ALTER TABLE `lar_users_friends` DROP COLUMN `user2_remark`;

# lar_users_friends_apply 数据表同步SQL
ALTER TABLE `lar_users_friends_apply` CHANGE `remarks` `remark` varchar(50) DEFAULT '' COMMENT '申请备注';
ALTER TABLE `lar_users_friends_apply` DROP COLUMN `updated_at`;
ALTER TABLE `lar_users_friends_apply` DROP COLUMN `status`;

-- ----------------------------
-- 以下是新增数据表
-- ----------------------------

CREATE TABLE `lar_robots` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '机器人ID',
    `user_id` int(11) unsigned DEFAULT '0' COMMENT '关联用户ID',
    `robot_name` varchar(20) NOT NULL DEFAULT '' COMMENT '机器人名称',
    `describe` varchar(255) DEFAULT '' COMMENT '描述信息',
    `logo` varchar(255) DEFAULT '' COMMENT '机器人logo',
    `is_talk` tinyint(4) DEFAULT '0' COMMENT '可发送消息[0:否;1:是;]',
    `status` tinyint(4) unsigned DEFAULT '0' COMMENT '状态[-1:已删除;0:正常;1:已禁用;]',
    `created_at` datetime DEFAULT NULL COMMENT '创建时间',
    `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='聊天机器人表';

CREATE TABLE `lar_talk_records_vote` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '投票ID',
    `record_id` int(11) unsigned DEFAULT '0' COMMENT '消息记录ID',
    `user_id` int(11) unsigned DEFAULT '0' COMMENT '用户ID',
    `title` varchar(50) DEFAULT '' COMMENT '投票标题',
    `answer_mode` tinyint(4) unsigned DEFAULT '0' COMMENT '答题模式[0:单选;1:多选;]',
    `answer_option` json DEFAULT NULL COMMENT '答题选项',
    `answer_num` smallint(6) unsigned DEFAULT '0' COMMENT '应答人数',
    `answered_num` smallint(6) unsigned DEFAULT '0' COMMENT '已答人数',
    `status` tinyint(4) unsigned DEFAULT '0' COMMENT '投票状态[0:投票中;1:已完成;]',
    `created_at` datetime DEFAULT NULL COMMENT '创建时间',
    `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_record_id` (`record_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='聊天对话记录（投票消息表）';

CREATE TABLE `lar_talk_records_vote_answer` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '答题ID',
    `vote_id` int(11) unsigned DEFAULT '0' COMMENT '投票ID',
    `user_id` int(11) unsigned DEFAULT '0' COMMENT '用户ID',
    `option` char(1) NOT NULL DEFAULT '' COMMENT '投票选项[A、B、C 、D、E、F]',
    `created_at` datetime DEFAULT NULL COMMENT '答题时间',
    PRIMARY KEY (`id`),
    KEY `idx_vote_id_user_id` (`vote_id`,`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='聊天对话记录（投票消息统计表）';


CREATE TABLE `lar_talk_records_login` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '登录ID',
  `record_id` int(11) unsigned DEFAULT '0' COMMENT '消息记录ID',
  `user_id` int(11) unsigned DEFAULT '0' COMMENT '用户ID',
  `ip` varchar(20) NOT NULL DEFAULT '' COMMENT 'IP地址',
  `platform` varchar(20) NOT NULL DEFAULT '' COMMENT '登录平台[h5,ios,windows,mac,web]',
  `agent` varchar(255) NOT NULL DEFAULT '' COMMENT '设备信息',
  `address` varchar(100) NOT NULL DEFAULT '' COMMENT 'IP所在地',
  `reason` varchar(100) NOT NULL DEFAULT '' COMMENT '登录异常提示',
  `created_at` datetime NOT NULL COMMENT '登录时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_record_id` (`record_id`) USING BTREE,
  KEY `idx_user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='聊天对话记录（登录日志）';
