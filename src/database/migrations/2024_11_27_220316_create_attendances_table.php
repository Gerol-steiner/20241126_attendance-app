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
            //以下は修正時に使用されるカラム
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete(); // 修正申請の起案者ID
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // 修正申請の承認者ID
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
