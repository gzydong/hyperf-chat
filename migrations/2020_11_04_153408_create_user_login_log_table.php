<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;
class CreateUserLoginLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_login_log', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('登录日志ID');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->string('ip', 20)->comment('登录地址IP');
            $table->dateTime('created_at')->nullable(true)->comment('登录时间');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}user_login_log` comment '用户登录日志表'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_login_log');
    }
}
