<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateSplitUploadTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('split_upload', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('临时文件ID');
            $table->unsignedTinyInteger('type')->default(2)->comment('数据类型[1:合并文件;2:拆分文件]');
            $table->unsignedInteger('drive')->unsigned()->default(1)->comment('文件驱动[1:local;2:cos;]');
            $table->unsignedInteger('user_id')->default(0)->comment('上传的用户ID');
            $table->string('upload_id', 100)->default('')->comment('上传文件ID');
            $table->string('original_name', 100)->default('')->comment('原文件名');
            $table->unsignedTinyInteger('split_index')->default(0)->comment('当前索引块');
            $table->unsignedTinyInteger('split_num')->default(0)->comment('总上传索引块');
            $table->string('path', 255)->default('')->comment('保存路径');
            $table->string('file_ext', 10)->default('')->comment('文件后缀名');
            $table->unsignedInteger('file_size')->default(0)->comment('临时文件大小');
            $table->unsignedTinyInteger('is_delete')->default(0)->comment('文件是否已被删除[0:否;1:是]');
            $table->json('attr')->nullable(false)->comment('额外参数Json');
            $table->dateTime('created_at')->nullable(true)->comment('创建时间');
            $table->dateTime('updated_at')->nullable(true)->comment('更新时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->index(['user_id', 'upload_id'], 'idx_user_id_upload_id');

            $table->comment('文件拆分上传');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('split_upload');
    }
}
