<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;
class CreateChatRecordsCodeTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_records_code', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('入群或退群通知ID');
            $table->unsignedInteger('record_id')->default(0)->comment('消息记录ID');
            $table->unsignedInteger('user_id')->default(0)->comment('上传文件的用户ID');
            $table->string('code_lang', 20)->default('')->comment("代码片段类型(如：php,java,python)");
            $table->text('code')->charset('utf8mb4')->comment('代码片段内容');
            $table->dateTime('created_at')->nullable(true)->comment('创建时间');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine = 'InnoDB';

            $table->index(['record_id'], 'idx_recordid');
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}chat_records_code` comment '用户聊天记录_代码块消息表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_records_code');
    }
}
