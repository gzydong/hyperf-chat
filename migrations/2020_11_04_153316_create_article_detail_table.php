<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;
class CreateArticleDetailTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('article_detail', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('笔记详情ID');
            $table->unsignedInteger('article_id')->nullable(false)->comment('笔记ID');
            $table->longtext('md_content')->charset('utf8mb4')->comment('Markdown 内容');
            $table->longtext('content')->charset('utf8mb4')->comment('Markdown 解析HTML内容');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine = 'InnoDB';

            $table->unique('article_id', 'unique_article_id');
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}article_detail` comment '笔记详情表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_detail');
    }
}
