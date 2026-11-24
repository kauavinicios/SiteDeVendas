import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Boxes,
    Pencil,
    Plus,
    Search,
} from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

type ProductStatusFilter = 'todos' | 'ativos' | 'inativos' | 'destaques';

type Category = {
    id: number;
    name: string;
};

type Variant = {
    id: number;
    name: string;
    price: string;
    stock: number;
    active: boolean;
};

type Product = {
    id: number;
    name: string;
    slug: string;
    active: boolean;
    featured: boolean;
    category: Category | null;
    variants: Variant[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedProducts = {
    data: Product[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

type ProductFilters = {
    busca: string;
    status: ProductStatusFilter;
};

type Props = {
    products: PaginatedProducts;
    filters: ProductFilters;
};

type Flash = {
    success?: string;
    error?: string;
};

type PageProps = {
    flash?: Flash;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Produtos',
        href: '/admin/produtos',
    },
];

const statusOptions: { value: ProductStatusFilter; label: string }[] = [
    { value: 'todos', label: 'Todos' },
    { value: 'ativos', label: 'Ativos' },
    { value: 'inativos', label: 'Inativos' },
    { value: 'destaques', label: 'Destaques' },
];

export default function ProductsIndex({
    products,
    filters,
}: Props) {
    const { flash } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.busca);

    useEffect(() => {
        setSearch(filters.busca);
    }, [filters.busca]);

    const applyFilters = (nextSearch: string, nextStatus: ProductStatusFilter) => {
        const params: Record<string, string> = {};
        const trimmed = nextSearch.trim();

        if (trimmed !== '') {
            params.busca = trimmed;
        }

        if (nextStatus !== 'todos') {
            params.status = nextStatus;
        }

        router.get(route('admin.products.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleSearchSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, filters.status);
    };

    const handleStatusChange = (nextStatus: ProductStatusFilter) => {
        applyFilters(search, nextStatus);
    };

    const hasActiveFilter = filters.busca !== '' || filters.status !== 'todos';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Produtos" />

            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <section className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-[var(--admin-text)]">
                            Produtos
                        </h1>

                        <p className="mt-1 text-sm text-[var(--admin-text-muted)]">
                            Gerencie os produtos disponíveis no catálogo da loja.
                        </p>
                    </div>

                    <Link
                        href="/admin/produtos/create"
                        className="
                            auth-primary-button
                            inline-flex
                            h-10
                            items-center
                            justify-center
                            gap-2
                            rounded-xl
                            px-4
                            text-sm
                            font-semibold
                            text-white
                        "
                    >
                        <Plus className="size-4" />

                        Novo produto
                    </Link>
                </section>

                {flash?.success && (
                    <div
                        className="
                            rounded-xl
                            border
                            border-[var(--admin-success-border)]
                            bg-[var(--admin-success-background)]
                            px-4
                            py-3
                            text-sm
                            font-medium
                            text-[var(--admin-success)]
                        "
                    >
                        {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        {flash.error}
                    </div>
                )}

                <section
                    className="
                        rounded-2xl
                        border
                        border-[var(--admin-border)]
                        bg-[var(--admin-surface)]
                        shadow-sm
                    "
                >
                    <div className="flex flex-col gap-4 border-b border-[var(--admin-border)] p-4 md:flex-row md:items-center md:justify-between md:p-5">
                        <form
                            onSubmit={handleSearchSubmit}
                            className="flex w-full max-w-md items-center gap-2"
                        >
                            <div className="relative flex-1">
                                <Search
                                    className="
                                        pointer-events-none
                                        absolute
                                        left-3
                                        top-1/2
                                        size-4
                                        -translate-y-1/2
                                        text-[var(--admin-text-muted)]
                                    "
                                />

                                <input
                                    type="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Buscar por produto, slug ou categoria..."
                                    className="
                                        h-10
                                        w-full
                                        rounded-xl
                                        border
                                        border-[var(--admin-border)]
                                        bg-[var(--admin-background)]
                                        pl-9
                                        pr-3
                                        text-sm
                                        text-[var(--admin-text)]
                                        outline-none
                                        placeholder:text-[var(--admin-text-muted)]
                                        focus:border-[var(--admin-primary)]
                                        focus:ring-2
                                        focus:ring-[var(--admin-primary)]/10
                                    "
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
                                        className={`
                                            h-9
                                            rounded-xl
                                            border
                                            px-3
                                            text-xs
                                            font-medium
                                            transition-colors
                                            ${
                                                selected
                                                    ? 'border-[var(--admin-primary)] bg-[var(--admin-background-soft)] text-[var(--admin-primary)]'
                                                    : 'border-[var(--admin-border)] bg-[var(--admin-surface)] text-[var(--admin-text-muted)] hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-text)]'
                                            }
                                        `}
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
                                    <TableHeader>
                                        Produto
                                    </TableHeader>

                                    <TableHeader>
                                        Categoria
                                    </TableHeader>

                                    <TableHeader>
                                        Variações
                                    </TableHeader>

                                    <TableHeader>
                                        Estoque
                                    </TableHeader>

                                    <TableHeader>
                                        Status
                                    </TableHeader>

                                    <TableHeader>
                                        Destaque
                                    </TableHeader>

                                    <TableHeader className="text-right">
                                        Ações
                                    </TableHeader>
                                </tr>
                            </thead>

                            <tbody>
                                {products.data.length > 0 ? (
                                    products.data.map((product) => {
                                        const totalStock =
                                            product.variants.reduce(
                                                (total, variant) =>
                                                    total + variant.stock,
                                                0,
                                            );

                                        return (
                                            <tr
                                                key={product.id}
                                                className="
                                                    border-b
                                                    border-[var(--admin-border)]
                                                    transition-colors
                                                    last:border-b-0
                                                    hover:bg-[var(--admin-background-soft)]
                                                "
                                            >
                                                <TableCell>
                                                    <div className="min-w-0">
                                                        <p className="truncate font-medium text-[var(--admin-text)]">
                                                            {product.name}
                                                        </p>

                                                        <p className="mt-1 truncate text-xs text-[var(--admin-text-muted)]">
                                                            {product.slug}
                                                        </p>
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    {product.category?.name ?? 'Sem categoria'}
                                                </TableCell>

                                                <TableCell>
                                                    {product.variants.length}
                                                </TableCell>

                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <Boxes className="size-4 text-[var(--admin-text-muted)]" />

                                                        <span>
                                                            {totalStock}
                                                        </span>
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <StatusBadge active={product.active} />
                                                </TableCell>

                                                <TableCell>
                                                    {product.featured ? (
                                                        <span className="text-sm font-medium text-[var(--admin-primary)]">
                                                            Sim
                                                        </span>
                                                    ) : (
                                                        <span className="text-sm text-[var(--admin-text-muted)]">
                                                            Não
                                                        </span>
                                                    )}
                                                </TableCell>

                                                <TableCell className="text-right">
                                                    <Link
                                                        href={`/admin/produtos/${product.id}/edit`}
                                                        className="
                                                            inline-flex
                                                            size-9
                                                            items-center
                                                            justify-center
                                                            rounded-lg
                                                            text-[var(--admin-text-muted)]
                                                            transition-colors
                                                            hover:bg-[var(--admin-background-soft)]
                                                            hover:text-[var(--admin-primary)]
                                                        "
                                                        aria-label={`Editar ${product.name}`}
                                                    >
                                                        <Pencil className="size-4" />
                                                    </Link>
                                                </TableCell>
                                            </tr>
                                        );
                                    })
                                ) : (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-4 py-12 text-center"
                                        >
                                            <p className="text-sm text-[var(--admin-text-muted)]">
                                                {hasActiveFilter
                                                    ? 'Nenhum produto encontrado para os filtros selecionados.'
                                                    : 'Nenhum produto cadastrado.'}
                                            </p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {products.last_page > 1 && (
                        <div className="flex flex-col items-center justify-between gap-3 border-t border-[var(--admin-border)] px-4 py-4 sm:flex-row md:px-5">
                            <p className="text-xs text-[var(--admin-text-muted)]">
                                Mostrando {products.from ?? 0} a{' '}
                                {products.to ?? 0} de {products.total} produtos
                            </p>

                            <nav
                                aria-label="Paginação de produtos"
                                className="flex flex-wrap items-center gap-1"
                            >
                                {products.links.map((link, index) => {
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
                                            className={`
                                                inline-flex
                                                h-8
                                                min-w-8
                                                items-center
                                                justify-center
                                                rounded-lg
                                                px-2.5
                                                text-xs
                                                font-medium
                                                transition-colors
                                                ${
                                                    link.active
                                                        ? 'bg-[var(--admin-primary)] text-white'
                                                        : 'border border-[var(--admin-border)] text-[var(--admin-text-muted)] hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-text)]'
                                                }
                                            `}
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

function TableHeader({
    children,
    className = '',
}: {
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <th
            className={`
                px-4
                py-3
                text-left
                text-xs
                font-semibold
                uppercase
                tracking-wide
                text-[var(--admin-text-muted)]
                ${className}
            `}
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
            className={`
                px-4
                py-4
                text-sm
                text-[var(--admin-text-muted)]
                ${className}
            `}
        >
            {children}
        </td>
    );
}

function StatusBadge({
    active,
}: {
    active: boolean;
}) {
    return (
        <span
            className={`
                inline-flex
                rounded-full
                px-2.5
                py-1
                text-xs
                font-medium

                ${
                    active
                        ? 'bg-green-50 text-green-700'
                        : 'bg-red-50 text-red-700'
                }
            `}
        >
            {active ? 'Ativo' : 'Inativo'}
        </span>
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
