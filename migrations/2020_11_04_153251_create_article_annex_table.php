<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

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
            $table->unsignedInteger('drive')->unsigned()->default(1)->comment('文件驱动[1:local;2:cos;]');
            $table->string('suffix', 10)->default('')->comment('文件后缀名');
            $table->unsignedBigInteger('size')->default(0)->comment('文件大小（单位字节）');
            $table->string('path', 500)->nullable(false)->default('')->comment('文件保存地址（相对地址）');
            $table->string('original_name', 100)->nullable(false)->default('')->comment('原文件名');
            $table->unsignedTinyInteger('status')->default(1)->comment('附件状态[1:正常;2:已删除]');
            $table->dateTime('created_at')->nullable(true)->comment('创建时间');
            $table->dateTime('updated_at')->nullable(true)->comment('更新时间');
            $table->dateTime('deleted_at')->nullable(true)->comment('删除时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->index(['user_id', 'article_id'], 'idx_user_id_article_id');

            $table->comment('笔记附件信息表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_annex');
    }
}
