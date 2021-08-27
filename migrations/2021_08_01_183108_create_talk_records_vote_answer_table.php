<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateTalkRecordsVoteAnswerTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('talk_records_vote_answer', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('答题ID');
            $table->unsignedInteger('vote_id')->default(0)->comment('投票ID');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->char('option', 1)->default('')->comment('投票选项[A、B、C 、D、E、F]');
            $table->dateTime('created_at')->comment('答题时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->index(['vote_id', 'user_id'], 'idx_vote_id_user_id');
            $table->comment('聊天对话记录（投票消息统计表）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talk_records_vote_answer');
    }
}
