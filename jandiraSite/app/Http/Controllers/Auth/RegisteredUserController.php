<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EmailValidation;
use App\Support\LegalDocuments;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
        if ($request->has('email')) {
            $request->merge([
                'email' => Str::lower(trim((string) $request->input('email'))),
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', EmailValidation::rule(), 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'marketing_opt_in' => ['sometimes', 'boolean'],
            'terms_accepted' => ['accepted'],
        ], [
            'name.required' => 'Informe seu nome completo.',
            'name.max' => 'O nome pode ter no máximo 255 caracteres.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.lowercase' => 'O e-mail deve estar em letras minúsculas.',
            'email.max' => 'O e-mail pode ter no máximo 255 caracteres.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'phone.max' => 'O telefone pode ter no máximo 20 caracteres.',
            'password.required' => 'Informe uma senha.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
            'password.confirmed' => 'A confirmação de senha não confere.',
            'terms_accepted.accepted' => 'Você precisa aceitar os Termos de Uso e a Política de Privacidade para criar sua conta.',
        ]);

        $marketingOptIn = $request->boolean('marketing_opt_in');
        $acceptedAt = now();

        $user = new User;
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'password' => Hash::make($validated['password']),
            'marketing_opt_in' => $marketingOptIn,
            'marketing_opt_in_at' => $marketingOptIn ? $acceptedAt : null,
            'terms_accepted_at' => $acceptedAt,
            'terms_version' => LegalDocuments::TERMS_VERSION,
            'privacy_accepted_at' => $acceptedAt,
            'privacy_version' => LegalDocuments::PRIVACY_VERSION,
        ])->forceFill([
            'role' => UserRole::Customer,
            'active' => true,
        ])->save();

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
