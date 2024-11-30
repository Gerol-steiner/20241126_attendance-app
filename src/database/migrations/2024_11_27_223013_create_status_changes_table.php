<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatusChangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade'); // 対応する勤務記録
            $table->foreignId('status_id')->constrained('statuses')->onDelete('restrict'); // 勤務ステータス
            $table->timestamp('changed_at'); // ステータス変更日時
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
        Schema::dropIfExists('status_changes');
    }
}
