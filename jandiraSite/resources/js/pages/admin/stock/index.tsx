import { Button } from '@/components/ui/button';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { AlertTriangle, Boxes, Check, Layers, Minus, PackageX, Pencil, Plus, Search } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

type StockStatusFilter = 'todos' | 'disponivel' | 'baixo' | 'zerado';

type StockVariant = {
    id: number;
    name: string;
    sku: string | null;
    price: string;
    stock: number;
    active: boolean;
    product: {
        id: number;
        name: string;
        active: boolean;
        category: {
            id: number;
            name: string;
        } | null;
    } | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedVariants = {
    data: StockVariant[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

type StockStats = {
    total_units: number;
    total_variants: number;
    low_stock: number;
    out_of_stock: number;
    low_stock_threshold: number;
};

type StockFilters = {
    busca: string;
    status: StockStatusFilter;
};

type Props = {
    variants: PaginatedVariants;
    stats: StockStats;
    filters: StockFilters;
};

type PageProps = {
    flash?: {
        success?: string;
        error?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Estoque',
        href: '/admin/estoque',
    },
];

const statusOptions: { value: StockStatusFilter; label: string }[] = [
    { value: 'todos', label: 'Todos' },
    { value: 'disponivel', label: 'Disponível' },
    { value: 'baixo', label: 'Estoque baixo' },
    { value: 'zerado', label: 'Sem estoque' },
];

export default function StockIndex({ variants, stats, filters }: Props) {
    const { flash } = usePage<PageProps>().props;
    const { can } = usePermissions();
    const canManageStock = can('manage-stock');
    const canManageProducts = can('manage-products');
    const [search, setSearch] = useState(filters.busca);

    useEffect(() => {
        setSearch(filters.busca);
    }, [filters.busca]);

    const applyFilters = (nextSearch: string, nextStatus: StockStatusFilter) => {
        const params: Record<string, string> = {};
        const trimmed = nextSearch.trim();

        if (trimmed !== '') {
            params.busca = trimmed;
        }

        if (nextStatus !== 'todos') {
            params.status = nextStatus;
        }

        router.get(route('admin.stock.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleSearchSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, filters.status);
    };

    const handleStatusChange = (nextStatus: StockStatusFilter) => {
        applyFilters(search, nextStatus);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Estoque" />

            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <section>
                    <h1 className="text-2xl font-semibold text-[var(--admin-text)]">Estoque</h1>

                    <p className="mt-1 text-sm text-[var(--admin-text-muted)]">
                        Acompanhe as quantidades disponíveis e ajuste o estoque de cada variação rapidamente.
                    </p>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard title="Unidades em estoque" value={stats.total_units} description="Soma total de todas as variações" icon={Boxes} />

                    <SummaryCard title="Variações" value={stats.total_variants} description="Opções de produtos cadastradas" icon={Layers} />

                    <SummaryCard
                        title="Estoque baixo"
                        value={stats.low_stock}
                        description={`Entre 1 e ${stats.low_stock_threshold} unidades`}
                        icon={AlertTriangle}
                        tone="warning"
                        active={filters.status === 'baixo'}
                        onClick={() => handleStatusChange(filters.status === 'baixo' ? 'todos' : 'baixo')}
                    />

                    <SummaryCard
                        title="Sem estoque"
                        value={stats.out_of_stock}
                        description="Variações esgotadas (0 unidades)"
                        icon={PackageX}
                        tone="danger"
                        active={filters.status === 'zerado'}
                        onClick={() => handleStatusChange(filters.status === 'zerado' ? 'todos' : 'zerado')}
                    />
                </section>

                {flash?.success && (
                    <div className="rounded-xl border border-[var(--admin-success-border)] bg-[var(--admin-success-background)] px-4 py-3 text-sm font-medium text-[var(--admin-success)]">
                        {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{flash.error}</div>
                )}

                <section className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] shadow-sm">
                    <div className="flex flex-col gap-4 border-b border-[var(--admin-border)] p-4 md:flex-row md:items-center md:justify-between md:p-5">
                        <form onSubmit={handleSearchSubmit} className="flex w-full max-w-md items-center gap-2">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-[var(--admin-text-muted)]" />

                                <input
                                    type="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Buscar por produto, variação ou SKU..."
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
                            {statusOptions.map((option) => {
                                const selected = filters.status === option.value;

                                return (
                                    <button
                                        key={option.value}
                                        type="button"
                                        onClick={() => handleStatusChange(option.value)}
                                        className={`h-9 rounded-xl border px-3 text-xs font-medium transition-colors ${
                                            selected
                                                ? 'border-[var(--admin-primary)] bg-[var(--admin-background-soft)] text-[var(--admin-primary)]'
                                                : 'border-[var(--admin-border)] bg-[var(--admin-surface)] text-[var(--admin-text-muted)] hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-text)]'
                                        } `}
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
                                    <TableHeader>Produto</TableHeader>
                                    <TableHeader>Variação / SKU</TableHeader>
                                    <TableHeader>Situação</TableHeader>
                                    <TableHeader>Estoque</TableHeader>
                                    {canManageProducts && <TableHeader className="text-right">Ações</TableHeader>}
                                </tr>
                            </thead>

                            <tbody>
                                {variants.data.length > 0 ? (
                                    variants.data.map((variant) => (
                                        <StockRow
                                            key={variant.id}
                                            variant={variant}
                                            lowThreshold={stats.low_stock_threshold}
                                            canManageStock={canManageStock}
                                            canManageProducts={canManageProducts}
                                        />
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={canManageProducts ? 5 : 4} className="px-4 py-12 text-center">
                                            <p className="text-sm text-[var(--admin-text-muted)]">
                                                Nenhuma variação encontrada para os filtros selecionados.
                                            </p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {variants.last_page > 1 && (
                        <div className="flex flex-col items-center justify-between gap-3 border-t border-[var(--admin-border)] px-4 py-4 sm:flex-row md:px-5">
                            <p className="text-xs text-[var(--admin-text-muted)]">
                                Mostrando {variants.from ?? 0} a {variants.to ?? 0} de {variants.total} variações
                            </p>

                            <nav aria-label="Paginação do estoque" className="flex flex-wrap items-center gap-1">
                                {variants.links.map((link, index) => {
                                    const label = formatPaginationLabel(link.label);

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
                                            } `}
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

function StockRow({
    variant,
    lowThreshold,
    canManageStock,
    canManageProducts,
}: {
    variant: StockVariant;
    lowThreshold: number;
    canManageStock: boolean;
    canManageProducts: boolean;
}) {
    const [stockValue, setStockValue] = useState(String(variant.stock));
    const [saving, setSaving] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    useEffect(() => {
        setStockValue(String(variant.stock));
        setErrorMessage(null);
    }, [variant.stock]);

    const parsed = Number.parseInt(stockValue, 10);
    const isValid = stockValue.trim() !== '' && !Number.isNaN(parsed) && parsed >= 0 && String(parsed) === stockValue.trim();
    const isDirty = isValid && parsed !== variant.stock;

    const adjustBy = (delta: number) => {
        const base = isValid ? parsed : variant.stock;
        const next = Math.max(0, base + delta);
        setStockValue(String(next));
        setErrorMessage(null);
    };

    const handleSave = () => {
        if (!isValid || !isDirty || saving) {
            return;
        }

        router.patch(
            route('admin.stock.update', variant.id),
            { stock: parsed },
            {
                preserveScroll: true,
                onStart: () => {
                    setSaving(true);
                    setErrorMessage(null);
                },
                onFinish: () => setSaving(false),
                onError: (errors) => {
                    setErrorMessage(errors.stock ?? 'Não foi possível atualizar o estoque.');
                },
            },
        );
    };

    const isInactive = !variant.active || variant.product?.active === false;

    return (
        <tr className="border-b border-[var(--admin-border)] transition-colors last:border-b-0 hover:bg-[var(--admin-background-soft)]">
            <TableCell>
                <div className="min-w-0">
                    <p className="truncate font-medium text-[var(--admin-text)]">{variant.product?.name ?? 'Produto removido'}</p>

                    <p className="mt-1 truncate text-xs text-[var(--admin-text-muted)]">{variant.product?.category?.name ?? 'Sem categoria'}</p>
                </div>
            </TableCell>

            <TableCell>
                <div className="min-w-0">
                    <p className="font-medium text-[var(--admin-text)]">{variant.name}</p>

                    <p className="mt-1 text-xs text-[var(--admin-text-muted)]">{variant.sku ? `SKU: ${variant.sku}` : 'Sem SKU'}</p>
                </div>
            </TableCell>

            <TableCell>
                <div className="flex flex-wrap items-center gap-1.5">
                    <StockBadge stock={variant.stock} lowThreshold={lowThreshold} />

                    {isInactive && (
                        <span className="inline-flex rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600">Inativo</span>
                    )}
                </div>
            </TableCell>

            <TableCell>
                {canManageStock ? (
                    <div className="space-y-1">
                        <div className="inline-flex items-center gap-1.5">
                            <button
                                type="button"
                                onClick={() => adjustBy(-1)}
                                disabled={saving || (isValid && parsed <= 0)}
                                aria-label={`Diminuir estoque de ${variant.name}`}
                                className="inline-flex size-8 items-center justify-center rounded-lg border border-[var(--admin-border)] bg-[var(--admin-surface)] text-[var(--admin-text-muted)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-text)] disabled:opacity-40"
                            >
                                <Minus className="size-3.5" />
                            </button>

                            <input
                                type="number"
                                min={0}
                                step={1}
                                value={stockValue}
                                onChange={(e) => {
                                    setStockValue(e.target.value);
                                    setErrorMessage(null);
                                }}
                                onKeyDown={(e) => {
                                    if (e.key === 'Enter') {
                                        e.preventDefault();
                                        handleSave();
                                    }
                                }}
                                aria-label={`Estoque de ${variant.name}`}
                                className="h-8 w-20 rounded-lg border border-[var(--admin-border)] bg-[var(--admin-background)] px-2 text-center text-sm font-medium text-[var(--admin-text)] outline-none focus:border-[var(--admin-primary)] focus:ring-2 focus:ring-[var(--admin-primary)]/10"
                            />

                            <button
                                type="button"
                                onClick={() => adjustBy(1)}
                                disabled={saving}
                                aria-label={`Aumentar estoque de ${variant.name}`}
                                className="inline-flex size-8 items-center justify-center rounded-lg border border-[var(--admin-border)] bg-[var(--admin-surface)] text-[var(--admin-text-muted)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-text)] disabled:opacity-40"
                            >
                                <Plus className="size-3.5" />
                            </button>

                            <button
                                type="button"
                                onClick={handleSave}
                                disabled={!isDirty || saving}
                                className="auth-primary-button inline-flex h-8 items-center gap-1.5 rounded-lg px-3 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                <Check className="size-3.5" />
                                {saving ? 'Salvando...' : 'Salvar'}
                            </button>
                        </div>

                        {errorMessage && <p className="text-xs text-red-600">{errorMessage}</p>}
                    </div>
                ) : (
                    <span className="font-medium text-[var(--admin-text)]">
                        {variant.stock} {variant.stock === 1 ? 'unidade' : 'unidades'}
                    </span>
                )}
            </TableCell>

            {canManageProducts && (
                <TableCell className="text-right">
                    {variant.product && (
                        <Link
                            href={`/admin/produtos/${variant.product.id}/edit`}
                            className="inline-flex size-9 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-primary)]"
                            aria-label={`Editar produto ${variant.product.name}`}
                            title="Editar produto"
                        >
                            <Pencil className="size-4" />
                        </Link>
                    )}
                </TableCell>
            )}
        </tr>
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
    tone?: 'default' | 'warning' | 'danger';
    active?: boolean;
    onClick?: () => void;
}) {
    const iconTone =
        tone === 'danger'
            ? 'bg-red-50 text-red-600'
            : tone === 'warning'
              ? 'bg-amber-50 text-amber-600'
              : 'bg-[var(--admin-background-soft)] text-[var(--admin-primary)]';

    const content = (
        <>
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-medium text-[var(--admin-text-muted)]">{title}</p>

                    <p className="mt-2 text-2xl font-semibold text-[var(--admin-text)]">{value}</p>
                </div>

                <div className={`flex size-10 items-center justify-center rounded-xl ${iconTone}`}>
                    <Icon className="size-5" aria-hidden="true" />
                </div>
            </div>

            <p className="mt-3 text-xs text-[var(--admin-text-muted)]">{description}</p>
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
                } `}
            >
                {content}
            </button>
        );
    }

    return <article className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm">{content}</article>;
}

function StockBadge({ stock, lowThreshold }: { stock: number; lowThreshold: number }) {
    if (stock <= 0) {
        return <span className="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">Sem estoque</span>;
    }

    if (stock <= lowThreshold) {
        return <span className="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">Estoque baixo</span>;
    }

    return <span className="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">Disponível</span>;
}

function TableHeader({ children, className = '' }: { children: React.ReactNode; className?: string }) {
    return (
        <th className={`px-4 py-3 text-left text-xs font-semibold tracking-wide text-[var(--admin-text-muted)] uppercase ${className} `}>
            {children}
        </th>
    );
}

function TableCell({ children, className = '' }: { children: React.ReactNode; className?: string }) {
    return <td className={`px-4 py-4 text-sm text-[var(--admin-text-muted)] ${className} `}>{children}</td>;
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
