<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateTalkRecordsDeleteTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talk_records_delete', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('聊天删除记录ID');
            $table->unsignedInteger('record_id')->default(0)->comment('聊天记录ID');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->dateTime('created_at')->nullable(true)->comment('删除时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->unique(['record_id', 'user_id'], 'uk_record_id_user_id');
            $table->comment('用户聊天记录_删除记录表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talk_records_delete');
    }
}
