<?php

namespace App\Http\Controllers;

use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $produtos = Product::query()
            ->where('active', true)
            ->where('featured', true)
            ->with([
                'category',
                'variants',
                'images',
            ])
            ->latest()
            ->take(8)
            ->get();

        return view('home', compact('produtos'));
    }
}