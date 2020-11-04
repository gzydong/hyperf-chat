<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;
class CreateUsersChatListTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users_chat_list', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('聊天列表ID');
            $table->unsignedTinyInteger('type')->default(1)->comment('聊天类型[1:好友;2:群聊]');
            $table->unsignedInteger('uid')->default(0)->comment('用户ID');
            $table->unsignedInteger('friend_id')->default(0)->comment('朋友的用户ID');
            $table->unsignedInteger('group_id')->default(0)->comment('聊天分组ID');
            $table->unsignedInteger('status')->default(1)->default(1)->comment('状态[0:已删除;1:正常]');
            $table->unsignedTinyInteger('is_top')->default(0)->comment('是否置顶[0:否;1:是]');
            $table->unsignedTinyInteger('not_disturb')->default(0)->comment('是否消息免打扰[0:否;1:是]');
            $table->dateTime('created_at')->nullable(true)->comment('创建时间');
            $table->dateTime('updated_at')->nullable(true)->comment('更新时间');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine = 'InnoDB';

            $table->index(['uid', 'friend_id', 'group_id', 'type'], 'idx_uid_type_friend_id_group_id');
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}users_chat_list` comment '用户聊天列表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_chat_list');
    }
}
