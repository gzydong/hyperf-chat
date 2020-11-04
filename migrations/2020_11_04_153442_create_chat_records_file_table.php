<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;
class CreateChatRecordsFileTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_records_file', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('文件ID');
            $table->unsignedInteger('record_id')->default(0)->comment('消息记录ID');
            $table->unsignedInteger('user_id')->default(0)->comment('上传文件的用户ID');
            $table->tinyInteger('file_source')->default(1)->unsigned()->comment('文件来源[1:用户上传;2:表情包]');
            $table->tinyInteger('file_type')->default(1)->unsigned()->comment('消息类型[1:图片;2:视频;3:文件]');
            $table->tinyInteger('save_type')->default(0)->unsigned()->comment('文件保存方式（0:本地 1:第三方[阿里OOS、七牛云] ）');
            $table->string('original_name', 100)->default('')->comment('原文件名');
            $table->string('file_suffix', 10)->default('')->comment('文件后缀名');
            $table->unsignedBigInteger('file_size')->default(0)->comment('文件大小（单位字节）');
            $table->string('save_dir', 500)->default('')->comment('文件保存地址（相对地址/第三方网络地址）');
            $table->tinyInteger('is_delete')->default(0)->unsigned()->comment('文件是否已删除[0:否;1:已删除]');
            $table->dateTime('created_at')->nullable(true)->comment('创建时间');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine = 'InnoDB';

            $table->unique(['record_id'], 'idx_record_id');
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}chat_records_file` comment '用户聊天记录_文件消息表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_records_file');
    }
}
