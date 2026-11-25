import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData, type UserRole } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    Pencil,
    Plus,
    Power,
    Search,
    ShieldCheck,
    UserCheck,
    UserX,
    Users,
} from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

type StaffRoleFilter = 'todos' | 'admin' | 'manager' | 'storekeeper' | 'support';
type StatusFilter = 'todos' | 'ativos' | 'inativos';

type StaffUser = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: UserRole;
    role_label: string;
    active: boolean;
    created_at: string | null;
};

type RoleOption = {
    value: UserRole;
    label: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedUsers = {
    data: StaffUser[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

type UserStats = {
    total: number;
    active: number;
    inactive: number;
    admins: number;
};

type UserFilters = {
    busca: string;
    cargo: StaffRoleFilter;
    status: StatusFilter;
};

type Props = {
    users: PaginatedUsers;
    stats: UserStats;
    roles: RoleOption[];
    filters: UserFilters;
};

type UserFormData = {
    name: string;
    email: string;
    phone: string;
    role: UserRole;
    password: string;
    password_confirmation: string;
    active: boolean;
};

type PageProps = SharedData & {
    flash?: {
        success?: string;
        error?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuários',
        href: '/admin/usuarios',
    },
];

const statusOptions: { value: StatusFilter; label: string }[] = [
    { value: 'todos', label: 'Todos' },
    { value: 'ativos', label: 'Ativos' },
    { value: 'inativos', label: 'Inativos' },
];

export default function UsersIndex({ users, stats, roles, filters }: Props) {
    const { auth, flash } = usePage<PageProps>().props;
    const currentUserId = auth?.user?.id;

    const [search, setSearch] = useState(filters.busca);
    const [open, setOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<StaffUser | null>(null);
    const [togglingId, setTogglingId] = useState<number | null>(null);

    useEffect(() => {
        setSearch(filters.busca);
    }, [filters.busca]);

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm<UserFormData>({
        name: '',
        email: '',
        phone: '',
        role: 'storekeeper',
        password: '',
        password_confirmation: '',
        active: true,
    });

    const applyFilters = (
        nextSearch: string,
        nextRole: StaffRoleFilter,
        nextStatus: StatusFilter,
    ) => {
        const params: Record<string, string> = {};
        const trimmed = nextSearch.trim();

        if (trimmed !== '') {
            params.busca = trimmed;
        }

        if (nextRole !== 'todos') {
            params.cargo = nextRole;
        }

        if (nextStatus !== 'todos') {
            params.status = nextStatus;
        }

        router.get(route('admin.users.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleSearchSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, filters.cargo, filters.status);
    };

    const closeModal = () => {
        setOpen(false);
        setEditingUser(null);
        reset();
        clearErrors();
    };

    const openCreateModal = () => {
        setEditingUser(null);
        setData({
            name: '',
            email: '',
            phone: '',
            role: 'storekeeper',
            password: '',
            password_confirmation: '',
            active: true,
        });
        clearErrors();
        setOpen(true);
    };

    const openEditModal = (user: StaffUser) => {
        setEditingUser(user);
        setData({
            name: user.name,
            email: user.email,
            phone: user.phone ?? '',
            role: user.role,
            password: '',
            password_confirmation: '',
            active: user.active,
        });
        clearErrors();
        setOpen(true);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        };

        if (editingUser) {
            put(route('admin.users.update', editingUser.id), options);

            return;
        }

        post(route('admin.users.store'), options);
    };

    const handleToggleStatus = (user: StaffUser) => {
        if (togglingId === user.id || user.id === currentUserId) {
            return;
        }

        const actionLabel = user.active ? 'desativar' : 'reativar';

        if (
            !confirm(
                `Deseja ${actionLabel} o acesso de "${user.name}" (${user.role_label})?`,
            )
        ) {
            return;
        }

        router.patch(
            route('admin.users.toggle-status', user.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setTogglingId(user.id),
                onFinish: () => setTogglingId(null),
            },
        );
    };

    const editingSelf = editingUser?.id === currentUserId;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Usuários" />

            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <section className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-[var(--admin-text)]">
                            Usuários da equipe
                        </h1>

                        <p className="mt-1 text-sm text-[var(--admin-text-muted)]">
                            Cadastre colaboradores, defina cargos e controle o
                            acesso ao painel administrativo.
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={openCreateModal}
                        className="auth-primary-button inline-flex h-10 items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold text-white"
                    >
                        <Plus className="size-4" />
                        Novo usuário
                    </button>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard
                        title="Equipe cadastrada"
                        value={stats.total}
                        description="Contas internas do painel"
                        icon={Users}
                    />

                    <SummaryCard
                        title="Ativos"
                        value={stats.active}
                        description="Com acesso liberado"
                        icon={UserCheck}
                    />

                    <SummaryCard
                        title="Inativos"
                        value={stats.inactive}
                        description="Acessos desativados"
                        icon={UserX}
                    />

                    <SummaryCard
                        title="Administradores"
                        value={stats.admins}
                        description="Administradores ativos"
                        icon={ShieldCheck}
                    />
                </section>

                {flash?.success && (
                    <div className="rounded-xl border border-[var(--admin-success-border)] bg-[var(--admin-success-background)] px-4 py-3 text-sm font-medium text-[var(--admin-success)]">
                        {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        {flash.error}
                    </div>
                )}

                <section className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] shadow-sm">
                    <div className="flex flex-col gap-4 border-b border-[var(--admin-border)] p-4 lg:flex-row lg:items-center lg:justify-between lg:p-5">
                        <form
                            onSubmit={handleSearchSubmit}
                            className="flex w-full max-w-md items-center gap-2"
                        >
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-[var(--admin-text-muted)]" />

                                <input
                                    type="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Buscar por nome, e-mail ou telefone..."
                                    className="h-10 w-full rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] pr-3 pl-9 text-sm text-[var(--admin-text)] outline-none placeholder:text-[var(--admin-text-muted)] focus:border-[var(--admin-primary)] focus:ring-2 focus:ring-[var(--admin-primary)]/10"
                                />
                            </div>

                            <Button
                                type="submit"
                                variant="outline"
                                className="h-10 rounded-xl border-[var(--admin-border)] px-4 text-sm text-[var(--admin-text)]"
                            >
                                Buscar
                            </Button>
                        </form>

                        <div className="flex flex-wrap items-center gap-2">
                            <select
                                aria-label="Filtrar por cargo"
                                value={filters.cargo}
                                onChange={(e) =>
                                    applyFilters(
                                        search,
                                        e.target.value as StaffRoleFilter,
                                        filters.status,
                                    )
                                }
                                className="h-9 rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] px-3 text-xs font-medium text-[var(--admin-text)] outline-none focus:border-[var(--admin-primary)]"
                            >
                                <option value="todos">Todos os cargos</option>
                                {roles.map((roleOption) => (
                                    <option
                                        key={roleOption.value}
                                        value={roleOption.value}
                                    >
                                        {roleOption.label}
                                    </option>
                                ))}
                            </select>

                            {statusOptions.map((option) => {
                                const selected =
                                    filters.status === option.value;

                                return (
                                    <button
                                        key={option.value}
                                        type="button"
                                        onClick={() =>
                                            applyFilters(
                                                search,
                                                filters.cargo,
                                                option.value,
                                            )
                                        }
                                        className={`h-9 rounded-xl border px-3 text-xs font-medium transition-colors ${
                                            selected
                                                ? 'border-[var(--admin-primary)] bg-[var(--admin-background-soft)] text-[var(--admin-primary)]'
                                                : 'border-[var(--admin-border)] bg-[var(--admin-surface)] text-[var(--admin-text-muted)] hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-text)]'
                                        }`}
                                    >
                                        {option.label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[780px]">
                            <thead className="bg-[var(--admin-background-soft)]">
                                <tr className="border-b border-[var(--admin-border)]">
                                    <TableHeader>Usuário</TableHeader>
                                    <TableHeader>Cargo</TableHeader>
                                    <TableHeader>Telefone</TableHeader>
                                    <TableHeader>Status</TableHeader>
                                    <TableHeader className="text-right">
                                        Ações
                                    </TableHeader>
                                </tr>
                            </thead>

                            <tbody>
                                {users.data.length > 0 ? (
                                    users.data.map((user) => {
                                        const isSelf =
                                            user.id === currentUserId;

                                        return (
                                            <tr
                                                key={user.id}
                                                className="border-b border-[var(--admin-border)] transition-colors last:border-b-0 hover:bg-[var(--admin-background-soft)]"
                                            >
                                                <TableCell>
                                                    <div className="min-w-0">
                                                        <div className="flex items-center gap-2">
                                                            <p className="truncate font-medium text-[var(--admin-text)]">
                                                                {user.name}
                                                            </p>

                                                            {isSelf && (
                                                                <span className="inline-flex rounded-full bg-[var(--admin-background-soft)] px-2 py-0.5 text-[11px] font-medium text-[var(--admin-primary)]">
                                                                    Você
                                                                </span>
                                                            )}
                                                        </div>

                                                        <p className="mt-1 truncate text-xs text-[var(--admin-text-muted)]">
                                                            {user.email}
                                                        </p>
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <RoleBadge
                                                        role={user.role}
                                                        label={user.role_label}
                                                    />
                                                </TableCell>

                                                <TableCell>
                                                    {user.phone ?? '—'}
                                                </TableCell>

                                                <TableCell>
                                                    <StatusBadge
                                                        active={user.active}
                                                    />
                                                </TableCell>

                                                <TableCell className="text-right">
                                                    <div className="inline-flex items-center gap-1">
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                openEditModal(
                                                                    user,
                                                                )
                                                            }
                                                            className="inline-flex size-9 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-primary)]"
                                                            aria-label={`Editar ${user.name}`}
                                                            title="Editar usuário"
                                                        >
                                                            <Pencil className="size-4" />
                                                        </button>

                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                handleToggleStatus(
                                                                    user,
                                                                )
                                                            }
                                                            disabled={
                                                                isSelf ||
                                                                togglingId ===
                                                                    user.id
                                                            }
                                                            className={`inline-flex size-9 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors disabled:cursor-not-allowed disabled:opacity-35 ${
                                                                user.active
                                                                    ? 'hover:bg-amber-50 hover:text-amber-700'
                                                                    : 'hover:bg-green-50 hover:text-green-700'
                                                            }`}
                                                            aria-label={
                                                                user.active
                                                                    ? `Desativar ${user.name}`
                                                                    : `Ativar ${user.name}`
                                                            }
                                                            title={
                                                                isSelf
                                                                    ? 'Você não pode desativar a própria conta'
                                                                    : user.active
                                                                      ? 'Desativar usuário'
                                                                      : 'Ativar usuário'
                                                            }
                                                        >
                                                            <Power className="size-4" />
                                                        </button>
                                                    </div>
                                                </TableCell>
                                            </tr>
                                        );
                                    })
                                ) : (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-12 text-center"
                                        >
                                            <p className="text-sm text-[var(--admin-text-muted)]">
                                                Nenhum usuário encontrado para
                                                os filtros selecionados.
                                            </p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {users.last_page > 1 && (
                        <div className="flex flex-col items-center justify-between gap-3 border-t border-[var(--admin-border)] px-4 py-4 sm:flex-row md:px-5">
                            <p className="text-xs text-[var(--admin-text-muted)]">
                                Mostrando {users.from ?? 0} a {users.to ?? 0} de{' '}
                                {users.total} usuários
                            </p>

                            <nav
                                aria-label="Paginação de usuários"
                                className="flex flex-wrap items-center gap-1"
                            >
                                {users.links.map((link, index) => {
                                    const label = formatPaginationLabel(
                                        link.label,
                                    );

                                    if (!link.url) {
                                        return (
                                            <span
                                                key={`${index}-${label}`}
                                                className="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-xs text-[var(--admin-text-muted)]/50"
                                            >
                                                {label}
                                            </span>
                                        );
                                    }

                                    return (
                                        <Link
                                            key={`${index}-${label}`}
                                            href={link.url}
                                            preserveScroll
                                            preserveState
                                            className={`inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-xs font-medium transition-colors ${
                                                link.active
                                                    ? 'bg-[var(--admin-primary)] text-white'
                                                    : 'border border-[var(--admin-border)] text-[var(--admin-text-muted)] hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-text)]'
                                            }`}
                                        >
                                            {label}
                                        </Link>
                                    );
                                })}
                            </nav>
                        </div>
                    )}
                </section>
            </main>

            <Dialog open={open} onOpenChange={(val) => !val && closeModal()}>
                <DialogContent className="max-w-lg border-[var(--admin-border)] bg-[var(--admin-surface)] p-6">
                    <DialogHeader>
                        <DialogTitle className="text-lg font-semibold text-[var(--admin-text)]">
                            {editingUser
                                ? 'Editar usuário da equipe'
                                : 'Novo usuário da equipe'}
                        </DialogTitle>
                        <DialogDescription className="text-sm text-[var(--admin-text-muted)]">
                            {editingUser
                                ? 'Atualize dados, cargo ou defina uma nova senha se necessário.'
                                : 'Preencha os dados e escolha o cargo de acesso ao painel.'}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submit} className="mt-4 space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="staff-name">Nome *</Label>
                            <Input
                                id="staff-name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                placeholder="Nome completo"
                                required
                                className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)]"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="staff-email">E-mail *</Label>
                                <Input
                                    id="staff-email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                    placeholder="colaborador@loja.com"
                                    required
                                    className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)]"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="staff-phone">Telefone</Label>
                                <Input
                                    id="staff-phone"
                                    value={data.phone}
                                    onChange={(e) =>
                                        setData('phone', e.target.value)
                                    }
                                    placeholder="(67) 99999-9999"
                                    className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)]"
                                />
                                <InputError message={errors.phone} />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="staff-role">Cargo *</Label>
                            <select
                                id="staff-role"
                                value={data.role}
                                disabled={editingSelf}
                                onChange={(e) =>
                                    setData('role', e.target.value as UserRole)
                                }
                                className="h-11 w-full rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] px-3 text-sm text-[var(--admin-text)] outline-none focus:border-[var(--admin-primary)] disabled:opacity-60"
                            >
                                {roles.map((roleOption) => (
                                    <option
                                        key={roleOption.value}
                                        value={roleOption.value}
                                    >
                                        {roleOption.label}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.role} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="staff-password">
                                    {editingUser
                                        ? 'Nova senha (opcional)'
                                        : 'Senha *'}
                                </Label>
                                <Input
                                    id="staff-password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) =>
                                        setData('password', e.target.value)
                                    }
                                    required={!editingUser}
                                    placeholder={
                                        editingUser
                                            ? 'Deixe vazio para manter'
                                            : 'Mínimo 8 caracteres'
                                    }
                                    className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)]"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="staff-password-confirm">
                                    Confirmar senha
                                </Label>
                                <Input
                                    id="staff-password-confirm"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) =>
                                        setData(
                                            'password_confirmation',
                                            e.target.value,
                                        )
                                    }
                                    required={!editingUser || data.password !== ''}
                                    placeholder="Repita a senha"
                                    className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)]"
                                />
                            </div>
                        </div>

                        <label
                            htmlFor="staff-active"
                            className="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] p-4"
                        >
                            <div>
                                <p className="text-sm font-medium text-[var(--admin-text)]">
                                    Conta ativa
                                </p>
                                <p className="mt-1 text-xs text-[var(--admin-text-muted)]">
                                    Contas desativadas não conseguem acessar o
                                    painel.
                                </p>
                            </div>

                            <input
                                id="staff-active"
                                type="checkbox"
                                checked={data.active}
                                disabled={editingSelf}
                                onChange={(e) =>
                                    setData('active', e.target.checked)
                                }
                                className="size-4 accent-[var(--admin-primary)] disabled:opacity-50"
                            />
                        </label>
                        <InputError message={errors.active} />

                        <DialogFooter className="gap-2 pt-2 sm:gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={closeModal}
                                disabled={processing}
                                className="rounded-xl border-[var(--admin-border)]"
                            >
                                Cancelar
                            </Button>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="auth-primary-button rounded-xl text-white"
                            >
                                {processing
                                    ? 'Salvando...'
                                    : editingUser
                                      ? 'Salvar alterações'
                                      : 'Criar usuário'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function SummaryCard({
    title,
    value,
    description,
    icon: Icon,
}: {
    title: string;
    value: number;
    description: string;
    icon: React.ElementType;
}) {
    return (
        <article className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-medium text-[var(--admin-text-muted)]">
                        {title}
                    </p>

                    <p className="mt-2 text-2xl font-semibold text-[var(--admin-text)]">
                        {value}
                    </p>
                </div>

                <div className="flex size-10 items-center justify-center rounded-xl bg-[var(--admin-background-soft)] text-[var(--admin-primary)]">
                    <Icon className="size-5" aria-hidden="true" />
                </div>
            </div>

            <p className="mt-3 text-xs text-[var(--admin-text-muted)]">
                {description}
            </p>
        </article>
    );
}

function RoleBadge({ role, label }: { role: UserRole; label: string }) {
    const classes =
        role === 'admin'
            ? 'bg-purple-50 text-purple-700'
            : role === 'manager'
              ? 'bg-blue-50 text-blue-700'
              : role === 'storekeeper'
                ? 'bg-amber-50 text-amber-700'
                : 'bg-neutral-100 text-neutral-700';

    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${classes}`}
        >
            {label}
        </span>
    );
}

function StatusBadge({ active }: { active: boolean }) {
    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                active
                    ? 'bg-green-50 text-green-700'
                    : 'bg-red-50 text-red-700'
            }`}
        >
            {active ? 'Ativo' : 'Inativo'}
        </span>
    );
}

function TableHeader({
    children,
    className = '',
}: {
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <th
            className={`px-4 py-3 text-left text-xs font-semibold tracking-wide text-[var(--admin-text-muted)] uppercase ${className}`}
        >
            {children}
        </th>
    );
}

function TableCell({
    children,
    className = '',
}: {
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <td
            className={`px-4 py-4 text-sm text-[var(--admin-text-muted)] ${className}`}
        >
            {children}
        </td>
    );
}

function formatPaginationLabel(raw: string): string {
    if (raw.includes('Previous') || raw.includes('&laquo;')) {
        return 'Anterior';
    }

    if (raw.includes('Next') || raw.includes('&raquo;')) {
        return 'Próxima';
    }

    return raw;
}
