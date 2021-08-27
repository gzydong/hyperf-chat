<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateTalkRecordsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talk_records', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('聊天记录ID');
            $table->unsignedTinyInteger('talk_type')->unsigned()->default(1)->comment('对话类型[1:私信;2:群聊;]');
            $table->unsignedTinyInteger('msg_type')->unsigned()->default(1)->comment('消息类型[1:文本消息;2:文件消息;3:会话消息;4:代码消息;5:投票消息;6:群公告;7:好友申请;8:登录通知;9:入群消息/退群消息;]');
            $table->unsignedInteger('user_id')->default(0)->comment('发送者ID（0:代表系统消息 >0: 用户ID）');
            $table->unsignedInteger('receiver_id')->default(0)->comment('接收者ID（用户ID 或 群ID）');
            $table->tinyInteger('is_revoke')->default(0)->comment('是否撤回消息[0:否;1:是]');
            $table->tinyInteger('is_mark')->default(0)->comment('是否重要消息[0:否;1:是;]');
            $table->tinyInteger('is_read')->default(0)->comment('是否已读[0:否;1:是;]');
            $table->unsignedInteger('quote_id')->default(0)->comment('引用消息ID');
            $table->text('content')->nullable(true)->charset('utf8mb4')->comment('文本消息 {@nickname@}');
            $table->string('warn_users', 200)->default('')->comment('@好友 、 多个用英文逗号 “,” 拼接 (0:代表所有人)');
            $table->dateTime('created_at')->nullable(true)->comment('创建时间');
            $table->dateTime('updated_at')->nullable(true)->comment('更新时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->index(['user_id', 'receiver_id'], 'idx_user_id_receiver_id');
            $table->comment('用户聊天记录表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talk_records');
    }
}
