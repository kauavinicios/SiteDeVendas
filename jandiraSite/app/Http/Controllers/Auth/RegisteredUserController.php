<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EmailValidation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    public function store(Request $request): SymfonyResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', EmailValidation::rule(), 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'marketing_opt_in' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'Informe seu nome completo.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.required' => 'Informe uma senha.',
            'password.confirmed' => 'A confirmação de senha não confere.',
        ]);

        $marketingOptIn = $request->boolean('marketing_opt_in');

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'password' => Hash::make($validated['password']),
            'role' => UserRole::Customer,
            'active' => true,
            'marketing_opt_in' => $marketingOptIn,
            'marketing_opt_in_at' => $marketingOptIn ? now() : null,
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return Inertia::location(
            redirect()
                ->intended(route('home', absolute: false))
                ->with('success', 'Conta criada com sucesso! Boas-vindas à Butique da Jandira.'),
        );
    }
}
