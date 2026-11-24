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
        $lencois = Category::where('slug', 'lencois')->firstOrFail();
        $fronhas = Category::where('slug', 'fronhas')->firstOrFail();
        $jogosDeCama = Category::where('slug', 'jogos-de-cama')->firstOrFail();
        $cobertas = Category::where('slug', 'cobertas')->firstOrFail();

        $boticario = Category::where('slug', 'o-boticario')->firstOrFail();
        $importados = Category::where('slug', 'perfumes-importados')->firstOrFail();

        $produtos = [

            // =====================================================
            // COBRE-LEITOS
            // =====================================================

            [
                'category' => $cobreLeitos,
                'name' => 'Cobre-leito Floral',
                'slug' => 'cobre-leito-floral',
                'description' => 'Cobre-leito floral com toque macio e acabamento confortável.',
                'variants' => [
                    ['name' => 'Casal', 'sku' => 'CLF-CAS', 'price' => 129.90, 'stock' => 4],
                    ['name' => 'Queen', 'sku' => 'CLF-QUE', 'price' => 149.90, 'stock' => 2],
                    ['name' => 'King', 'sku' => 'CLF-KIN', 'price' => 179.90, 'stock' => 1],
                ],
            ],

            [
                'category' => $cobreLeitos,
                'name' => 'Cobre-leito Rosé',
                'slug' => 'cobre-leito-rose',
                'description' => 'Cobre-leito em tom rosé com acabamento delicado.',
                'variants' => [
                    ['name' => 'Casal', 'sku' => 'CLR-CAS', 'price' => 139.90, 'stock' => 5],
                    ['name' => 'Queen', 'sku' => 'CLR-QUE', 'price' => 159.90, 'stock' => 3],
                    ['name' => 'King', 'sku' => 'CLR-KIN', 'price' => 189.90, 'stock' => 0],
                ],
            ],

            [
                'category' => $cobreLeitos,
                'name' => 'Cobre-leito Elegance',
                'slug' => 'cobre-leito-elegance',
                'description' => 'Modelo elegante para quartos de casal.',
                'variants' => [
                    ['name' => 'Casal', 'sku' => 'CLE-CAS', 'price' => 159.90, 'stock' => 3],
                    ['name' => 'Queen', 'sku' => 'CLE-QUE', 'price' => 179.90, 'stock' => 2],
                    ['name' => 'King', 'sku' => 'CLE-KIN', 'price' => 209.90, 'stock' => 1],
                ],
            ],

            // =====================================================
            // LENÇÓIS
            // =====================================================

            [
                'category' => $lencois,
                'name' => 'Lençol 200 Fios',
                'slug' => 'lencol-200-fios',
                'description' => 'Lençol macio e confortável em tecido 200 fios.',
                'variants' => [
                    ['name' => 'Casal', 'sku' => 'LEN200-CAS', 'price' => 89.90, 'stock' => 6],
                    ['name' => 'Queen', 'sku' => 'LEN200-QUE', 'price' => 109.90, 'stock' => 4],
                    ['name' => 'King', 'sku' => 'LEN200-KIN', 'price' => 129.90, 'stock' => 2],
                ],
            ],

            [
                'category' => $lencois,
                'name' => 'Lençol Premium',
                'slug' => 'lencol-premium',
                'description' => 'Lençol premium com toque suave.',
                'variants' => [
                    ['name' => 'Casal', 'sku' => 'LENPRE-CAS', 'price' => 99.90, 'stock' => 4],
                    ['name' => 'Queen', 'sku' => 'LENPRE-QUE', 'price' => 119.90, 'stock' => 3],
                    ['name' => 'King', 'sku' => 'LENPRE-KIN', 'price' => 139.90, 'stock' => 1],
                ],
            ],

            [
                'category' => $lencois,
                'name' => 'Lençol Confort',
                'slug' => 'lencol-confort',
                'description' => 'Lençol confortável para uso diário.',
                'variants' => [
                    ['name' => 'Casal', 'sku' => 'LENCON-CAS', 'price' => 79.90, 'stock' => 8],
                    ['name' => 'Queen', 'sku' => 'LENCON-QUE', 'price' => 99.90, 'stock' => 5],
                    ['name' => 'King', 'sku' => 'LENCON-KIN', 'price' => 119.90, 'stock' => 3],
                ],
            ],

            // =====================================================
            // FRONHAS
            // =====================================================

            [
                'category' => $fronhas,
                'name' => 'Fronha Floral',
                'slug' => 'fronha-floral',
                'description' => 'Fronha floral com tecido macio.',
                'variants' => [
                    ['name' => 'Unidade', 'sku' => 'FROFL-UNI', 'price' => 29.90, 'stock' => 8],
                    ['name' => 'Kit 2 unidades', 'sku' => 'FROFL-K2', 'price' => 54.90, 'stock' => 4],
                ],
            ],

            [
                'category' => $fronhas,
                'name' => 'Fronha Rosé',
                'slug' => 'fronha-rose',
                'description' => 'Fronha rosé para combinar com jogos de cama.',
                'variants' => [
                    ['name' => 'Unidade', 'sku' => 'FRORO-UNI', 'price' => 32.90, 'stock' => 7],
                    ['name' => 'Kit 2 unidades', 'sku' => 'FRORO-K2', 'price' => 59.90, 'stock' => 3],
                ],
            ],

            [
                'category' => $fronhas,
                'name' => 'Fronha Premium',
                'slug' => 'fronha-premium',
                'description' => 'Fronha com acabamento premium.',
                'variants' => [
                    ['name' => 'Unidade', 'sku' => 'FROPRE-UNI', 'price' => 34.90, 'stock' => 6],
                    ['name' => 'Kit 2 unidades', 'sku' => 'FROPRE-K2', 'price' => 64.90, 'stock' => 2],
                ],
            ],

            // =====================================================
            // JOGOS DE CAMA
            // =====================================================

            [
                'category' => $jogosDeCama,
                'name' => 'Jogo de Cama Classic',
                'slug' => 'jogo-cama-classic',
                'description' => 'Jogo de cama completo para deixar o quarto mais confortável.',
                'variants' => [
                    ['name' => 'Casal', 'sku' => 'JGC-CLA-CAS', 'price' => 149.90, 'stock' => 4],
                    ['name' => 'Queen', 'sku' => 'JGC-CLA-QUE', 'price' => 179.90, 'stock' => 3],
                    ['name' => 'King', 'sku' => 'JGC-CLA-KIN', 'price' => 209.90, 'stock' => 1],
                ],
            ],

            [
                'category' => $jogosDeCama,
                'name' => 'Jogo de Cama Floral',
                'slug' => 'jogo-cama-floral',
                'description' => 'Jogo de cama com estampa floral.',
                'variants' => [
                    ['name' => 'Casal', 'sku' => 'JGC-FLO-CAS', 'price' => 159.90, 'stock' => 3],
                    ['name' => 'Queen', 'sku' => 'JGC-FLO-QUE', 'price' => 189.90, 'stock' => 2],
                    ['name' => 'King', 'sku' => 'JGC-FLO-KIN', 'price' => 219.90, 'stock' => 0],
                ],
            ],

            [
                'category' => $jogosDeCama,
                'name' => 'Jogo de Cama Premium',
                'slug' => 'jogo-cama-premium',
                'description' => 'Jogo de cama com acabamento premium.',
                'variants' => [
                    ['name' => 'Casal', 'sku' => 'JGC-PRE-CAS', 'price' => 179.90, 'stock' => 2],
                    ['name' => 'Queen', 'sku' => 'JGC-PRE-QUE', 'price' => 209.90, 'stock' => 2],
                    ['name' => 'King', 'sku' => 'JGC-PRE-KIN', 'price' => 239.90, 'stock' => 1],
                ],
            ],

            // =====================================================
            // COBERTAS
            // =====================================================

            [
                'category' => $cobertas,
                'name' => 'Coberta Soft',
                'slug' => 'coberta-soft',
                'description' => 'Coberta macia e confortável.',
                'variants' => [
                    ['name' => 'Casal', 'sku' => 'COBSOF-CAS', 'price' => 99.90, 'stock' => 5],
                    ['name' => 'Queen', 'sku' => 'COBSOF-QUE', 'price' => 119.90, 'stock' => 3],
                    ['name' => 'King', 'sku' => 'COBSOF-KIN', 'price' => 149.90, 'stock' => 2],
                ],
            ],

            [
                'category' => $cobertas,
                'name' => 'Coberta Microfibra',
                'slug' => 'coberta-microfibra',
                'description' => 'Coberta em microfibra com toque suave.',
                'variants' => [
                    ['name' => 'Casal', 'sku' => 'COBMIC-CAS', 'price' => 109.90, 'stock' => 4],
                    ['name' => 'Queen', 'sku' => 'COBMIC-QUE', 'price' => 129.90, 'stock' => 2],
                    ['name' => 'King', 'sku' => 'COBMIC-KIN', 'price' => 159.90, 'stock' => 1],
                ],
            ],

            [
                'category' => $cobertas,
                'name' => 'Coberta Extra Macia',
                'slug' => 'coberta-extra-macia',
                'description' => 'Coberta com toque extra macio para dias frios.',
                'variants' => [
                    ['name' => 'Casal', 'sku' => 'COBEXT-CAS', 'price' => 119.90, 'stock' => 3],
                    ['name' => 'Queen', 'sku' => 'COBEXT-QUE', 'price' => 139.90, 'stock' => 2],
                    ['name' => 'King', 'sku' => 'COBEXT-KIN', 'price' => 169.90, 'stock' => 0],
                ],
            ],

            // =====================================================
            // O BOTICÁRIO
            // =====================================================

            [
                'category' => $boticario,
                'name' => 'Perfume Floratta',
                'slug' => 'perfume-floratta',
                'description' => 'Fragrância feminina delicada.',
                'variants' => [
                    ['name' => '75 ml', 'sku' => 'BOT-FLO-75', 'price' => 129.90, 'stock' => 4],
                ],
            ],

            [
                'category' => $boticario,
                'name' => 'Perfume Egeo',
                'slug' => 'perfume-egeo',
                'description' => 'Fragrância marcante da linha Egeo.',
                'variants' => [
                    ['name' => '90 ml', 'sku' => 'BOT-EGE-90', 'price' => 139.90, 'stock' => 3],
                ],
            ],

            [
                'category' => $boticario,
                'name' => 'Perfume Malbec',
                'slug' => 'perfume-malbec',
                'description' => 'Fragrância masculina intensa.',
                'variants' => [
                    ['name' => '100 ml', 'sku' => 'BOT-MAL-100', 'price' => 179.90, 'stock' => 2],
                ],
            ],

            // =====================================================
            // PERFUMES IMPORTADOS
            // =====================================================

            [
                'category' => $importados,
                'name' => 'Perfume Importado Elegance',
                'slug' => 'perfume-importado-elegance',
                'description' => 'Fragrância importada elegante e marcante.',
                'variants' => [
                    ['name' => '50 ml', 'sku' => 'IMP-ELE-50', 'price' => 149.90, 'stock' => 3],
                    ['name' => '100 ml', 'sku' => 'IMP-ELE-100', 'price' => 189.90, 'stock' => 2],
                ],
            ],

            [
                'category' => $importados,
                'name' => 'Perfume Importado Gold',
                'slug' => 'perfume-importado-gold',
                'description' => 'Perfume importado de fragrância sofisticada.',
                'variants' => [
                    ['name' => '50 ml', 'sku' => 'IMP-GOL-50', 'price' => 169.90, 'stock' => 2],
                    ['name' => '100 ml', 'sku' => 'IMP-GOL-100', 'price' => 209.90, 'stock' => 1],
                ],
            ],

            [
                'category' => $importados,
                'name' => 'Perfume Importado Classic',
                'slug' => 'perfume-importado-classic',
                'description' => 'Fragrância importada clássica.',
                'variants' => [
                    ['name' => '50 ml', 'sku' => 'IMP-CLA-50', 'price' => 139.90, 'stock' => 4],
                    ['name' => '100 ml', 'sku' => 'IMP-CLA-100', 'price' => 169.90, 'stock' => 2],
                ],
            ],

            [
                'category' => $importados,
                'name' => 'Perfume Importado Intense',
                'slug' => 'perfume-importado-intense',
                'description' => 'Perfume importado com aroma intenso.',
                'variants' => [
                    ['name' => '50 ml', 'sku' => 'IMP-INT-50', 'price' => 179.90, 'stock' => 3],
                    ['name' => '100 ml', 'sku' => 'IMP-INT-100', 'price' => 229.90, 'stock' => 1],
                ],
            ],

            [
                'category' => $importados,
                'name' => 'Perfume Importado Fresh',
                'slug' => 'perfume-importado-fresh',
                'description' => 'Fragrância fresca para o dia a dia.',
                'variants' => [
                    ['name' => '50 ml', 'sku' => 'IMP-FRE-50', 'price' => 129.90, 'stock' => 5],
                    ['name' => '100 ml', 'sku' => 'IMP-FRE-100', 'price' => 159.90, 'stock' => 3],
                ],
            ],

            [
                'category' => $importados,
                'name' => 'Perfume Importado Night',
                'slug' => 'perfume-importado-night',
                'description' => 'Fragrância marcante para ocasiões noturnas.',
                'variants' => [
                    ['name' => '50 ml', 'sku' => 'IMP-NIG-50', 'price' => 159.90, 'stock' => 2],
                    ['name' => '100 ml', 'sku' => 'IMP-NIG-100', 'price' => 199.90, 'stock' => 0],
                ],
            ],
        ];

        // updateOrCreate (chaves: slug do produto / sku da variação) permite
        // executar o seeder várias vezes sem duplicar registros.
        foreach ($produtos as $index => $dados) {

            $produto = Product::updateOrCreate(
                ['slug' => $dados['slug']],
                [
                    'category_id' => $dados['category']->id,
                    'name' => $dados['name'],
                    'description' => $dados['description'],
                    'active' => true,
                    'featured' => $index < 8,
                ],
            );

            foreach ($dados['variants'] as $variant) {

                $produto->variants()->updateOrCreate(
                    ['sku' => $variant['sku']],
                    [
                        'name' => $variant['name'],
                        'price' => $variant['price'],
                        'stock' => $variant['stock'],
                        'active' => true,
                    ],
                );
            }
        }
    }
}
