<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateEmoticonTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emoticon', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('表情分组ID');
            $table->string('name', 50)->default('')->nullable(false)->comment('分组名称');
            $table->string('icon', 255)->default('')->comment('分组图标');
            $table->unsignedTinyInteger('status')->default(0)->comment('分组状态[-1:已删除;0:正常;1:已禁用;]');
            $table->dateTime('created_at')->nullable()->comment('创建时间');
            $table->dateTime('updated_at')->nullable()->comment('更新时间');

            $table->charset   = 'utf8';
            $table->collation = 'utf8_general_ci';

            $table->unique(['name'], 'uk_name');
            $table->comment('表情包');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emoticon');
    }
}
