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
        Schema::table('agenda', function (Blueprint $table) {
            $table->boolean('registrar_mundoz')->default(false);
            $table->string('escola')->nullable();
            $table->string('ano')->nullable();
            $table->string('tipo')->nullable();
            $table->string('confirmado_por')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agenda', function (Blueprint $table) {
            $table->dropColumn(['registrar_mundoz', 'escola', 'ano', 'tipo', 'confirmado_por']);
        });
    }
};