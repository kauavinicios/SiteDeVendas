<?php

namespace App\Services;

use App\Mail\BackInStockMail;
use App\Models\Favorite;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class StockAlertService
{
    /**
     * Dispara avisos por e-mail quando uma variação volta de estoque <= 0 para > 0.
     */
    public function notifyRestockedVariant(ProductVariant $variant, int $previousStock): int
    {
        if ($previousStock > 0 || $variant->stock <= 0 || ! $variant->active) {
            return 0;
        }

        $variant->loadMissing('product');

        if (! $variant->product || ! $variant->product->active) {
            return 0;
        }

        $pendingAlerts = StockAlert::query()
            ->with('user')
            ->where('product_variant_id', $variant->id)
            ->whereNull('notified_at')
            ->get();

        $recipients = [];

        foreach ($pendingAlerts as $alert) {
            $email = Str::lower(trim($alert->email));

            if ($email === '') {
                continue;
            }

            $recipients[$email] = $alert->user?->name ?? 'cliente';
        }

        $favorites = Favorite::query()
            ->with('user')
            ->where('product_id', $variant->product_id)
            ->where('notify_on_restock', true)
            ->whereHas('user', fn ($query) => $query->where('active', true))
            ->get();

        foreach ($favorites as $favorite) {
            $user = $favorite->user;

            if (! $user || ! $user->email) {
                continue;
            }

            $email = Str::lower(trim($user->email));

            $recipients[$email] ??= $user->name;
        }

        foreach ($recipients as $email => $name) {
            Mail::to($email)->queue(new BackInStockMail($variant, $name));
        }

        if ($pendingAlerts->isNotEmpty()) {
            StockAlert::query()
                ->whereIn('id', $pendingAlerts->pluck('id'))
                ->update(['notified_at' => now()]);
        }

        return count($recipients);
    }
}
