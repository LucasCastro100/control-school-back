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
        Schema::create('agenda_orientadores', function (Blueprint $table) {
            $table->foreignUuid('agenda_id')->constrained('agenda')->cascadeOnDelete();
            $table->foreignUuid('orientador_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['agenda_id', 'orientador_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_orientadores');
    }
};
