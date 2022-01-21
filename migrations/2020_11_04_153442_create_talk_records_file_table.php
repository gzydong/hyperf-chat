<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateTalkRecordsFileTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talk_records_file', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('文件ID');
            $table->unsignedInteger('record_id')->default(0)->comment('消息记录ID');
            $table->unsignedInteger('user_id')->default(0)->comment('上传文件的用户ID');
            $table->tinyInteger('source')->default(1)->unsigned()->comment('文件来源[1:用户上传;2:表情包]');
            $table->tinyInteger('type')->default(1)->unsigned()->comment('消息类型[1:图片;2:视频;3:文件]');
            $table->tinyInteger('drive')->default(1)->unsigned()->comment('驱动类型[1:local;2:cos;]');
            $table->string('original_name', 100)->default('')->comment('原文件名');
            $table->string('suffix', 10)->default('')->comment('文件后缀名');
            $table->unsignedBigInteger('size')->default(0)->comment('文件大小（单位字节）');
            $table->string('path', 300)->default('')->comment('文件地址(相对地址)');
            $table->string('url', 300)->default('')->comment('网络地址(公开文件地址)');
            $table->tinyInteger('is_delete')->default(0)->unsigned()->comment('文件是否已删除[0:否;1:已删除]');
            $table->dateTime('created_at')->nullable(true)->comment('创建时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->unique(['record_id'], 'uk_record_id');
            $table->comment('用户聊天记录_文件消息表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talk_records_file');
    }
}
