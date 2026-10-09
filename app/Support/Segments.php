<?php

namespace App\Support;

use App\Models\SchoolClass;

class Segments
{
    public const LABELS = [
        'Educação Infantil',
        'Fund. I 1º-2º',
        'Fund. I 3º-5º',
        'Fund. II 6º-9º',
        'Ensino Médio',
    ];

    private const YEARS_BY_LABEL = [
        'Educação Infantil' => ['Infantil 3 anos', 'Infantil 4 anos', 'Infantil 5 anos'],
        'Fund. I 1º-2º' => ['1 ano', '2 ano'],
        'Fund. I 3º-5º' => ['3 ano', '4 ano', '5 ano'],
        'Fund. II 6º-9º' => ['6 ano', '7 ano', '8 ano', '9 ano'],
        'Ensino Médio' => ['1 ano ensino médio', '2 ano ensino médio', '3 ano ensino médio'],
    ];

    private const LEGACY_NAP_SINGLE = [
        'NAP 1' => 'Educação Infantil',
        'NAP 3' => 'Fund. II 6º-9º',
        'NAP 4' => 'Ensino Médio',
    ];

    public static function yearsForLabel(string $label): array
    {
        return self::YEARS_BY_LABEL[$label] ?? [];
    }

    public static function labelForYear(string $className): ?string
    {
        $allYears = [];
        foreach (self::YEARS_BY_LABEL as $label => $years) {
            foreach ($years as $year) {
                $allYears[] = ['label' => $label, 'year' => $year];
            }
        }
        usort($allYears, fn ($a, $b) => strlen($b['year']) <=> strlen($a['year']));

        foreach ($allYears as $entry) {
            if ($className === $entry['year'] || str_starts_with($className, $entry['year'].' ')) {
                return $entry['label'];
            }
        }

        return null;
    }

    public static function forClass(SchoolClass $class): ?string
    {
        $byName = self::labelForYear($class->name);
        if ($byName !== null) {
            return $byName;
        }

        if (in_array($class->nap, self::LABELS, true)) {
            return $class->nap;
        }

        return self::LEGACY_NAP_SINGLE[$class->nap] ?? null;
    }
}