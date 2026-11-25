<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.index', [
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'marketing_opt_in' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'Informe seu nome completo.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está em uso.',
        ]);

        $wantsOptIn = $request->boolean('marketing_opt_in');
        $wasOptedIn = (bool) $user->marketing_opt_in;

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = filled($validated['phone'] ?? null) ? trim($validated['phone']) : null;
        $user->marketing_opt_in = $wantsOptIn;

        if (! $wasOptedIn && $wantsOptIn) {
            $user->marketing_opt_in_at = now();
            $user->marketing_opt_out_at = null;
        } elseif ($wasOptedIn && ! $wantsOptIn) {
            $user->marketing_opt_out_at = now();
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()
            ->route('account.index')
            ->with('success', 'Seus dados e preferências foram atualizados.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('passwordUpdate', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.required' => 'Informe sua senha atual.',
            'current_password.current_password' => 'A senha atual está incorreta.',
            'password.required' => 'Informe a nova senha.',
            'password.confirmed' => 'A confirmação da nova senha não confere.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('account.index')
            ->with('success', 'Senha alterada com sucesso.');
    }
}
