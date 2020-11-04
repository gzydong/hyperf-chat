<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;
class CreateUsersGroupNoticeTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users_group_notice', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('公告ID');
            $table->unsignedInteger('group_id')->default(0)->comment('群ID');
            $table->unsignedInteger('user_id')->default(0)->comment('创建者用户ID');
            $table->string('title', 30)->default('')->charset('utf8mb4')->comment('公告标题');
            $table->text('content')->charset('utf8mb4')->comment('公告内容');
            $table->tinyInteger('is_delete')->default(0)->comment('是否删除[0:否;1:已删除]');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine = 'InnoDB';

            $table->index(['group_id'], 'idx_group_id');
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}users_group_notice` comment '群组公告表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_group_notice');
    }
}
