import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { AlertCircle, BellRing, Clock3, CreditCard, DollarSign, Heart, Layers, ShoppingBag, TrendingUp, Wallet } from 'lucide-react';
import { useState } from 'react';

type PeriodKey = '7d' | '30d' | '90d' | '365d';

type Kpis = {
    gross_revenue: number;
    revenue_with_cost: number;
    estimated_cost: number;
    estimated_profit: number;
    margin_percent: number | null;
    items_without_cost: number;
    total_orders: number;
    confirmed_orders: number;
    pending_orders: number;
    cancelled_orders: number;
    average_ticket: number;
    conversion_rate: number;
    pending_stock_alerts: number;
    total_favorites: number;
};

type RevenuePoint = {
    label: string;
    revenue: number;
    profit: number;
    orders: number;
};

type StatusItem = {
    status: string;
    label: string;
    tone: string;
    count: number;
    percentage: number;
};

type TopProduct = {
    name: string;
    units: number;
    revenue: number;
    profit: number | null;
};

type TopCategory = {
    name: string;
    units: number;
    revenue: number;
};

type FavoriteDemand = {
    id: number;
    name: string;
    favorites_count: number;
    stock: number;
};

type RestockDemand = {
    variant_id: number;
    product_name: string;
    variant_name: string;
    stock: number;
    pending_alerts: number;
};

type PaymentFunnel = {
    created_links: number;
    paid_links: number;
    pending_links: number;
    failed_or_cancelled: number;
    checkout_conversion_rate: number;
};

type PaymentAmounts = {
    expected_paid_total: number;
    actual_paid_total: number;
    difference_total: number;
};

type CaptureMethodStat = {
    method: string;
    label: string;
    count: number;
    revenue: number;
    percentage: number;
    average_ticket: number;
};

type InstallmentStat = {
    installments: number;
    label: string;
    count: number;
    revenue: number;
    percentage: number;
};

type PaymentMetrics = {
    funnel: PaymentFunnel;
    avg_time_to_pay_minutes: number | null;
    amounts: PaymentAmounts;
    average_card_installments: number | null;
    capture_methods: CaptureMethodStat[];
    installments_breakdown: InstallmentStat[];
};

type Props = {
    period: PeriodKey;
    periodLabel: string;
    kpis: Kpis;
    revenueSeries: RevenuePoint[];
    statusBreakdown: StatusItem[];
    topProducts: TopProduct[];
    topCategories: TopCategory[];
    mostFavorited: FavoriteDemand[];
    mostRequestedRestock: RestockDemand[];
    paymentMetrics: PaymentMetrics;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Relatórios',
        href: '/admin/relatorios',
    },
];

const periodOptions: { value: PeriodKey; label: string }[] = [
    { value: '7d', label: '7 dias' },
    { value: '30d', label: '30 dias' },
    { value: '90d', label: '3 meses' },
    { value: '365d', label: '12 meses' },
];

const statusBarColors: Record<string, string> = {
    warning: '#d97706',
    info: '#2563eb',
    primary: '#8b3f59',
    success: '#4f7a59',
    danger: '#a34c4c',
};

const captureMethodColors: Record<string, string> = {
    pix: '#4f7a59',
    credit_card: '#8b3f59',
    debit_card: '#2563eb',
    other: '#74686c',
};

export default function AnalyticsIndex({
    period,
    periodLabel,
    kpis,
    revenueSeries,
    statusBreakdown,
    topProducts,
    topCategories,
    mostFavorited,
    mostRequestedRestock,
    paymentMetrics,
}: Props) {
    const changePeriod = (nextPeriod: PeriodKey) => {
        router.get(
            route('admin.analytics.index'),
            { periodo: nextPeriod },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const maxProductRevenue = Math.max(...topProducts.map((p) => p.revenue), 1);
    const maxCategoryRevenue = Math.max(...topCategories.map((c) => c.revenue), 1);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Relatórios e lucros" />

            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <section className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-[var(--admin-text)]">Relatórios e lucros</h1>

                        <p className="mt-1 text-sm text-[var(--admin-text-muted)]">
                            Desempenho de vendas, estimativa de margem e indicadores de demanda ({periodLabel}).
                        </p>
                    </div>

                    <div className="inline-flex rounded-xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-1">
                        {periodOptions.map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                onClick={() => changePeriod(option.value)}
                                className={`rounded-lg px-3 py-1.5 text-xs font-medium transition-colors ${
                                    period === option.value
                                        ? 'bg-[var(--admin-primary)] text-white'
                                        : 'text-[var(--admin-text-muted)] hover:text-[var(--admin-text)]'
                                }`}
                            >
                                {option.label}
                            </button>
                        ))}
                    </div>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <KpiCard
                        title="Faturamento bruto"
                        value={formatCurrency(kpis.gross_revenue)}
                        description={`${kpis.confirmed_orders} pedido(s) confirmado(s)`}
                        icon={DollarSign}
                    />

                    <KpiCard
                        title="Lucro bruto estimado"
                        value={formatCurrency(kpis.estimated_profit)}
                        description={
                            kpis.margin_percent !== null
                                ? `Margem de ${kpis.margin_percent}% sobre itens com custo`
                                : 'Cadastre o custo nas variações para estimar'
                        }
                        icon={TrendingUp}
                        highlight
                    />

                    <KpiCard
                        title="Ticket médio"
                        value={formatCurrency(kpis.average_ticket)}
                        description={`Taxa de confirmação: ${kpis.conversion_rate}%`}
                        icon={ShoppingBag}
                    />

                    <KpiCard
                        title="Demanda reprimida"
                        value={`${kpis.pending_stock_alerts} alertas`}
                        description={`${kpis.total_favorites} produto(s) favoritado(s)`}
                        icon={BellRing}
                    />
                </section>

                {kpis.items_without_cost > 0 && (
                    <div className="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50/70 px-4 py-3 text-xs text-amber-900">
                        <AlertCircle className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        <p>
                            Neste período, <strong>{kpis.items_without_cost} unidade(s)</strong> vendida(s) não tinham preço de custo cadastrado na
                            variação. O lucro estimado considera apenas os {formatCurrency(kpis.revenue_with_cost)} em vendas com custo informado (custo
                            total: {formatCurrency(kpis.estimated_cost)}).
                        </p>
                    </div>
                )}

                <section className="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
                    <RevenueProfitChart series={revenueSeries} />

                    <StatusBreakdownCard statusBreakdown={statusBreakdown} totalOrders={kpis.total_orders} />
                </section>

                <InfinitePayMetricsSection paymentMetrics={paymentMetrics} periodLabel={periodLabel} />

                <section className="grid gap-6 lg:grid-cols-2">
                    <article className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm md:p-6">
                        <h2 className="text-base font-semibold text-[var(--admin-text)]">Produtos mais vendidos</h2>
                        <p className="mt-1 text-xs text-[var(--admin-text-muted)]">Ranking por faturamento confirmado no período.</p>

                        {topProducts.length > 0 ? (
                            <div className="mt-5 space-y-4">
                                {topProducts.map((product) => (
                                    <div key={product.name} className="space-y-1.5">
                                        <div className="flex items-center justify-between gap-4 text-sm">
                                            <span className="truncate font-medium text-[var(--admin-text)]">{product.name}</span>
                                            <span className="shrink-0 font-semibold text-[var(--admin-text)]">
                                                {formatCurrency(product.revenue)}
                                            </span>
                                        </div>

                                        <div className="h-2 w-full overflow-hidden rounded-full bg-[var(--admin-background-soft)]">
                                            <div
                                                className="h-full rounded-full bg-[var(--admin-primary)]"
                                                style={{ width: `${Math.max((product.revenue / maxProductRevenue) * 100, 4)}%` }}
                                            />
                                        </div>

                                        <div className="flex justify-between text-[11px] text-[var(--admin-text-muted)]">
                                            <span>{product.units} unidade(s)</span>
                                            {product.profit !== null && (
                                                <span className="text-[var(--admin-success)]">Lucro est.: {formatCurrency(product.profit)}</span>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <EmptyChartState message="Nenhuma venda confirmada neste período." />
                        )}
                    </article>

                    <article className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm md:p-6">
                        <h2 className="text-base font-semibold text-[var(--admin-text)]">Vendas por categoria</h2>
                        <p className="mt-1 text-xs text-[var(--admin-text-muted)]">Participação das categorias no faturamento.</p>

                        {topCategories.length > 0 ? (
                            <div className="mt-5 space-y-4">
                                {topCategories.map((category) => (
                                    <div key={category.name} className="space-y-1.5">
                                        <div className="flex items-center justify-between gap-4 text-sm">
                                            <span className="truncate font-medium text-[var(--admin-text)]">{category.name}</span>
                                            <span className="shrink-0 font-semibold text-[var(--admin-text)]">
                                                {formatCurrency(category.revenue)}
                                            </span>
                                        </div>

                                        <div className="h-2 w-full overflow-hidden rounded-full bg-[var(--admin-background-soft)]">
                                            <div
                                                className="h-full rounded-full bg-[#c98ca0]"
                                                style={{ width: `${Math.max((category.revenue / maxCategoryRevenue) * 100, 4)}%` }}
                                            />
                                        </div>

                                        <p className="text-[11px] text-[var(--admin-text-muted)]">{category.units} unidade(s) vendida(s)</p>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <EmptyChartState message="Nenhuma categoria pontuada neste período." />
                        )}
                    </article>
                </section>

                <section className="grid gap-6 lg:grid-cols-2">
                    <article className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm md:p-6">
                        <div className="flex items-center justify-between gap-2">
                            <div>
                                <h2 className="flex items-center gap-2 text-base font-semibold text-[var(--admin-text)]">
                                    <Heart className="size-4 text-[var(--admin-primary)]" />
                                    Produtos mais favoritados
                                </h2>
                                <p className="mt-1 text-xs text-[var(--admin-text-muted)]">Itens salvos nos preferidos pelas clientes.</p>
                            </div>
                        </div>

                        {mostFavorited.length > 0 ? (
                            <div className="mt-4 divide-y divide-[var(--admin-border)]">
                                {mostFavorited.map((item) => (
                                    <div key={item.id} className="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                                        <div className="min-w-0">
                                            <Link
                                                href={`/admin/produtos/${item.id}/edit`}
                                                className="truncate text-sm font-medium text-[var(--admin-text)] hover:text-[var(--admin-primary)]"
                                            >
                                                {item.name}
                                            </Link>
                                            <p className="text-xs text-[var(--admin-text-muted)]">Estoque total: {item.stock} un.</p>
                                        </div>

                                        <span className="inline-flex shrink-0 rounded-full bg-[var(--admin-background-soft)] px-2.5 py-1 text-xs font-semibold text-[var(--admin-primary)]">
                                            {item.favorites_count} favorito(s)
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <EmptyChartState message="Nenhum produto favoritado ainda." />
                        )}
                    </article>

                    <article className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm md:p-6">
                        <div className="flex items-center justify-between gap-2">
                            <div>
                                <h2 className="flex items-center gap-2 text-base font-semibold text-[var(--admin-text)]">
                                    <BellRing className="size-4 text-amber-600" />
                                    Mais pedidos no &ldquo;Avise-me&rdquo;
                                </h2>
                                <p className="mt-1 text-xs text-[var(--admin-text-muted)]">
                                    Variações esgotadas com maior procura para reposição.
                                </p>
                            </div>

                            <Link href="/admin/estoque" className="text-xs font-medium text-[var(--admin-primary)] hover:underline">
                                Repor estoque
                            </Link>
                        </div>

                        {mostRequestedRestock.length > 0 ? (
                            <div className="mt-4 divide-y divide-[var(--admin-border)]">
                                {mostRequestedRestock.map((item) => (
                                    <div key={item.variant_id} className="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-[var(--admin-text)]">
                                                {item.product_name} ({item.variant_name})
                                            </p>
                                            <p className="text-xs text-[var(--admin-text-muted)]">Estoque atual: {item.stock} un.</p>
                                        </div>

                                        <span className="inline-flex shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                            {item.pending_alerts} aguardando
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <EmptyChartState message="Nenhum alerta pendente de reposição no momento." />
                        )}
                    </article>
                </section>
            </main>
        </AppLayout>
    );
}

function RevenueProfitChart({ series }: { series: RevenuePoint[] }) {
    const [hoveredIndex, setHoveredIndex] = useState<number | null>(null);

    const maxVal = Math.max(...series.map((p) => Math.max(p.revenue, p.profit)), 1);
    const hasAnyData = series.some((p) => p.revenue > 0 || p.profit > 0);
    const chartHeight = 200;
    const chartWidth = Math.max(series.length * 42, 520);
    const labelStep = series.length > 16 ? Math.ceil(series.length / 10) : 1;

    const activePoint = hoveredIndex !== null ? series[hoveredIndex] : null;

    return (
        <article className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm md:p-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 className="text-base font-semibold text-[var(--admin-text)]">Faturamento vs Lucro estimado</h2>
                    <p className="mt-1 text-xs text-[var(--admin-text-muted)]">Passe o cursor sobre as barras para detalhar cada período.</p>
                </div>

                <div className="flex items-center gap-4 text-xs">
                    <span className="inline-flex items-center gap-1.5 text-[var(--admin-text-muted)]">
                        <span className="size-2.5 rounded-sm bg-[#8b3f59]" />
                        Faturamento
                    </span>
                    <span className="inline-flex items-center gap-1.5 text-[var(--admin-text-muted)]">
                        <span className="size-2.5 rounded-sm bg-[#4f7a59]" />
                        Lucro estimado
                    </span>
                </div>
            </div>

            <div className="mt-3 min-h-6 text-xs text-[var(--admin-text)]">
                {activePoint ? (
                    <p className="inline-flex flex-wrap items-center gap-3 rounded-lg border border-[var(--admin-border)] bg-[var(--admin-background-soft)] px-3 py-1">
                        <strong>{activePoint.label}:</strong>
                        <span>Faturamento: {formatCurrency(activePoint.revenue)}</span>
                        <span className="text-[var(--admin-success)]">Lucro est.: {formatCurrency(activePoint.profit)}</span>
                        <span className="text-[var(--admin-text-muted)]">({activePoint.orders} pedido(s))</span>
                    </p>
                ) : (
                    <span className="text-[var(--admin-text-muted)]">Passe o mouse sobre uma coluna para ver os valores exatos.</span>
                )}
            </div>

            {hasAnyData ? (
                <div className="mt-3 overflow-x-auto">
                    <svg viewBox={`0 0 ${chartWidth} ${chartHeight + 30}`} className="h-60 w-full min-w-[460px]">
                        {[0, 0.25, 0.5, 0.75, 1].map((ratio) => {
                            const y = chartHeight - ratio * (chartHeight - 20);

                            return (
                                <line
                                    key={ratio}
                                    x1={0}
                                    y1={y}
                                    x2={chartWidth}
                                    y2={y}
                                    stroke="#eadeda"
                                    strokeDasharray={ratio === 0 ? undefined : '4 4'}
                                />
                            );
                        })}

                        {series.map((point, index) => {
                            const slotWidth = chartWidth / series.length;
                            const barWidth = Math.max(Math.min(slotWidth * 0.34, 18), 6);
                            const centerX = index * slotWidth + slotWidth / 2;

                            const revHeight = point.revenue > 0 ? Math.max((point.revenue / maxVal) * (chartHeight - 28), 4) : 0;
                            const profitHeight = point.profit > 0 ? Math.max((point.profit / maxVal) * (chartHeight - 28), 4) : 0;

                            return (
                                <g
                                    key={point.label}
                                    onMouseEnter={() => setHoveredIndex(index)}
                                    onMouseLeave={() => setHoveredIndex(null)}
                                    className="cursor-pointer"
                                >
                                    <rect
                                        x={index * slotWidth}
                                        y={0}
                                        width={slotWidth}
                                        height={chartHeight}
                                        fill={hoveredIndex === index ? 'rgba(139, 63, 89, 0.05)' : 'transparent'}
                                    />

                                    <rect
                                        x={centerX - barWidth - 1}
                                        y={chartHeight - revHeight}
                                        width={barWidth}
                                        height={revHeight}
                                        rx={3}
                                        fill="#8b3f59"
                                    />

                                    <rect
                                        x={centerX + 1}
                                        y={chartHeight - profitHeight}
                                        width={barWidth}
                                        height={profitHeight}
                                        rx={3}
                                        fill="#4f7a59"
                                    />

                                    {index % labelStep === 0 && (
                                        <text x={centerX} y={chartHeight + 18} textAnchor="middle" fill="#74686c" fontSize="10">
                                            {point.label}
                                        </text>
                                    )}
                                </g>
                            );
                        })}
                    </svg>
                </div>
            ) : (
                <EmptyChartState message="Ainda não há pedidos confirmados com faturamento neste período." />
            )}
        </article>
    );
}

function StatusBreakdownCard({ statusBreakdown, totalOrders }: { statusBreakdown: StatusItem[]; totalOrders: number }) {
    return (
        <article className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm md:p-6">
            <h2 className="text-base font-semibold text-[var(--admin-text)]">Pedidos por status</h2>
            <p className="mt-1 text-xs text-[var(--admin-text-muted)]">Distribuição entre os {totalOrders} pedido(s) do período.</p>

            {totalOrders > 0 ? (
                <>
                    <div className="mt-5 flex h-3.5 w-full overflow-hidden rounded-full bg-[var(--admin-background-soft)]">
                        {statusBreakdown
                            .filter((item) => item.count > 0)
                            .map((item) => (
                                <div
                                    key={item.status}
                                    style={{
                                        width: `${item.percentage}%`,
                                        backgroundColor: statusBarColors[item.tone] ?? '#74686c',
                                    }}
                                    title={`${item.label}: ${item.count}`}
                                />
                            ))}
                    </div>

                    <div className="mt-5 space-y-3">
                        {statusBreakdown.map((item) => (
                            <div key={item.status} className="flex items-center justify-between text-sm">
                                <span className="inline-flex items-center gap-2 text-[var(--admin-text)]">
                                    <span
                                        className="size-2.5 rounded-full"
                                        style={{ backgroundColor: statusBarColors[item.tone] ?? '#74686c' }}
                                    />
                                    {item.label}
                                </span>

                                <span className="text-xs font-medium text-[var(--admin-text-muted)]">
                                    <strong className="text-[var(--admin-text)]">{item.count}</strong> ({item.percentage}%)
                                </span>
                            </div>
                        ))}
                    </div>
                </>
            ) : (
                <EmptyChartState message="Nenhum pedido registrado no período." />
            )}
        </article>
    );
}

function KpiCard({
    title,
    value,
    description,
    icon: Icon,
    highlight = false,
}: {
    title: string;
    value: string;
    description: string;
    icon: React.ElementType;
    highlight?: boolean;
}) {
    return (
        <article className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-medium text-[var(--admin-text-muted)]">{title}</p>
                    <p className={`mt-2 text-2xl font-semibold ${highlight ? 'text-[var(--admin-success)]' : 'text-[var(--admin-text)]'}`}>
                        {value}
                    </p>
                </div>

                <div className="flex size-10 items-center justify-center rounded-xl bg-[var(--admin-background-soft)] text-[var(--admin-primary)]">
                    <Icon className="size-5" aria-hidden="true" />
                </div>
            </div>

            <p className="mt-3 text-xs text-[var(--admin-text-muted)]">{description}</p>
        </article>
    );
}

function EmptyChartState({ message }: { message: string }) {
    return (
        <div className="mt-4 flex min-h-36 items-center justify-center rounded-xl border border-dashed border-[var(--admin-border)] bg-[var(--admin-background-soft)] px-4 text-center">
            <p className="text-xs text-[var(--admin-text-muted)]">{message}</p>
        </div>
    );
}

function InfinitePayMetricsSection({ paymentMetrics, periodLabel }: { paymentMetrics: PaymentMetrics; periodLabel: string }) {
    const { funnel, amounts, capture_methods: captureMethods, installments_breakdown: installmentsBreakdown } = paymentMetrics;
    const maxInstallmentRevenue = Math.max(...installmentsBreakdown.map((item) => item.revenue), 1);

    const amountDifferenceNote =
        amounts.difference_total !== 0
            ? `Cobrado: ${formatCurrency(amounts.expected_paid_total)} (${amounts.difference_total > 0 ? '+' : ''}${formatCurrency(amounts.difference_total)})`
            : `${funnel.paid_links} transação(ões) liquidada(s)`;

    return (
        <section className="space-y-4">
            <div>
                <h2 className="text-lg font-semibold text-[var(--admin-text)]">Desempenho de Pagamentos (InfinitePay)</h2>
                <p className="mt-0.5 text-xs text-[var(--admin-text-muted)]">
                    Métricas capturadas dos links e confirmações InfinitePay no período ({periodLabel}).
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <KpiCard
                    title="Conversão do checkout"
                    value={`${funnel.checkout_conversion_rate}%`}
                    description={`${funnel.paid_links} pago(s) de ${funnel.created_links} link(s) gerado(s)`}
                    icon={CreditCard}
                />

                <KpiCard
                    title="Tempo médio até pagar"
                    value={formatDuration(paymentMetrics.avg_time_to_pay_minutes)}
                    description="Entre geração do link e confirmação"
                    icon={Clock3}
                />

                <KpiCard
                    title="Volume confirmado InfinitePay"
                    value={formatCurrency(amounts.actual_paid_total)}
                    description={amountDifferenceNote}
                    icon={Wallet}
                    highlight
                />

                <KpiCard
                    title="Parcelamento médio (Cartão)"
                    value={paymentMetrics.average_card_installments !== null ? `${paymentMetrics.average_card_installments}x` : '—'}
                    description="Média de parcelas nas vendas no cartão"
                    icon={Layers}
                />
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <article className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm md:p-6">
                    <h3 className="text-base font-semibold text-[var(--admin-text)]">Pix vs Cartão de Crédito</h3>
                    <p className="mt-1 text-xs text-[var(--admin-text-muted)]">
                        Distribuição por meio de captura entre transações pagas.
                    </p>

                    {captureMethods.length > 0 ? (
                        <>
                            <div className="mt-5 flex h-3.5 w-full overflow-hidden rounded-full bg-[var(--admin-background-soft)]">
                                {captureMethods.map((item) => (
                                    <div
                                        key={item.method}
                                        style={{
                                            width: `${item.percentage}%`,
                                            backgroundColor: captureMethodColors[item.method] ?? '#74686c',
                                        }}
                                        title={`${item.label}: ${item.count} (${item.percentage}%)`}
                                    />
                                ))}
                            </div>

                            <div className="mt-5 space-y-3.5">
                                {captureMethods.map((item) => (
                                    <div key={item.method} className="space-y-1">
                                        <div className="flex items-center justify-between gap-4 text-sm">
                                            <span className="inline-flex items-center gap-2 font-medium text-[var(--admin-text)]">
                                                <span
                                                    className="size-2.5 rounded-full"
                                                    style={{ backgroundColor: captureMethodColors[item.method] ?? '#74686c' }}
                                                />
                                                {item.label}
                                            </span>

                                            <span className="font-semibold text-[var(--admin-text)]">{formatCurrency(item.revenue)}</span>
                                        </div>

                                        <div className="flex items-center justify-between text-[11px] text-[var(--admin-text-muted)]">
                                            <span>
                                                {item.count} pagamento(s) &bull; {item.percentage}% dos pagos
                                            </span>
                                            <span>Ticket médio: {formatCurrency(item.average_ticket)}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </>
                    ) : (
                        <EmptyChartState message="Nenhum pagamento confirmado pela InfinitePay neste período." />
                    )}
                </article>

                <article className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm md:p-6">
                    <h3 className="text-base font-semibold text-[var(--admin-text)]">Parcelamento no Cartão &amp; Funil</h3>
                    <p className="mt-1 text-xs text-[var(--admin-text-muted)]">
                        Preferência de parcelas e status dos links gerados no período.
                    </p>

                    <div className="mt-4 grid grid-cols-2 gap-2.5 rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background-soft)] p-3 text-xs sm:grid-cols-4">
                        <div>
                            <p className="text-[var(--admin-text-muted)]">Links gerados</p>
                            <p className="mt-0.5 text-sm font-semibold text-[var(--admin-text)]">{funnel.created_links}</p>
                        </div>
                        <div>
                            <p className="text-[var(--admin-text-muted)]">Pagos</p>
                            <p className="mt-0.5 text-sm font-semibold text-[var(--admin-success)]">{funnel.paid_links}</p>
                        </div>
                        <div>
                            <p className="text-[var(--admin-text-muted)]">Pendentes</p>
                            <p className="mt-0.5 text-sm font-semibold text-amber-700">{funnel.pending_links}</p>
                        </div>
                        <div>
                            <p className="text-[var(--admin-text-muted)]">Falha/Canc.</p>
                            <p className="mt-0.5 text-sm font-semibold text-[var(--admin-text)]">{funnel.failed_or_cancelled}</p>
                        </div>
                    </div>

                    {installmentsBreakdown.length > 0 ? (
                        <div className="mt-5 space-y-3.5">
                            {installmentsBreakdown.map((item) => (
                                <div key={item.installments} className="space-y-1.5">
                                    <div className="flex items-center justify-between gap-4 text-sm">
                                        <span className="font-medium text-[var(--admin-text)]">{item.label}</span>
                                        <span className="font-semibold text-[var(--admin-text)]">{formatCurrency(item.revenue)}</span>
                                    </div>

                                    <div className="h-2 w-full overflow-hidden rounded-full bg-[var(--admin-background-soft)]">
                                        <div
                                            className="h-full rounded-full bg-[#8b3f59]"
                                            style={{ width: `${Math.max((item.revenue / maxInstallmentRevenue) * 100, 4)}%` }}
                                        />
                                    </div>

                                    <div className="flex justify-between text-[11px] text-[var(--admin-text-muted)]">
                                        <span>{item.count} compra(s) no cartão</span>
                                        <span>{item.percentage}% do volume em cartão</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <EmptyChartState message="Nenhuma compra no cartão de crédito confirmada neste período." />
                    )}
                </article>
            </div>
        </section>
    );
}

function formatDuration(minutes: number | null): string {
    if (minutes === null) {
        return '—';
    }

    if (minutes < 1) {
        return '< 1 min';
    }

    if (minutes < 60) {
        return `${Math.round(minutes)} min`;
    }

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = Math.round(minutes % 60);

    return remainingMinutes > 0 ? `${hours}h ${remainingMinutes}min` : `${hours}h`;
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}
