<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakTimeModificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('break_time_modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_modification_id')->constrained()->onDelete('cascade'); // 外部キー：勤怠修正レコード
            $table->time('break_start'); // 休憩開始時刻
            $table->time('break_end')->nullable(); // 休憩終了時刻
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('break_time_modifications');
    }
}
