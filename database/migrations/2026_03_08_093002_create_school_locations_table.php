<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('school_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Cth: "Kampus 1", "Kampus 2"
            $table->string('latitude');
            $table->string('longitude');
            $table->integer('radius'); // dalam meter, misal: 150
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_locations');
    }
};
