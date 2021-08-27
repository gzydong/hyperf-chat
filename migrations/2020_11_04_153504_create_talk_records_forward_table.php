<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateTalkRecordsForwardTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talk_records_forward', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('合并转发ID');
            $table->unsignedInteger('record_id')->default(0)->comment('消息记录ID');
            $table->unsignedInteger('user_id')->default(0)->comment('转发用户ID');
            $table->string('records_id', 255)->default('')->comment("转发的聊天记录ID，多个用','分割");
            $table->json('text')->default(null)->comment('记录快照');
            $table->dateTime('created_at')->nullable(true)->comment('转发时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->index(['user_id', 'records_id'], 'idx_user_id_records_id');
            $table->comment('用户聊天记录_转发信息表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talk_records_forward');
    }
}
