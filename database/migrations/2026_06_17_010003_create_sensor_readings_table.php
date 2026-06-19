<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pond_id')->constrained('ponds')->onDelete('cascade');
            $table->float('water_level');
            $table->float('ph_value');
            $table->float('flow_rate');
            $table->float('distance_cm');
            $table->timestamp('read_at');
            $table->timestamps();

            $table->index(['pond_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
