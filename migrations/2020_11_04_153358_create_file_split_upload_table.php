<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;
class CreateFileSplitUploadTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('file_split_upload', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('临时文件ID');
            $table->unsignedTinyInteger('file_type')->default(2)->comment('数据类型[1:合并文件;2:拆分文件]');
            $table->unsignedInteger('user_id')->default(0)->comment('上传的用户ID');
            $table->string('hash_name', 30)->default('')->comment('临时文件hash名');
            $table->string('original_name', 100)->default('')->comment('原文件名');
            $table->unsignedTinyInteger('split_index')->default(0)->comment('当前索引块');
            $table->unsignedTinyInteger('split_num')->default(0)->comment('总上传索引块');
            $table->string('save_dir', 255)->default('')->comment('文件的临时保存路径');
            $table->string('file_ext', 10)->default('')->comment('文件后缀名');
            $table->unsignedInteger('file_size')->default(0)->comment('临时文件大小');
            $table->unsignedTinyInteger('is_delete')->default(0)->comment('文件是否已被删除[0:否;1:是]');
            $table->unsignedInteger('upload_at')->nullable(true)->comment('文件上传时间');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->index(['user_id', 'hash_name'], 'idx_user_id_hash_name');
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}file_split_upload` comment '文件拆分上传'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_split_upload');
    }
}
