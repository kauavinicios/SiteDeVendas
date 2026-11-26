import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { BarChart3, BellRing, Boxes, Clock3, DollarSign, Mail, Package, Plus, ShieldCheck, ShoppingBag, Star, Tags, TrendingUp, Users } from 'lucide-react';

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

interface DashboardSummary {
    pending_orders: number;
    confirmed_orders_30d: number;
    pending_stock_alerts: number;
    revenue_30d: number | null;
    estimated_profit_30d: number | null;
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
    summary?: DashboardSummary;
    recentProducts: RecentProduct[];
}

export default function Dashboard({ stats, summary, recentProducts }: DashboardProps) {
    const { can } = usePermissions();
    const canManageProducts = can('manage-products');
    const canViewFinancial = can('view-financial-reports');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <section className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-[var(--admin-text)]">Visão geral</h1>

                        <p className="mt-1 text-sm text-[var(--admin-text-muted)]">Acompanhe os principais dados e atalhos da loja.</p>
                    </div>

                    {canViewFinancial && (
                        <Link
                            href="/admin/relatorios"
                            className="inline-flex h-10 items-center gap-2 rounded-xl border border-[var(--admin-border)] bg-[var(--admin-surface)] px-4 text-sm font-medium text-[var(--admin-primary)] transition-colors hover:border-[var(--admin-primary)] hover:bg-[var(--admin-background-soft)]"
                        >
                            <BarChart3 className="size-4" aria-hidden="true" />
                            Ver relatórios e lucros
                        </Link>
                    )}
                </section>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <DashboardCard title="Produtos" value={stats.products} description="Produtos cadastrados" icon={Package} />

                    <DashboardCard title="Categorias" value={stats.categories} description="Categorias disponíveis" icon={Tags} />

                    <DashboardCard title="Estoque" value={stats.stock} description="Unidades disponíveis" icon={Boxes} />

                    <DashboardCard title="Destaques" value={stats.featured} description="Produtos em destaque" icon={Star} />
                </section>

                {summary && (
                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <DashboardCard
                            title="Pedidos pendentes"
                            value={summary.pending_orders}
                            description="Aguardando confirmação no WhatsApp"
                            icon={Clock3}
                        />

                        <DashboardCard
                            title="Alertas de estoque"
                            value={summary.pending_stock_alerts}
                            description="Clientes no Avise-me"
                            icon={BellRing}
                        />

                        {canViewFinancial && summary.revenue_30d !== null && (
                            <DashboardCard
                                title="Faturamento (30d)"
                                value={formatCurrency(summary.revenue_30d)}
                                description={`${summary.confirmed_orders_30d} pedido(s) confirmado(s)`}
                                icon={DollarSign}
                            />
                        )}

                        {canViewFinancial && summary.estimated_profit_30d !== null && (
                            <DashboardCard
                                title="Lucro estimado (30d)"
                                value={formatCurrency(summary.estimated_profit_30d)}
                                description="Baseado no custo cadastrado"
                                icon={TrendingUp}
                            />
                        )}
                    </section>
                )}

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
                                {recentProducts.map((product) => {
                                    const rowBody = (
                                        <>
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium text-[var(--admin-text)] transition-colors group-hover:text-[var(--admin-primary)]">
                                                    {product.name}
                                                </p>

                                                <p className="mt-1 text-xs text-[var(--admin-text-muted)]">
                                                    {product.category?.name ?? 'Sem categoria'}
                                                </p>
                                            </div>

                                            <div className="shrink-0 text-right">
                                                <p className="text-sm font-medium text-[var(--admin-text)]">{product.stock}</p>

                                                <p className="text-xs text-[var(--admin-text-muted)]">em estoque</p>
                                            </div>
                                        </>
                                    );

                                    if (canManageProducts) {
                                        return (
                                            <Link
                                                key={product.id}
                                                href={`/admin/produtos/${product.id}/edit`}
                                                className="group flex items-center justify-between gap-4 py-4 transition-colors first:pt-0 last:pb-0"
                                            >
                                                {rowBody}
                                            </Link>
                                        );
                                    }

                                    return (
                                        <div key={product.id} className="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                                            {rowBody}
                                        </div>
                                    );
                                })}
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
                            {can('view-orders') && (
                                <QuickAction
                                    href="/admin/pedidos"
                                    icon={ShoppingBag}
                                    title={can('manage-orders') ? 'Gerenciar pedidos' : 'Consultar pedidos'}
                                    description="Acompanhe os pedidos e atualize status."
                                />
                            )}

                            {canViewFinancial && (
                                <QuickAction
                                    href="/admin/relatorios"
                                    icon={BarChart3}
                                    title="Relatórios e lucros"
                                    description="Veja gráficos de vendas, margem e demanda."
                                />
                            )}

                            {can('view-products') && (
                                <QuickAction
                                    href="/admin/produtos"
                                    icon={Package}
                                    title={canManageProducts ? 'Gerenciar produtos' : 'Consultar produtos'}
                                    description={canManageProducts ? 'Visualize e edite o catálogo.' : 'Consulte os itens e preços da loja.'}
                                />
                            )}

                            {canManageProducts && (
                                <QuickAction
                                    href="/admin/produtos/create"
                                    icon={Plus}
                                    title="Adicionar produto"
                                    description="Cadastre um novo item na loja."
                                />
                            )}

                            {can('view-categories') && (
                                <QuickAction
                                    href="/admin/categorias"
                                    icon={Tags}
                                    title={can('manage-categories') ? 'Gerenciar categorias' : 'Consultar categorias'}
                                    description={
                                        can('manage-categories') ? 'Organize departamentos e subcategorias.' : 'Veja os departamentos da loja.'
                                    }
                                />
                            )}

                            {can('view-stock') && (
                                <QuickAction
                                    href="/admin/estoque"
                                    icon={Boxes}
                                    title={can('manage-stock') ? 'Controlar estoque' : 'Consultar estoque'}
                                    description={
                                        can('manage-stock') ? 'Ajuste quantidades e acompanhe alertas.' : 'Acompanhe a disponibilidade dos itens.'
                                    }
                                />
                            )}

                            {can('view-customers') && (
                                <QuickAction
                                    href="/admin/clientes"
                                    icon={Users}
                                    title="Clientes"
                                    description="Consulte clientes e inscritos em novidades."
                                />
                            )}

                            {can('manage-campaigns') && (
                                <QuickAction
                                    href="/admin/novidades"
                                    icon={Mail}
                                    title="Novidades por e-mail"
                                    description="Crie campanhas LGPD com o visual da loja."
                                />
                            )}

                            {can('manage-users') && (
                                <QuickAction
                                    href="/admin/usuarios"
                                    icon={ShieldCheck}
                                    title="Usuários da equipe"
                                    description="Gerencie cargos e acessos internos."
                                />
                            )}
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

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
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
