<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): SymfonyResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            if ($user->isStaff()) {
                return redirect()->intended(route('dashboard', absolute: false));
            }

            return Inertia::location(
                redirect()->intended(route('account.index', absolute: false)),
            );
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
