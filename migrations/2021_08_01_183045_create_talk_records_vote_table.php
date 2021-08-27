<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateTalkRecordsVoteTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talk_records_vote', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('投票ID');
            $table->unsignedInteger('record_id')->default(0)->comment('消息记录ID');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->string('title', 50)->default('')->comment('投票标题');
            $table->unsignedInteger('answer_mode')->default(0)->comment('答题模式[0:单选;1:多选;]');
            $table->json('answer_option')->default(null)->comment('答题选项');
            $table->unsignedSmallInteger('answer_num')->default(0)->comment('应答人数');
            $table->unsignedSmallInteger('answered_num')->default(0)->comment('已答人数');
            $table->unsignedTinyInteger('status')->default(0)->comment('投票状态[0:投票中;1:已完成;]');
            $table->dateTime('created_at')->nullable(true)->comment('创建时间');
            $table->dateTime('updated_at')->nullable(true)->comment('更新时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->unique(['record_id'], 'uk_record_id');
            $table->comment('聊天对话记录（投票消息表）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talk_records_vote');
    }
}
