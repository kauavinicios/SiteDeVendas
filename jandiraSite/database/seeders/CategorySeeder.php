<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $casa = Category::create([
            'name' => 'Casa',
            'slug' => 'casa',
        ]);

        $casa->children()->createMany([
            [
                'name' => 'Lençóis',
                'slug' => 'lencois',
            ],
            [
                'name' => 'Fronhas',
                'slug' => 'fronhas',
            ],
            [
                'name' => 'Jogos de Cama',
                'slug' => 'jogos-de-cama',
            ],
            [
                'name' => 'Cobre-leitos',
                'slug' => 'cobre-leitos',
            ],
            [
                'name' => 'Cobertas',
                'slug' => 'cobertas',
            ],
        ]);

        $perfumaria = Category::create([
            'name' => 'Perfumaria',
            'slug' => 'perfumaria',
        ]);

        $perfumaria->children()->createMany([
            [
                'name' => 'O Boticário',
                'slug' => 'o-boticario',
            ],
            [
                'name' => 'Perfumes Importados',
                'slug' => 'perfumes-importados',
            ],
        ]);
    }
}