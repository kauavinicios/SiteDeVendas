<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SearchPattern;
use App\Support\SecurityAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('busca')->toString());
        $status = $request->string('status')->toString();
        $newsletter = $request->string('novidades')->toString();

        if (! in_array($status, ['todos', 'ativos', 'inativos'], true)) {
            $status = 'todos';
        }

        if (! in_array($newsletter, ['todos', 'inscritos', 'nao-inscritos'], true)) {
            $newsletter = 'todos';
        }

        $customersBase = User::query()->where('role', UserRole::Customer->value);

        $customers = (clone $customersBase)
            ->when($search !== '', function ($query) use ($search) {
                $pattern = SearchPattern::contains($search);

                $query->where(function ($subQuery) use ($pattern) {
                    $subQuery
                        ->where('name', 'ilike', $pattern)
                        ->orWhere('email', 'ilike', $pattern)
                        ->orWhere('phone', 'ilike', $pattern);
                });
            })
            ->when($status === 'ativos', fn ($query) => $query->where('active', true))
            ->when($status === 'inativos', fn ($query) => $query->where('active', false))
            ->when($newsletter === 'inscritos', fn ($query) => $query->where('marketing_opt_in', true))
            ->when($newsletter === 'nao-inscritos', fn ($query) => $query->where('marketing_opt_in', false))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'active' => $customer->active,
                'marketing_opt_in' => $customer->marketing_opt_in,
                'marketing_opt_in_at' => $customer->marketing_opt_in_at?->format('d/m/Y H:i'),
                'created_at' => $customer->created_at?->format('d/m/Y'),
            ]);

        return Inertia::render('admin/customers/index', [
            'customers' => $customers,
            'stats' => [
                'total' => (clone $customersBase)->count(),
                'active' => (clone $customersBase)->where('active', true)->count(),
                'subscribed' => (clone $customersBase)->where('marketing_opt_in', true)->count(),
                'inactive' => (clone $customersBase)->where('active', false)->count(),
            ],
            'filters' => [
                'busca' => $search,
                'status' => $status,
                'novidades' => $newsletter,
            ],
        ]);
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        abort_unless($user->isCustomer(), 404);

        $nextActive = ! $user->active;

        $user->forceFill(['active' => $nextActive])->save();

        SecurityAudit::record('customer.status_toggled', [
            'target_user_id' => $user->id,
            'active' => $user->active,
        ]);

        $message = $nextActive
            ? 'Conta do cliente reativada com sucesso.'
            : 'Conta do cliente desativada com sucesso.';

        return back()->with('success', $message);
    }
}
