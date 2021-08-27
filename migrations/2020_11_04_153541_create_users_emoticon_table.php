<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateUsersEmoticonTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users_emoticon', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('表情包收藏ID');
            $table->unsignedInteger('user_id')->default(0)->comment('用户ID');
            $table->string('emoticon_ids', 255)->default('')->comment('表情包ID');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->unique(['user_id'], 'uk_user_id');
            $table->comment('用户收藏表情包');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_emoticon');
    }
}
