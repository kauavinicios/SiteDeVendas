<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        $whatsappDigits = preg_replace('/\D+/', '', (string) config('services.whatsapp.number'));
        $whatsappMessage = rawurlencode('Olá! Vim pela página Sobre da Butique da Jandira e gostaria de conhecer melhor os produtos.');

        $whatsappUrl = $whatsappDigits !== ''
            ? "https://wa.me/{$whatsappDigits}?text={$whatsappMessage}"
            : null;

        return view('pages.about', [
            'whatsappUrl' => $whatsappUrl,
        ]);
    }
}
