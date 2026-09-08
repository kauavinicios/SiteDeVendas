import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

import { Head, Link, usePage } from '@inertiajs/react';
import {
    Boxes,
    Pencil,
    Plus,
    Search,
} from 'lucide-react';

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

type PaginatedProducts = {
    data: Product[];
};

type Props = {
    products: PaginatedProducts;
};

type Flash = {
    success?: string;
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

export default function ProductsIndex({
    products,
}: Props) {
    const { flash } = usePage<PageProps>().props;
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
                
                <section
                    className="
                        rounded-2xl
                        border
                        border-[var(--admin-border)]
                        bg-[var(--admin-surface)]
                        shadow-sm
                    "
                >
                    <div className="border-b border-[var(--admin-border)] p-4 md:p-5">
                        <div className="relative max-w-md">
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
                                placeholder="Buscar produto..."
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
                                                Nenhum produto cadastrado.
                                            </p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
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