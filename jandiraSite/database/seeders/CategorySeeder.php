<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Categorias raiz e suas subcategorias.
     *
     * Usa updateOrCreate (chave: slug) para que o seeder possa ser
     * executado várias vezes sem duplicar registros.
     */
    public function run(): void
    {
        $tree = [
            ['name' => 'Casa', 'slug' => 'casa', 'children' => [
                ['name' => 'Lençóis', 'slug' => 'lencois'],
                ['name' => 'Fronhas', 'slug' => 'fronhas'],
                ['name' => 'Jogos de Cama', 'slug' => 'jogos-de-cama'],
                ['name' => 'Cobre-leitos', 'slug' => 'cobre-leitos'],
                ['name' => 'Cobertas', 'slug' => 'cobertas'],
            ]],
            ['name' => 'Perfumaria', 'slug' => 'perfumaria', 'children' => [
                ['name' => 'O Boticário', 'slug' => 'o-boticario'],
                ['name' => 'Perfumes Importados', 'slug' => 'perfumes-importados'],
            ]],
        ];

        foreach ($tree as $root) {
            $parent = Category::updateOrCreate(
                ['slug' => $root['slug']],
                ['name' => $root['name'], 'parent_id' => null],
            );

            foreach ($root['children'] as $child) {
                Category::updateOrCreate(
                    ['slug' => $child['slug']],
                    ['name' => $child['name'], 'parent_id' => $parent->id],
                );
            }
        }
    }
}
