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
        Schema::create('request_rests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_attendance_id')->constrained()->onDelete('cascade');
            $table->datetime('request_rest_start');
            $table->datetime('request_rest_end');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_rests');
    }
};
