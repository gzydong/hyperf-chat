<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateRobotTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('robot', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('机器人ID');
            $table->unsignedInteger('user_id')->comment('关联用户ID');
            $table->string('robot_name', 30)->default('')->comment('机器人名称');
            $table->string('describe', 255)->default('')->comment('描述信息');
            $table->string('logo', 255)->default('')->comment('机器人logo');
            $table->unsignedTinyInteger('is_talk')->default(0)->unsigned()->comment('可发送消息[0:否;1:是;]');
            $table->unsignedTinyInteger('status')->default(0)->unsigned()->comment('状态[-1:已删除;0:正常;1:已禁用;]');
            $table->unsignedTinyInteger('type')->default(0)->unsigned()->comment('机器人类型');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->comment('聊天机器人表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('robot');
    }
}
