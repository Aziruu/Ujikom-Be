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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->date('date'); // 2026-01-28
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->enum('method', ['rfid', 'face', 'qrcode', 'manual'])->nullable();
            $table->enum('status', ['hadir', 'telat', 'izin', 'sakit', 'alpa'])->default('alpa');
            $table->integer('late_duration')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
