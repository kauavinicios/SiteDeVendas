<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function unsubscribe(User $user): View
    {
        if ($user->marketing_opt_in) {
            $user->forceFill([
                'marketing_opt_in' => false,
                'marketing_opt_out_at' => now(),
            ])->save();
        }

        return view('newsletter.unsubscribed', [
            'user' => $user,
        ]);
    }
}
