<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;
class CreateEmoticonTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('emoticon', function (Blueprint $table) {
            $table->unsignedInteger('id', true)->comment('表情分组ID');
            $table->string('name', 100)->default('')->comment('表情分组名称');
            $table->string('url', 255)->default('')->comment('图片地址');
            $table->unsignedInteger('created_at')->nullable(true)->default(0)->comment('创建时间');

            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
        });

        $prefix = config('databases.default.prefix');
        DB::statement("ALTER TABLE `{$prefix}emoticon` comment '表情包'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emoticon');
    }
}
