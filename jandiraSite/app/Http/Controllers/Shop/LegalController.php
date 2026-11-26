<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Support\LegalDocuments;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function terms(): View
    {
        return view('legal.terms', [
            'version' => LegalDocuments::TERMS_VERSION,
            'updatedAt' => LegalDocuments::UPDATED_AT,
        ]);
    }

    public function privacy(): View
    {
        return view('legal.privacy', [
            'version' => LegalDocuments::PRIVACY_VERSION,
            'updatedAt' => LegalDocuments::UPDATED_AT,
        ]);
    }
}
