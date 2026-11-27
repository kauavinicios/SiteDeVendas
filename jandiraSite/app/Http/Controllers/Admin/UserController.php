<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\User;
use App\Support\SearchPattern;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('busca')->toString());
        $role = $request->string('cargo')->toString();
        $status = $request->string('status')->toString();

        if (! in_array($role, ['todos', ...UserRole::staffValues()], true)) {
            $role = 'todos';
        }

        if (! in_array($status, ['todos', 'ativos', 'inativos'], true)) {
            $status = 'todos';
        }

        $staffBase = User::query()->whereIn('role', UserRole::staffValues());

        $users = (clone $staffBase)
            ->when($search !== '', function ($query) use ($search) {
                $pattern = SearchPattern::contains($search);

                $query->where(function ($subQuery) use ($pattern) {
                    $subQuery
                        ->where('name', 'ilike', $pattern)
                        ->orWhere('email', 'ilike', $pattern)
                        ->orWhere('phone', 'ilike', $pattern);
                });
            })
            ->when($role !== 'todos', fn ($query) => $query->where('role', $role))
            ->when($status === 'ativos', fn ($query) => $query->where('active', true))
            ->when($status === 'inativos', fn ($query) => $query->where('active', false))
            ->orderByDesc('active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role?->value,
                'role_label' => $user->role?->label(),
                'active' => $user->active,
                'created_at' => $user->created_at?->format('d/m/Y'),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'stats' => [
                'total' => (clone $staffBase)->count(),
                'active' => (clone $staffBase)->where('active', true)->count(),
                'inactive' => (clone $staffBase)->where('active', false)->count(),
                'admins' => User::query()
                    ->where('role', UserRole::Admin->value)
                    ->where('active', true)
                    ->count(),
            ],
            'roles' => array_map(
                fn (UserRole $staffRole) => [
                    'value' => $staffRole->value,
                    'label' => $staffRole->label(),
                ],
                UserRole::staffRoles(),
            ),
            'filters' => [
                'busca' => $search,
                'cargo' => $role,
                'status' => $status,
            ],
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = new User;
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'password' => Hash::make($validated['password']),
        ])->forceFill([
            'role' => $validated['role'],
            'active' => $validated['active'],
            'email_verified_at' => now(),
        ])->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuário da equipe cadastrado com sucesso.');
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $validated = $request->validated();
        $isSelf = $user->id === $request->user()?->id;

        if ($isSelf && ! $validated['active']) {
            return back()->with('error', 'Você não pode desativar a sua própria conta.');
        }

        if ($isSelf && $validated['role'] !== UserRole::Admin->value) {
            return back()->with('error', 'Você não pode remover o seu próprio cargo de administrador.');
        }

        $wasActiveAdmin = $user->isAdmin() && $user->active;
        $remainsActiveAdmin = $validated['role'] === UserRole::Admin->value && $validated['active'];

        if ($wasActiveAdmin && ! $remainsActiveAdmin && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'O sistema precisa manter pelo menos um administrador ativo.');
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
        ];

        if (filled($validated['password'] ?? null)) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->fill($payload)->forceFill([
            'role' => $validated['role'],
            'active' => $validated['active'],
        ])->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isStaff(), 404);

        $nextActive = ! $user->active;

        if ($user->id === $request->user()?->id && ! $nextActive) {
            return back()->with('error', 'Você não pode desativar a sua própria conta.');
        }

        if (! $nextActive && $user->isAdmin() && $this->isLastActiveAdmin($user)) {
            return back()->with('error', 'O sistema precisa manter pelo menos um administrador ativo.');
        }

        $user->forceFill(['active' => $nextActive])->save();

        $message = $nextActive
            ? 'Usuário reativado com sucesso.'
            : 'Usuário desativado com sucesso.';

        return back()->with('success', $message);
    }

    private function isLastActiveAdmin(User $user): bool
    {
        return User::query()
            ->where('role', UserRole::Admin->value)
            ->where('active', true)
            ->where('id', '!=', $user->id)
            ->doesntExist();
    }
}
