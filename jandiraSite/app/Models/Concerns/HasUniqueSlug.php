<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Gera slugs únicos para a coluna "slug" do model.
 *
 * Exemplo: "Perfume Egeo" -> "perfume-egeo"; se já existir,
 * "perfume-egeo-2", "perfume-egeo-3"...
 */
trait HasUniqueSlug
{
    /**
     * @param  string  $value  Texto base (normalmente o nome)
     * @param  int|null  $ignoreId  ID a desconsiderar (o próprio registro, na edição)
     */
    public static function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = $base;
        $counter = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
