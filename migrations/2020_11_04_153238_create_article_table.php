<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;

class CreateArticleTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('article', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('笔记ID');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->unsignedInteger('class_id')->default(0)->comment('分类ID');
            $table->string('tags_id', 20)->default('')->comment('笔记关联标签');
            $table->string('title', 80)->default('')->charset('utf8mb4')->comment('笔记标题');
            $table->string('abstract', 200)->default('')->charset('utf8mb4')->comment('笔记摘要');
            $table->string('image', 255)->default('')->comment('笔记首图');
            $table->unsignedTinyInteger('is_asterisk')->default(0)->comment('是否星标笔记[0:否;1:是]');
            $table->unsignedTinyInteger('status')->default(1)->comment('笔记状态[1:正常;2:已删除]');
            $table->dateTime('created_at')->nullable(true)->comment('添加时间');
            $table->dateTime('updated_at')->nullable(true)->comment('最后一次更新时间');
            $table->dateTime('deleted_at')->nullable(true)->comment('笔记删除时间');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine = 'InnoDB';

            //创建索引
            $table->index(['user_id', 'class_id', 'title'], 'idx_user_id_class_id_title');
        });

        $prefix = config('databases.default.prefix');
        Db::statement("ALTER TABLE `{$prefix}article` comment '用户笔记表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article');
    }
}
