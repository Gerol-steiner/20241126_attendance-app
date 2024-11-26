<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->id(); // 主キー
            $table->string('name'); // 名前
            $table->string('email')->unique(); // メールアドレス（ユニーク）
            $table->string('password'); // パスワード
            $table->timestamp('email_verified_at')->nullable(); // メール認証
            $table->tinyInteger('is_admin')->default(0); // is_adminカラム（デフォルトは0）
            $table->timestamps(); // created_at と updated_at

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
