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
        Schema::table('user_schools', function (Blueprint $table) {
            $table->string('nap')->nullable()->after('school_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_schools', function (Blueprint $table) {
            if (Schema::hasColumn('user_schools', 'nap')) {
                $table->dropColumn('nap');
            }
        });
    }
};