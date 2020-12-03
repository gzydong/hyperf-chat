<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;

class CreateUsersFriendsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users_friends', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('关系ID');
            $table->unsignedInteger('user1')->default(0)->comment('用户1(user1 一定比 user2 小)');
            $table->unsignedInteger('user2')->default(0)->comment('用户2(user1 一定比 user2 小)');
            $table->string('user1_remark', 20)->default('')->comment('好友备注');
            $table->string('user2_remark', 20)->default('')->comment('好友备注');
            $table->unsignedTinyInteger('active')->default(1)->default(1)->comment('主动邀请方[1:user1;2:user2]');
            $table->unsignedTinyInteger('status')->default(1)->comment('好友状态[0:已解除好友关系;1:好友状态]');
            $table->dateTime('agree_time')->comment('成为好友时间');
            $table->dateTime('created_at')->nullable(true)->comment('创建时间');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine = 'InnoDB';

            $table->index(['user1', 'user2'], 'idx_user1_user2');
            $table->index(['user2', 'user1'], 'idx_user2_user1');
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}users_friends` comment '用户好友关系表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_friends');
    }
}
