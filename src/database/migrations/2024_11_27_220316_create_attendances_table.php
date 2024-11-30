<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // ユーザーID
            $table->date('date'); // 勤務日
            $table->time('check_in')->nullable(); // 出勤時刻
            $table->time('check_out')->nullable(); // 退勤時刻
            $table->time('break_start')->nullable(); // 休憩開始時刻
            $table->time('break_end')->nullable(); // 休憩終了時刻
            $table->string('remarks', 255)->nullable(); // 備考
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}