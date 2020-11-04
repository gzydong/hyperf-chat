<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;
class CreateChatRecordsInviteTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_records_invite', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('入群或退群通知ID');
            $table->unsignedInteger('record_id')->default(0)->comment('消息记录ID');
            $table->tinyInteger('type')->default(1)->comment('通知类型[1:入群通知;2:自动退群;3:管理员踢群]');
            $table->unsignedInteger('operate_user_id')->default(0)->comment('操作人的用户ID(邀请人)');
            $table->string('user_ids', 255)->default('')->comment("用户ID，多个用','分割");

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine = 'InnoDB';

            $table->index(['record_id'], 'idx_recordid');
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}chat_records_invite` comment '用户聊天记录_入群或退群消息表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_records_invite');
    }
}
