<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceModificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade'); // 対応する勤怠レコード
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // 修正申請を行うユーザー
            $table->time('check_in'); // 修正申請の出勤時刻
            $table->time('check_out'); // 修正申請の退勤時刻
            $table->time('break_start'); // 修正申請の休憩開始時刻
            $table->time('break_end'); // 修正申請の休憩終了時刻
            $table->text('remarks'); // 修正理由
            $table->foreignId('approved_by')->nullable()->constrained('users'); // 承認者の管理者
            $table->timestamp('approved_at')->nullable(); // 承認日時
            $table->timestamps(); // 作成日時と更新日時
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_modifications');
    }
}
