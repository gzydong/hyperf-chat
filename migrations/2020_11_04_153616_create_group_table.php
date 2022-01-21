<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateGroupTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('group', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('群ID');
            $table->unsignedInteger('creator_id')->default(0)->comment('创建者ID(群主ID)');
            $table->string('group_name', 30)->default('')->charset('utf8mb4')->comment('群名称');
            $table->string('profile', 100)->default('')->comment('群介绍');
            $table->string('avatar', 200)->default('')->comment('群头像');
            $table->unsignedSmallInteger('max_num')->default(200)->comment('最大群成员数量');
            $table->tinyInteger('is_overt')->default(0)->comment('是否公开可见[0:否;1:是;]');
            $table->tinyInteger('is_mute')->default(0)->comment('是否全员禁言 [0:否;1:是;]，提示:不包含群主或管理员');
            $table->tinyInteger('is_dismiss')->default(0)->comment('是否已解散[0:否;1:是;]');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');
            $table->dateTime('dismissed_at')->nullable()->comment('解散时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';
            $table->comment('聊天群组表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group');
    }
}
