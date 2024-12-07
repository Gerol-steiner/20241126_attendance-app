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
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete(); // 外部キー：勤怠レコード（nullable）
            $table->date('date')->nullable(); // 修正対象の勤務日
            $table->time('check_in')->nullable(); // 出勤時刻
            $table->time('check_out')->nullable(); // 退勤時刻
            $table->string('remark', 255); // 申請理由（備考：文字列、必須）
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete(); // 勤怠対象ユーザーID（nullable）
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete(); // 修正案を起案したユーザーID（nullable）
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // 承認者（管理者ID、nullable）
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
        Schema::table('attendance_modifications', function (Blueprint $table) {
            // 外部キー制約を削除
            $table->dropForeign(['attendance_id']);
            $table->dropForeign(['staff_id']);
            $table->dropForeign(['requested_by']);
            $table->dropForeign(['approved_by']);
        });

        // テーブルを削除
        Schema::dropIfExists('attendance_modifications');
    }
}
