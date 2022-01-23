<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateGroupMemberTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('group_member', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('群成员ID');
            $table->unsignedInteger('group_id')->default(0)->comment('群ID');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->tinyInteger('leader')->comment('成员属性[0:普通成员;1:管理员;2:群主;]');
            $table->tinyInteger('is_mute')->default(0)->comment('是否禁言[0:否;1:是;]');
            $table->tinyInteger('is_quit')->default(0)->comment('是否退群[0:否;1:是;]');
            $table->string('user_card', 20)->default('')->comment('群名片');
            $table->dateTime('created_at')->nullable()->comment('入群时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('deleted_at')->nullable()->comment('退群时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->unique(['group_id', 'user_id'], 'uk_group_id_user_id');
            $table->index(['user_id'], 'idx_user_id');
            $table->comment('聊天群组成员表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_member');
    }
}
