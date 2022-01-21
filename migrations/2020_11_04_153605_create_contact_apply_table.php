<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateContactApplyTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contact_apply', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('申请ID');
            $table->unsignedInteger('user_id')->default(0)->comment('申请人ID');
            $table->unsignedInteger('friend_id')->default(0)->comment('好友ID');
            $table->string('remark', 50)->default('')->comment('备注信息');
            $table->dateTime('created_at')->nullable()->comment('申请时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->engine    = 'InnoDB';

            $table->index(['user_id'], 'idx_user_id');
            $table->index(['friend_id'], 'idx_friend_id');
            $table->comment('用户添加好友申请表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_apply');
    }
}
