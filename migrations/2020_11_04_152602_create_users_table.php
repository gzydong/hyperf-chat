<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->autoIncrement()->comment('用户ID');
            $table->string('mobile', 11)->default('')->comment('手机号');
            $table->string('nickname', 20)->default('')->comment('用户昵称');
            $table->string('avatar', 255)->default('')->comment('用户头像地址');
            $table->unsignedTinyInteger('gender')->default(0)->comment('用户性别[0:未知;1:男;2:女;]');
            $table->string('password', 255)->default('')->comment('用户密码');
            $table->string('motto', 100)->default('')->comment('用户座右铭');
            $table->string('email', 30)->default('')->comment('用户邮箱');
            $table->unsignedTinyInteger('is_robot')->default(0)->comment('是否机器人[0:否;1:是;]');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->unique(['mobile'], 'uk_mobile');

            $table->comment('用户信息表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
