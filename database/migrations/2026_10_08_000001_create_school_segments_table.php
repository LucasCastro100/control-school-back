<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_segments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('segment_name');
            $table->string('year');
            $table->string('material_type')->nullable();
            $table->string('schedule_type')->default('semanal');
            $table->timestamps();

            $table->unique(['school_id', 'segment_name', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_segments');
    }
};