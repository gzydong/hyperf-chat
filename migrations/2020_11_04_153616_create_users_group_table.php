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
            $table->dateTime('created_at')->nullable()->comment('创建时间');
        });

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
