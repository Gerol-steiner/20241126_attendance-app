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
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // ユーザーID（申請修正の起案者）
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade'); // 外部キー：勤怠レコード
            $table->time('check_in')->nullable(); // 出勤時刻
            $table->time('check_out')->nullable(); // 退勤時刻
            $table->string('remark', 255); // 申請理由（備考：文字列、必須）
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
        Schema::dropIfExists('attendance_modifications');
    }
}
