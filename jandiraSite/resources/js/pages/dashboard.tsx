import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Boxes, Package, Plus, Star, Tags } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
    },
];

interface DashboardStats {
    products: number;
    categories: number;
    stock: number;
    featured: number;
}

interface RecentProduct {
    id: number;
    name: string;
    slug: string;
    stock: number;
    featured: boolean;

    category: {
        id: number;
        name: string;
    } | null;
}

interface DashboardProps {
    stats: DashboardStats;
    recentProducts: RecentProduct[];
}

export default function Dashboard({ stats, recentProducts }: DashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <section>
                    <h1 className="text-2xl font-semibold text-[var(--admin-text)]">Visão geral</h1>

                    <p className="mt-1 text-sm text-[var(--admin-text-muted)]">Acompanhe os principais dados e atalhos da loja.</p>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <DashboardCard title="Produtos" value={stats.products} description="Produtos cadastrados" icon={Package} />

                    <DashboardCard title="Categorias" value={stats.categories} description="Categorias disponíveis" icon={Tags} />

                    <DashboardCard title="Estoque" value={stats.stock} description="Unidades disponíveis" icon={Boxes} />

                    <DashboardCard title="Destaques" value={stats.featured} description="Produtos em destaque" icon={Star} />
                </section>

                <section className="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
                    <div className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm md:p-6">
                        <div className="mb-5 flex items-center justify-between gap-4">
                            <div>
                                <h2 className="text-base font-semibold text-[var(--admin-text)]">Produtos recentes</h2>

                                <p className="mt-1 text-sm text-[var(--admin-text-muted)]">Os últimos produtos adicionados ao catálogo.</p>
                            </div>

                            <Link
                                href="/admin/produtos"
                                className="text-sm font-medium text-[var(--admin-primary)] transition-colors hover:text-[var(--admin-primary-dark)]"
                            >
                                Ver produtos
                            </Link>
                        </div>

                        {recentProducts.length > 0 ? (
                            <div className="divide-y divide-[var(--admin-border)]">
                                {recentProducts.map((product) => (
                                    <Link
                                        key={product.id}
                                        href={`/admin/produtos/${product.id}/edit`}
                                        className="group flex items-center justify-between gap-4 py-4 transition-colors first:pt-0 last:pb-0"
                                    >
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-[var(--admin-text)] transition-colors group-hover:text-[var(--admin-primary)]">
                                                {product.name}
                                            </p>

                                            <p className="mt-1 text-xs text-[var(--admin-text-muted)]">{product.category?.name ?? 'Sem categoria'}</p>
                                        </div>

                                        <div className="shrink-0 text-right">
                                            <p className="text-sm font-medium text-[var(--admin-text)]">{product.stock}</p>

                                            <p className="text-xs text-[var(--admin-text-muted)]">em estoque</p>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        ) : (
                            <div className="flex min-h-40 items-center justify-center rounded-xl border border-dashed border-[var(--admin-border)] bg-[var(--admin-background-soft)] px-4 text-center">
                                <p className="text-sm text-[var(--admin-text-muted)]">Nenhum produto cadastrado ainda.</p>
                            </div>
                        )}
                    </div>

                    <div className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm md:p-6">
                        <div className="mb-5">
                            <h2 className="text-base font-semibold text-[var(--admin-text)]">Acesso rápido</h2>

                            <p className="mt-1 text-sm text-[var(--admin-text-muted)]">Atalhos para as tarefas mais utilizadas.</p>
                        </div>

                        <div className="grid gap-3">
                            <QuickAction
                                href="/admin/produtos"
                                icon={Package}
                                title="Gerenciar produtos"
                                description="Visualize e edite o catálogo."
                            />

                            <QuickAction
                                href="/admin/produtos/create"
                                icon={Plus}
                                title="Adicionar produto"
                                description="Cadastre um novo item na loja."
                            />

                            <QuickAction
                                href="/admin/categorias"
                                icon={Tags}
                                title="Gerenciar categorias"
                                description="Organize departamentos e subcategorias."
                            />

                            <QuickAction
                                href="/admin/estoque"
                                icon={Boxes}
                                title="Controlar estoque"
                                description="Ajuste quantidades e acompanhe alertas."
                            />
                        </div>
                    </div>
                </section>
            </main>
        </AppLayout>
    );
}

interface DashboardCardProps {
    title: string;
    value: string | number;
    description: string;
    icon: React.ElementType;
}

function DashboardCard({ title, value, description, icon: Icon }: DashboardCardProps) {
    return (
        <article className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-medium text-[var(--admin-text-muted)]">{title}</p>

                    <p className="mt-2 text-2xl font-semibold text-[var(--admin-text)]">{value}</p>
                </div>

                <div className="flex size-10 items-center justify-center rounded-xl bg-[var(--admin-background-soft)] text-[var(--admin-primary)]">
                    <Icon className="size-5" aria-hidden="true" />
                </div>
            </div>

            <p className="mt-3 text-xs text-[var(--admin-text-muted)]">{description}</p>
        </article>
    );
}

interface QuickActionProps {
    href: string;
    title: string;
    description: string;
    icon: React.ElementType;
}

function QuickAction({ href, title, description, icon: Icon }: QuickActionProps) {
    return (
        <Link
            href={href}
            className="group flex items-center gap-3 rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] p-3 transition-all hover:border-[var(--admin-primary-light)] hover:bg-[var(--admin-background-soft)]"
        >
            <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-[var(--admin-background-soft)] text-[var(--admin-primary)]">
                <Icon className="size-4" aria-hidden="true" />
            </div>

            <div className="min-w-0">
                <p className="text-sm font-medium text-[var(--admin-text)] transition-colors group-hover:text-[var(--admin-primary)]">{title}</p>

                <p className="mt-0.5 text-xs text-[var(--admin-text-muted)]">{description}</p>
            </div>
        </Link>
    );
}
