<?php

namespace App\Support;

use Illuminate\Support\Str;

class SearchPattern
{
    /**
     * Prepara um termo de busca para cláusulas LIKE / ILIKE escapando os
     * wildcards SQL ("\", "%", "_") e limitando o comprimento da entrada.
     */
    public static function contains(string $term, int $maxLength = 100): string
    {
        $clean = Str::limit(trim($term), $maxLength, '');
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $clean);

        return '%'.$escaped.'%';
    }
}
