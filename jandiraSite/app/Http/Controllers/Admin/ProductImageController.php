<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ImageSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductImageController extends Controller
{
    public function store(
        ProductImageRequest $request,
        Product $product,
        ImageSanitizer $imageSanitizer,
    ): RedirectResponse {
        $files = $request->file('images', []);
        $nextPosition = ((int) $product->images()->max('position')) + 1;

        foreach ($files as $file) {
            $path = $imageSanitizer->storeSanitized($file, "products/{$product->id}", 'public');

            $product->images()->create([
                'path' => $path,
                'position' => $nextPosition,
            ]);

            $nextPosition++;
        }

        $count = count($files);
        $message = $count === 1
            ? 'Imagem enviada com sucesso.'
            : "{$count} imagens enviadas com sucesso.";

        return back()->with('success', $message);
    }

    public function reorder(
        Request $request,
        Product $product
    ): RedirectResponse {
        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => [
                'required',
                'integer',
                Rule::exists('product_images', 'id')->where('product_id', $product->id),
            ],
        ]);

        $ids = array_values(array_unique($validated['order']));

        DB::transaction(function () use ($product, $ids) {
            foreach ($ids as $index => $imageId) {
                $product->images()
                    ->where('id', $imageId)
                    ->update(['position' => $index + 1]);
            }
        });

        return back()->with('success', 'Ordem das imagens atualizada com sucesso.');
    }

    public function destroy(
        Product $product,
        ProductImage $image
    ): RedirectResponse {
        abort_unless($image->product_id === $product->id, 404);

        Storage::disk('public')->delete($image->path);

        $image->delete();

        DB::transaction(function () use ($product) {
            $remaining = $product->images()
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            foreach ($remaining as $index => $item) {
                $item->update(['position' => $index + 1]);
            }
        });

        return back()->with('success', 'Imagem excluída com sucesso.');
    }
}
