<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;
class CreateChatRecordsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_records', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('聊天记录ID');
            $table->tinyInteger('source')->unsigned()->default(1)->comment('消息来源[1:好友消息;2:群聊消息]');
            $table->tinyInteger('msg_type')->unsigned()->default(1)->comment('消息类型[1:文本消息;2:文件消息;3:系统提示好友入群消息或系统提示好友退群消息;4:会话记录转发]');
            $table->unsignedInteger('user_id')->default(0)->comment('发送消息的用户ID[0:代表系统消息]');
            $table->unsignedInteger('receive_id')->default(0)->comment('接收消息的用户ID或群聊ID');
            $table->text('content')->nullable(true)->charset('utf8mb4')->comment('文本消息');
            $table->tinyInteger('is_revoke')->default(0)->comment('是否撤回消息[0:否;1:是]');
            $table->dateTime('created_at')->nullable(true)->comment('发送消息的时间');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine = 'InnoDB';

            $table->index(['user_id', 'receive_id'], 'idx_userid_receiveid');
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}chat_records` comment '用户聊天记录表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_records');
    }
}
