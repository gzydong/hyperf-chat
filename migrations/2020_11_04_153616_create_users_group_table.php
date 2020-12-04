<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;

class CreateUsersGroupTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users_group', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('群ID');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->string('group_name', 30)->default('')->charset('utf8mb4')->comment('群名称');
            $table->string('group_profile', 100)->default('')->comment('群介绍');
            $table->tinyInteger('status')->default(0)->comment('群状态[0:正常;1:已解散]');
            $table->string('avatar', 255)->default('')->comment('群头像');

            //预留字段
            //$table->string('invite_mode', 255)->default(0)->comment('邀请方式[0:全员邀请;1:全员邀请需群主确认;2:仅群主可邀请;]');

            $table->dateTime('created_at')->nullable()->comment('创建时间');
        });

        // 预留表(后期邀请入群需同意后进入)
        /*
        Schema::create('users_group_invite', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('自增ID');
            $table->unsignedInteger('record_id')->default(0)->comment('消息记录ID');
            $table->unsignedInteger('group_id')->default(0)->comment('群ID');
            $table->unsignedInteger('user_id', 30)->default(0)->comment('用户ID');
            $table->tinyInteger('status')->default(0)->comment('邀请状态[0:等待同意;1:已同意;2:拒绝邀请]');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
        });
        */

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}users_group` comment '用户聊天群'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_group');
    }
}
