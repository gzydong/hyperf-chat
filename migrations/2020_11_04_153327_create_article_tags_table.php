<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateArticleTagsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('article_tag', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('笔记标签ID');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->string('tag_name', 20)->default('')->comment('标签名');
            $table->unsignedTinyInteger('sort')->default(0)->comment('排序');
            $table->dateTime('created_at')->nullable(true)->comment('创建时间');
            $table->dateTime('updated_at')->nullable(true)->comment('更新时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->index(['user_id'], 'idx_user_id');

            $table->comment('笔记标签表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_tag');
    }
}
