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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->uuid('guru_id')->unique()->nullable();
            $table->string('nip')->unique()->nullable();;
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('password')->nullable();
            $table->string('rfid_uid')->unique()->nullable();
            $table->text('face_descriptor')->nullable();
            $table->string('photo_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
