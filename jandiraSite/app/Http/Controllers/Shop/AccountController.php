<?php

namespace App\Http\Controllers\Shop;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Support\EmailValidation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $orders = $user->orders()
            ->with('items')
            ->latest()
            ->take(20)
            ->get();

        $allUserOrders = $user->orders()->get(['status', 'total', 'items_count', 'created_at']);
        $completedOrders = $allUserOrders->filter(
            fn ($order) => $order->status instanceof OrderStatus && $order->status->countsAsRevenue()
        );

        $favorites = $user->favorites()
            ->with(['product.category', 'product.images', 'product.variants'])
            ->whereHas('product', fn ($query) => $query->where('active', true))
            ->latest()
            ->get();

        return view('account.index', [
            'user' => $user,
            'orders' => $orders,
            'favorites' => $favorites,
            'orderStats' => [
                'total_orders' => $allUserOrders->count(),
                'confirmed_orders' => $completedOrders->count(),
                'total_items' => (int) $completedOrders->sum('items_count'),
                'total_spent' => (float) $completedOrders->sum('total'),
            ],
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
                EmailValidation::rule(),
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

        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        $message = $emailChanged
            ? 'Seus dados foram atualizados. Enviamos um link de confirmação para o novo e-mail.'
            : 'Seus dados e preferências foram atualizados.';

        return redirect()
            ->route('account.index')
            ->with('success', $message);
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

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isCustomer(), 403, 'Contas internas da equipe não podem ser excluídas.');

        $request->validateWithBag('accountDeletion', [
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Informe sua senha atual para confirmar a exclusão.',
            'password.current_password' => 'A senha informada está incorreta.',
        ]);

        Auth::guard('web')->logout();

        $user->closeCustomerAccount();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('success', 'Sua conta foi encerrada conforme a Política de Privacidade.');
    }
}
