<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 40)->unique()->nullable(false)->default('')->comment('用户名');
            $table->string('password')->nullable(false)->default('')->comment('密码');
            $table->string('mobile', 12)->unique()->nullable(false)->default('')->comment('手机号');
            $table->string('email', 100)->nullable(false)->default('')->comment('邮箱');
            $table->unsignedInteger('created_at')->nullable(false)->default(0)->comment('创建时间');
            $table->unsignedBigInteger('created_by')->nullable(false)->default(0)->comment('创建人');
            $table->unsignedInteger('updated_at')->nullable(false)->default(0)->comment('更新时间');
            $table->unsignedBigInteger('updated_by')->nullable(false)->default(0)->comment('更新人');
            $table->unsignedTinyInteger('deleteflag')->nullable(false)->default(0)->comment('是否删除');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
