<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;
class CreateUsersGroupMemberTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users_group_member', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('自增ID');
            $table->unsignedInteger('group_id')->default(0)->comment('群ID');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->tinyInteger('group_owner')->nullable()->comment('是否为群主[0:否;1:是]');
            $table->tinyInteger('status')->default(0)->comment('退群状态[0:正常状态;1:已退群]');
            $table->string('visit_card', 20)->default('')->comment('用户群名片');
            $table->dateTime('created_at')->nullable()->comment('入群时间');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine = 'InnoDB';

            $table->index(['group_id', 'status'], 'idx_group_id_status');
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}users_group_member` comment '群聊成员'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_group_member');
    }
}
