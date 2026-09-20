<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NapCapacity
{
    public const LIMIT = 2;

    /**
     * Quantidade de usuários atualmente vinculados a uma escola + NAP.
     */
    public static function usedCount(string $schoolId, ?string $nap): int
    {
        if ($nap === null || $nap === '') {
            return 0;
        }

        return DB::table('user_schools')
            ->where('school_id', $schoolId)
            ->where('nap', $nap)
            ->count();
    }

    /**
     * Garante que uma nova (escola, NAP) não ultrapasse o limite.
     *
     * @param  string|null  $excludingUserId  Usuário que já é dono do vínculo (atualização/re-link).
     */
    public static function assertWithinLimit(string $schoolId, ?string $nap, ?string $excludingUserId = null): void
    {
        if ($nap === null || $nap === '') {
            return;
        }

        if ($excludingUserId !== null && self::owns($schoolId, $nap, $excludingUserId)) {
            return;
        }

        if (self::usedCount($schoolId, $nap) >= self::LIMIT) {
            throw ValidationException::withMessages([
                'nap' => "Limite de ".self::LIMIT." usuários por NAP atingido (NAP {$nap}).",
            ]);
        }
    }

    /**
     * Valida o plano final de vínculos por NAP (replace no lado da escola).
     *
     * @param  array<string, array{school_id: string, nap?: string|null}>  $links
     */
    public static function assertFinalCountsWithinLimit(array $links): void
    {
        $counts = [];

        foreach ($links as $link) {
            $nap = $link['nap'] ?? null;
            if ($nap === null || $nap === '') {
                continue;
            }

            $key = $link['school_id'].'|'.$nap;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        foreach ($counts as $key => $count) {
            if ($count > self::LIMIT) {
                $nap = explode('|', $key)[1];
                throw ValidationException::withMessages([
                    'nap' => "Limite de ".self::LIMIT." usuários por NAP atingido (NAP {$nap}).",
                ]);
            }
        }
    }

    protected static function owns(string $schoolId, string $nap, string $userId): bool
    {
        return DB::table('user_schools')
            ->where('user_id', $userId)
            ->where('school_id', $schoolId)
            ->where('nap', $nap)
            ->exists();
    }
}