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
        Schema::table('users', function (Blueprint $table) {
            $table->string('mundoz_user')->nullable()->after('role_id');
            $table->string('mundoz_password')->nullable()->after('mundoz_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'mundoz_password')) {
                $table->dropColumn('mundoz_password');
            }
            if (Schema::hasColumn('users', 'mundoz_user')) {
                $table->dropColumn('mundoz_user');
            }
        });
    }
};