<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $cobreLeitos = Category::where('slug', 'cobre-leitos')->firstOrFail();

        $produto = Product::create([
            'category_id' => $cobreLeitos->id,
            'name' => 'Cobre-leito Floral',
            'slug' => 'cobre-leito-floral',
            'description' => 'Cobre-leito floral com toque macio e acabamento confortável.',
            'active' => true,
            'featured' => true,
        ]);

        $produto->variants()->createMany([
            [
                'name' => 'Casal',
                'sku' => 'CLF-CAS-001',
                'price' => 129.90,
                'stock' => 3,
                'active' => true,
            ],
            [
                'name' => 'Queen',
                'sku' => 'CLF-QUE-001',
                'price' => 149.90,
                'stock' => 2,
                'active' => true,
            ],
            [
                'name' => 'King',
                'sku' => 'CLF-KIN-001',
                'price' => 179.90,
                'stock' => 1,
                'active' => true,
            ],
        ]);

        $produto->images()->createMany([
            [
                'path' => 'products/cobre-leito-floral-01.jpg',
                'position' => 1,
            ],
            [
                'path' => 'products/cobre-leito-floral-02.jpg',
                'position' => 2,
            ],
        ]);


        $perfumesImportados = Category::where('slug', 'perfumes-importados')
            ->firstOrFail();

        $perfume = Product::create([
            'category_id' => $perfumesImportados->id,
            'name' => 'Perfume Importado Elegance',
            'slug' => 'perfume-importado-elegance',
            'description' => 'Fragrância importada com aroma marcante e sofisticado.',
            'active' => true,
            'featured' => true,
        ]);

        $perfume->variants()->create([
            'name' => '100 ml',
            'sku' => 'PIE-100-001',
            'price' => 189.90,
            'stock' => 4,
            'active' => true,
        ]);

        $perfume->images()->create([
            'path' => 'products/perfume-elegance-01.jpg',
            'position' => 1,
        ]);
    }
}
