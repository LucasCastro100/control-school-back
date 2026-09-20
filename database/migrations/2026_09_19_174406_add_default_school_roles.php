<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roles = [
            ['name' => 'Diretor(a)', 'permissions' => ['schools']],
            ['name' => 'Coordenador(a)', 'permissions' => ['schools']],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore([
                'id' => (string) Str::uuid(),
                'name' => $role['name'],
                'permissions' => json_encode($role['permissions']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')
            ->whereIn('name', ['Diretor(a)', 'Coordenador(a)'])
            ->delete();
    }
};