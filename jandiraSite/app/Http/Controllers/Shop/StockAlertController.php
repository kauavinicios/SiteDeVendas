<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Support\EmailValidation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StockAlertController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $rawEmail = trim((string) $request->input('email', ''));

        if ($rawEmail === '' && $request->user()) {
            $rawEmail = (string) $request->user()->email;
        }

        $request->merge([
            'email' => Str::lower($rawEmail),
        ]);

        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'email' => ['required', 'string', 'lowercase', EmailValidation::rule(), 'max:255'],
        ], [
            'variant_id.required' => 'Selecione uma opção do produto.',
            'email.required' => 'Informe seu e-mail para receber o aviso de estoque.',
            'email.email' => 'Informe um endereço de e-mail válido.',
        ]);

        $variant = ProductVariant::query()
            ->with('product')
            ->where('active', true)
            ->findOrFail($validated['variant_id']);

        if (! $variant->product || ! $variant->product->active) {
            abort(404);
        }

        if ($variant->stock > 0) {
            return back()->with('error', 'Esta opção já está disponível em estoque.');
        }

        $alreadyPending = StockAlert::query()
            ->where('product_variant_id', $variant->id)
            ->where('email', $validated['email'])
            ->whereNull('notified_at')
            ->exists();

        if (! $alreadyPending) {
            StockAlert::create([
                'product_variant_id' => $variant->id,
                'user_id' => $request->user()?->id,
                'email' => $validated['email'],
            ]);
        }

        return back()->with('success', 'Pronto! Avisaremos no seu e-mail assim que esta opção voltar ao estoque.');
    }
}
