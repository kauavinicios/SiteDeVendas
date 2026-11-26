<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle(Request $request, Product $product): RedirectResponse
    {
        $user = $request->user();

        $existing = $user->favorites()
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return back()->with('success', 'Produto removido dos seus preferidos.');
        }

        abort_unless($product->active, 404);

        $user->favorites()->create([
            'product_id' => $product->id,
            'notify_on_restock' => true,
        ]);

        return back()->with('success', 'Produto adicionado aos seus preferidos! Avisaremos caso volte ao estoque.');
    }
}
