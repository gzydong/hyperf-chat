<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;

class CreateArticleAnnexTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('article_annex', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true)->comment('文件ID');
            $table->unsignedInteger('user_id')->unsigned()->comment('上传文件的用户ID');
            $table->unsignedInteger('article_id')->default(0)->comment('笔记ID');
            $table->string('file_suffix', 10)->default('')->comment('文件后缀名');
            $table->bigInteger('file_size')->default(0)->unsigned()->comment('文件大小（单位字节）');
            $table->string('save_dir', 500)->nullable()->comment('文件保存地址（相对地址）');
            $table->string('original_name', 100)->nullable()->comment('原文件名');
            $table->tinyInteger('status')->default(1)->unsigned()->comment('附件状态[1:正常;2:已删除]');
            $table->dateTime('created_at')->nullable(true)->comment('附件上传时间');
            $table->dateTime('deleted_at')->nullable(true)->comment('附件删除时间');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine = 'InnoDB';

            $table->index(['user_id', 'article_id'], 'idx_user_id_article_id');
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}article_annex` comment '笔记附件信息表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_annex');
    }
}
