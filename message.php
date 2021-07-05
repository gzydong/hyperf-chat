<?php

// 对话的消息推送协议
$talk_message = [
    'sender_id'   => 0, //发送者ID
    'receiver_id' => 0, //接收者ID
    'talk_type'   => 0, //对话类型[1:私聊;2:群聊;]
    'data'        => [
        "id"           => 0, // 消息记录ID
        "talk_type"    => 1, // 对话来源
        "msg_type"     => 1, // 消息类型
        "user_id"      => 0, // 发送者用户ID
        "receiver_id"  => 0, // 接收者ID
        "nickname"     => '',// 用户昵称
        "avatar"       => '',// 用户头像
        "group_name"   => '',// 群组名称
        "group_avatar" => '',// 群组头像
        "file"         => [],
        "code_block"   => [],
        "forward"      => [],
        "invite"       => [],
        "content"      => '',// 文本消息
        "created_at"   => '',
        "is_revoke"    => 0, // 消息是否撤销
    ]
];

// 撤销聊天消息推送协议
$revoke_talk_message = [
    'talk_type'   => 0,//对话类型
    'sender_id'   => 0,//发送者ID
    'receiver_id' => 0,//接收者ID
    'record_id'   => 0,//撤销的记录
];

// 好友在线状态通知消息推送协议
$online_status_message = [
    'user_id' => 0,//用户ID
    'status'  => 0,//在线状态[0:离线;1:在线;]
];

// 键盘输入事件消息推送协议
$keyboard_message = [
    'sender_id'   => 0,
    'receiver_id' => 0,
];

// 好友申请消息推送协议
$friend_apply_message = [
    'sender_id'   => 0, //发送者ID
    'receiver_id' => 0, //接收者ID
    'remark'      => '',//申请备注
    'friend'      => [
        'user_id'  => 0,
        'avatar'   => '',
        'nickname' => '',
        'mobile'   => '',
    ]
];

// 好友申请回调消息推送协议
$friend_apply_callback_message = [
    'sender_id'   => 0, //发送者ID
    'receiver_id' => 0, //接收者ID
    'status'      => 0, //处理备注[0:未处理;1:已同意;2:已拒绝;]
    'remark'      => '',//处理备注
    'friend'      => [
        'user_id'  => 0,
        'avatar'   => '',
        'nickname' => '',
        'mobile'   => '',
    ]
];

// ACK已读消息推送协议
$read_message = [
    'sender_id'   => 0, //发送者ID
    'receiver_id' => 0, //接收者ID
    'msg_id'      => 0  //已读消息ID
];
