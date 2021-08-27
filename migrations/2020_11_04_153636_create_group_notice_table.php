<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateGroupNoticeTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('group_notice', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('群公告ID');
            $table->unsignedInteger('group_id')->default(0)->comment('群组ID');
            $table->unsignedInteger('creator_id')->default(0)->comment('创建者用户ID');
            $table->string('title', 50)->default('')->charset('utf8mb4')->comment('公告标题');
            $table->text('content')->charset('utf8mb4')->comment('公告内容');
            $table->tinyInteger('is_top')->default(0)->comment('是否置顶[0:否;1:是;]');
            $table->tinyInteger('is_delete')->default(0)->comment('是否删除[0:否;1:是;]');
            $table->tinyInteger('is_confirm')->default(0)->comment('是否需群成员确认公告[0:否;1:是;]');
            $table->json('confirm_users')->nullable()->comment('已确认成员');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('删除时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->index(['group_id', 'is_delete', 'is_top', 'updated_at'], 'idx_group_id_is_delete_is_top_updated_at');
            $table->comment('群组公告表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_notice');
    }
}
