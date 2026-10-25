<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('request_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->datetime('request_clock_in');
            $table->datetime('request_clock_out')->nullable();
            $table->string('request_note', 255);
            $table->tinyInteger('status')->comment('0:申請中,1:承認済み');
            $table->datetime('approved_at')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_attendances');
    }
};
