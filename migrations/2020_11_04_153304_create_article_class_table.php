<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;
class CreateArticleClassTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('article_class', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('笔记分类ID');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->string('class_name', 20)->default('')->comment('分类名');
            $table->unsignedTinyInteger('sort')->default(0)->comment('排序');
            $table->unsignedTinyInteger('is_default')->default(0)->comment('默认分类[1:是;0:不是]');
            $table->unsignedInteger('created_at')->nullable(true)->default(0)->comment('创建时间');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine = 'InnoDB';

            $table->index(['user_id', 'sort'], 'idx_user_id_sort');
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}article_class` comment '笔记分类表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_class');
    }
}
