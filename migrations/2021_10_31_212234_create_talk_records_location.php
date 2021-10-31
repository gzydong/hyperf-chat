<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateTalkRecordsLocation extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talk_records_location', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('自增ID');
            $table->unsignedInteger('record_id')->default(0)->comment('消息记录ID');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->string('longitude', 20)->default('')->comment('经度');
            $table->string('latitude', 20)->default('')->comment('纬度');
            $table->dateTime('created_at')->nullable(true)->comment('创建时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->unique(['record_id'], 'uk_record_id');
            $table->index(['user_id'], 'idx_user_id');

            $table->comment('聊天对话记录（位置消息）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talk_records_location');
    }
}
