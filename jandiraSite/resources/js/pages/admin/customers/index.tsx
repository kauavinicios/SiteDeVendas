import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MailCheck, Power, Search, UserCheck, UserX, Users } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

type StatusFilter = 'todos' | 'ativos' | 'inativos';
type NewsletterFilter = 'todos' | 'inscritos' | 'nao-inscritos';

type CustomerItem = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    active: boolean;
    marketing_opt_in: boolean;
    marketing_opt_in_at: string | null;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedCustomers = {
    data: CustomerItem[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

type CustomerStats = {
    total: number;
    active: number;
    subscribed: number;
    inactive: number;
};

type CustomerFilters = {
    busca: string;
    status: StatusFilter;
    novidades: NewsletterFilter;
};

type Props = {
    customers: PaginatedCustomers;
    stats: CustomerStats;
    filters: CustomerFilters;
};

type PageProps = SharedData & {
    flash?: {
        success?: string;
        error?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clientes',
        href: '/admin/clientes',
    },
];

const statusOptions: { value: StatusFilter; label: string }[] = [
    { value: 'todos', label: 'Todos' },
    { value: 'ativos', label: 'Ativos' },
    { value: 'inativos', label: 'Inativos' },
];

export default function CustomersIndex({ customers, stats, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const { can } = usePermissions();
    const canManageUsers = can('manage-users');

    const [search, setSearch] = useState(filters.busca);
    const [togglingId, setTogglingId] = useState<number | null>(null);

    useEffect(() => {
        setSearch(filters.busca);
    }, [filters.busca]);

    const applyFilters = (
        nextSearch: string,
        nextStatus: StatusFilter,
        nextNewsletter: NewsletterFilter,
    ) => {
        const params: Record<string, string> = {};
        const trimmed = nextSearch.trim();

        if (trimmed !== '') {
            params.busca = trimmed;
        }

        if (nextStatus !== 'todos') {
            params.status = nextStatus;
        }

        if (nextNewsletter !== 'todos') {
            params.novidades = nextNewsletter;
        }

        router.get(route('admin.customers.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleSearchSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, filters.status, filters.novidades);
    };

    const handleToggleStatus = (customer: CustomerItem) => {
        if (!canManageUsers || togglingId === customer.id) {
            return;
        }

        const actionLabel = customer.active ? 'desativar' : 'reativar';

        if (!confirm(`Deseja ${actionLabel} a conta de "${customer.name}"?`)) {
            return;
        }

        router.patch(
            route('admin.customers.toggle-status', customer.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setTogglingId(customer.id),
                onFinish: () => setTogglingId(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Clientes" />

            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <section>
                    <h1 className="text-2xl font-semibold text-[var(--admin-text)]">
                        Clientes
                    </h1>

                    <p className="mt-1 text-sm text-[var(--admin-text-muted)]">
                        Consulte os clientes cadastrados na loja e acompanhe as
                        inscrições para novidades por e-mail.
                    </p>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard
                        title="Clientes cadastrados"
                        value={stats.total}
                        description="Total de contas na loja"
                        icon={Users}
                    />

                    <SummaryCard
                        title="Ativos"
                        value={stats.active}
                        description="Contas habilitadas"
                        icon={UserCheck}
                    />

                    <SummaryCard
                        title="Inscritos em novidades"
                        value={stats.subscribed}
                        description="Aceitaram receber e-mails (LGPD)"
                        icon={MailCheck}
                        active={filters.novidades === 'inscritos'}
                        onClick={() =>
                            applyFilters(
                                search,
                                filters.status,
                                filters.novidades === 'inscritos'
                                    ? 'todos'
                                    : 'inscritos',
                            )
                        }
                    />

                    <SummaryCard
                        title="Inativos"
                        value={stats.inactive}
                        description="Contas desativadas"
                        icon={UserX}
                        tone="danger"
                        active={filters.status === 'inativos'}
                        onClick={() =>
                            applyFilters(
                                search,
                                filters.status === 'inativos'
                                    ? 'todos'
                                    : 'inativos',
                                filters.novidades,
                            )
                        }
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
                                aria-label="Filtrar por inscrição de novidades"
                                value={filters.novidades}
                                onChange={(e) =>
                                    applyFilters(
                                        search,
                                        filters.status,
                                        e.target.value as NewsletterFilter,
                                    )
                                }
                                className="h-9 rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] px-3 text-xs font-medium text-[var(--admin-text)] outline-none focus:border-[var(--admin-primary)]"
                            >
                                <option value="todos">
                                    Novidades: Todos
                                </option>
                                <option value="inscritos">
                                    Inscritos (e-mail)
                                </option>
                                <option value="nao-inscritos">
                                    Não inscritos
                                </option>
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
                                                option.value,
                                                filters.novidades,
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
                                    <TableHeader>Cliente</TableHeader>
                                    <TableHeader>Telefone</TableHeader>
                                    <TableHeader>
                                        Novidades por e-mail
                                    </TableHeader>
                                    <TableHeader>Cadastro</TableHeader>
                                    <TableHeader>Status</TableHeader>
                                    {canManageUsers && (
                                        <TableHeader className="text-right">
                                            Ações
                                        </TableHeader>
                                    )}
                                </tr>
                            </thead>

                            <tbody>
                                {customers.data.length > 0 ? (
                                    customers.data.map((customer) => (
                                        <tr
                                            key={customer.id}
                                            className="border-b border-[var(--admin-border)] transition-colors last:border-b-0 hover:bg-[var(--admin-background-soft)]"
                                        >
                                            <TableCell>
                                                <div className="min-w-0">
                                                    <p className="truncate font-medium text-[var(--admin-text)]">
                                                        {customer.name}
                                                    </p>

                                                    <p className="mt-1 truncate text-xs text-[var(--admin-text-muted)]">
                                                        {customer.email}
                                                    </p>
                                                </div>
                                            </TableCell>

                                            <TableCell>
                                                {customer.phone ?? '—'}
                                            </TableCell>

                                            <TableCell>
                                                {customer.marketing_opt_in ? (
                                                    <div>
                                                        <span className="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                                            Inscrito
                                                        </span>
                                                        {customer.marketing_opt_in_at && (
                                                            <p className="mt-1 text-[11px] text-[var(--admin-text-muted)]">
                                                                Aceite:{' '}
                                                                {
                                                                    customer.marketing_opt_in_at
                                                                }
                                                            </p>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <span className="inline-flex rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-600">
                                                        Não inscrito
                                                    </span>
                                                )}
                                            </TableCell>

                                            <TableCell>
                                                {customer.created_at ?? '—'}
                                            </TableCell>

                                            <TableCell>
                                                <StatusBadge
                                                    active={customer.active}
                                                />
                                            </TableCell>

                                            {canManageUsers && (
                                                <TableCell className="text-right">
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            handleToggleStatus(
                                                                customer,
                                                            )
                                                        }
                                                        disabled={
                                                            togglingId ===
                                                            customer.id
                                                        }
                                                        className={`inline-flex size-9 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors disabled:opacity-40 ${
                                                            customer.active
                                                                ? 'hover:bg-amber-50 hover:text-amber-700'
                                                                : 'hover:bg-green-50 hover:text-green-700'
                                                        }`}
                                                        aria-label={
                                                            customer.active
                                                                ? `Desativar ${customer.name}`
                                                                : `Ativar ${customer.name}`
                                                        }
                                                        title={
                                                            customer.active
                                                                ? 'Desativar cliente'
                                                                : 'Ativar cliente'
                                                        }
                                                    >
                                                        <Power className="size-4" />
                                                    </button>
                                                </TableCell>
                                            )}
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td
                                            colSpan={canManageUsers ? 6 : 5}
                                            className="px-4 py-12 text-center"
                                        >
                                            <p className="text-sm text-[var(--admin-text-muted)]">
                                                Nenhum cliente encontrado para
                                                os filtros selecionados.
                                            </p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {customers.last_page > 1 && (
                        <div className="flex flex-col items-center justify-between gap-3 border-t border-[var(--admin-border)] px-4 py-4 sm:flex-row md:px-5">
                            <p className="text-xs text-[var(--admin-text-muted)]">
                                Mostrando {customers.from ?? 0} a{' '}
                                {customers.to ?? 0} de {customers.total}{' '}
                                clientes
                            </p>

                            <nav
                                aria-label="Paginação de clientes"
                                className="flex flex-wrap items-center gap-1"
                            >
                                {customers.links.map((link, index) => {
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
        </AppLayout>
    );
}

function SummaryCard({
    title,
    value,
    description,
    icon: Icon,
    tone = 'default',
    active = false,
    onClick,
}: {
    title: string;
    value: number;
    description: string;
    icon: React.ElementType;
    tone?: 'default' | 'danger';
    active?: boolean;
    onClick?: () => void;
}) {
    const iconTone =
        tone === 'danger'
            ? 'bg-red-50 text-red-600'
            : 'bg-[var(--admin-background-soft)] text-[var(--admin-primary)]';

    const content = (
        <>
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-medium text-[var(--admin-text-muted)]">
                        {title}
                    </p>

                    <p className="mt-2 text-2xl font-semibold text-[var(--admin-text)]">
                        {value}
                    </p>
                </div>

                <div
                    className={`flex size-10 items-center justify-center rounded-xl ${iconTone}`}
                >
                    <Icon className="size-5" aria-hidden="true" />
                </div>
            </div>

            <p className="mt-3 text-xs text-[var(--admin-text-muted)]">
                {description}
            </p>
        </>
    );

    if (onClick) {
        return (
            <button
                type="button"
                onClick={onClick}
                className={`rounded-2xl border bg-[var(--admin-surface)] p-5 text-left shadow-sm transition-colors ${
                    active
                        ? 'border-[var(--admin-primary)] ring-2 ring-[var(--admin-primary)]/15'
                        : 'border-[var(--admin-border)] hover:border-[var(--admin-primary-light)]'
                }`}
            >
                {content}
            </button>
        );
    }

    return (
        <article className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm">
            {content}
        </article>
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
