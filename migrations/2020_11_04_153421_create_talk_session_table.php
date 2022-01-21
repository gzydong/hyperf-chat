<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateTalkSessionTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talk_session', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('聊天列表ID');
            $table->unsignedTinyInteger('talk_type')->default(1)->comment('聊天类型[1:私信;2:群聊;]');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->unsignedInteger('receiver_id')->default(0)->comment('接收者ID（用户ID 或 群ID）');
            $table->unsignedTinyInteger('is_top')->default(0)->comment('是否置顶[0:否;1:是]');
            $table->unsignedTinyInteger('is_robot')->default(0)->comment('是否机器人[0:否;1:是;]');
            $table->unsignedTinyInteger('is_delete')->default(0)->comment('是否删除[0:否;1:是;]');
            $table->unsignedTinyInteger('is_disturb')->default(0)->comment('消息免打扰[0:否;1:是;]');
            $table->dateTime('created_at')->nullable(true)->comment('创建时间');
            $table->dateTime('updated_at')->nullable(true)->comment('更新时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->unique(['user_id', 'receiver_id', 'talk_type'], 'uk_user_id_receiver_id_talk_type');
            $table->comment('用户聊天列表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talk_session');
    }
}
