<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $produtos = Product::query()
            ->where('active', true)
            ->with([
                'category',
                'variants',
                'images',
            ])

            ->when($request->filled('busca'), function ($query) use ($request) {
                $busca = trim($request->string('busca')->toString());

                $query->where(function ($query) use ($busca) {
                    $query
                        ->where('name', 'ilike', '%'.$busca.'%')
                        ->orWhere('description', 'ilike', '%'.$busca.'%')
                        ->orWhereHas('category', function ($query) use ($busca) {
                            $query->where('name', 'ilike', '%'.$busca.'%');
                        });
                });
            })

            ->when($request->filled('categorias'), function ($query) use ($request) {

                $slugs = collect($request->input('categorias', []))
                    ->filter()
                    ->values();

                $categoriasSelecionadas = Category::query()
                    ->whereIn('slug', $slugs)
                    ->get();

                $idsCategorias = collect();

                foreach ($categoriasSelecionadas as $categoria) {

                    $categoria->load('children');

                    $idsCategorias->push($categoria->id);

                    $idsCategorias = $idsCategorias->merge(
                        $categoria->descendantIds()
                    );
                }

                $query->whereIn(
                    'category_id',
                    $idsCategorias->unique()->values()
                );
            });
        $ordenacao = $request->string('ordenacao')->toString();

        switch ($ordenacao) {
            case 'menor-preco':
                $produtos
                    ->withMin(
                        [
                            'variants as menor_preco' => function ($query) {
                                $query->where('active', true);
                            },
                        ],
                        'price'
                    )
                    ->orderBy('menor_preco');
                break;

            case 'maior-preco':
                $produtos
                    ->withMax(
                        [
                            'variants as maior_preco' => function ($query) {
                                $query->where('active', true);
                            },
                        ],
                        'price'
                    )
                    ->orderByDesc('maior_preco');
                break;

            case 'nome':
                $produtos->orderBy('name');
                break;

            default:
                $produtos->latest();
                break;
        }

        $produtos = $produtos
            ->paginate(12)
            ->withQueryString();

        $categorias = Category::query()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('products.index', compact(
            'produtos',
            'categorias'
        ));
    }

    public function show(Product $product)
    {
        abort_unless($product->active, 404);

        $product->load([
            'category',
            'variants' => function ($query) {
                $query
                    ->where('active', true)
                    ->orderBy('price');
            },
            'images',
        ]);

        return view('products.show', compact('product'));
    }
}
