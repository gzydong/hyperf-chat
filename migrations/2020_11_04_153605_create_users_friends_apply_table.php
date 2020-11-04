<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;
class CreateUsersFriendsApplyTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users_friends_apply', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('申请ID');
            $table->unsignedInteger('user_id')->default(0)->comment('申请人ID');
            $table->unsignedInteger('friend_id')->default(0)->comment('被申请人');
            $table->unsignedTinyInteger('status')->default(0)->comment('申请状态[0:等待处理;1:已同意]');
            $table->string('remarks', 50)->default('')->comment('申请人备注信息');
            $table->dateTime('created_at')->nullable()->comment('申请时间');
            $table->dateTime('updated_at')->nullable()->comment('处理时间');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine = 'InnoDB';

            $table->index(['user_id'], 'idx_user_id');
            $table->index(['friend_id'], 'idx_friend_id');
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}users_friends_apply` comment '用户添加好友申请表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_friends_apply');
    }
}
