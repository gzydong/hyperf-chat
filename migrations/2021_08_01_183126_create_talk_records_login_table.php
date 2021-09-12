<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateTalkRecordsLoginTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talk_records_login', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('登录ID');
            $table->unsignedInteger('record_id')->default(0)->comment('消息记录ID');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->string('platform', 20)->default('')->comment('登录平台[h5,ios,windows,mac,web]');
            $table->string('ip', 20)->default('')->comment('IP地址');
            $table->string('agent', 300)->default('')->comment('设备信息');
            $table->string('address', 100)->default('')->comment('IP所在地');
            $table->string('reason', 100)->default('')->comment('登录异常提示');
            $table->dateTime('created_at')->nullable(true)->comment('创建时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->unique(['record_id'], 'uk_record_id');
            $table->index(['user_id', 'ip'], 'idx_user_id_ip');

            $table->comment('聊天对话记录（登录日志）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talk_records_login');
    }
}
