# lar_group
ALTER TABLE lar_users_group RENAME lar_group;
ALTER TABLE lar_group CHANGE `user_id` `creator_id` int(11) unsigned DEFAULT '0' COMMENT '创建者ID(群主ID)';
ALTER TABLE lar_group CHANGE `status` `is_dismiss` tinyint(4) unsigned DEFAULT '0' COMMENT '是否已解散[0:否;1:是;]';
ALTER TABLE lar_group CHANGE `group_profile` `profile` varchar(100) DEFAULT '' COMMENT '群介绍';
ALTER TABLE lar_group ADD `max_num` smallint(5) unsigned DEFAULT '200' COMMENT '最大群成员数量';
ALTER TABLE lar_group ADD `is_overt` tinyint(4) unsigned DEFAULT '0' COMMENT '是否公开可见[0:否;1:是;]';
ALTER TABLE lar_group ADD `is_mute` tinyint(4) unsigned DEFAULT '0' COMMENT '是否全员禁言 [0:否;1:是;]，提示:不包含群主或管理员';
ALTER TABLE lar_group ADD `dismissed_at` datetime DEFAULT NULL COMMENT '解散时间';

# ------------------

# lar_group_member
ALTER TABLE lar_users_group_member RENAME lar_group_member;
ALTER TABLE lar_group_member MODIFY `group_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '群组ID';
ALTER TABLE lar_group_member MODIFY `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID';
ALTER TABLE lar_group_member CHANGE `group_owner` `leader` tinyint(4) unsigned DEFAULT '0' COMMENT '成员属性[0:普通成员;1:管理员;2:群主;]';
ALTER TABLE lar_group_member CHANGE `status` `is_quit` tinyint(4) DEFAULT '0' COMMENT '是否退群[0:否;1:是;]';
ALTER TABLE lar_group_member CHANGE `visit_card` `user_card` varchar(20) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '群名片';
ALTER TABLE lar_group_member ADD `is_mute` tinyint(4) unsigned DEFAULT '0' COMMENT '是否禁言[0:否;1:是;]';
ALTER TABLE lar_group_member ADD `deleted_at` datetime DEFAULT NULL COMMENT '退群时间';
update lar_group_member set leader = 2 where leader = 1;

# ------------------

# lar_group_notice
ALTER TABLE lar_users_group_notice RENAME lar_group_notice;
ALTER TABLE lar_group_notice MODIFY `group_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '群组ID';
ALTER TABLE lar_group_notice CHANGE `user_id` `creator_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建者用户ID';
ALTER TABLE lar_group_notice MODIFY `title` varchar(50) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '公告标题';
ALTER TABLE lar_group_notice ADD `is_top` tinyint(4) unsigned DEFAULT '0' COMMENT '是否置顶[0:否;1:是;]';
ALTER TABLE lar_group_notice MODIFY `is_delete` tinyint(4) unsigned DEFAULT '0' COMMENT '是否删除[0:否;1:是;]';
ALTER TABLE lar_group_notice ADD `is_confirm` tinyint(4) DEFAULT '0' COMMENT '是否需群成员确认公告[0:否;1:是;]';
ALTER TABLE lar_group_notice ADD `confirm_users` json DEFAULT NULL COMMENT '已确认成员';
