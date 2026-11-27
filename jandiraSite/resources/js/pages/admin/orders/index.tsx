import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Clock3, CreditCard, DollarSign, ExternalLink, Eye, RefreshCw, Search, ShoppingBag } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

type OrderItemEntry = {
    id: number;
    product_name: string;
    variant_name: string;
    sku: string | null;
    unit_price: number;
    quantity: number;
    subtotal: number;
};

type PaymentTransactionSummary = {
    id: number;
    provider: string;
    driver: string;
    auth_mode: string;
    status: string;
    status_label: string;
    status_tone: string;
    slug: string | null;
    transaction_nsu: string | null;
    capture_method: string | null;
    capture_method_label: string | null;
    installments: number | null;
    amount: number;
    paid_amount: number | null;
    checkout_url: string | null;
    receipt_url: string | null;
    paid_at: string | null;
    last_checked_at: string | null;
};

type PaymentEventEntry = {
    id: number;
    source: string;
    source_label: string;
    event_type: string;
    event_label: string;
    external_event_id: string | null;
    notes: string | null;
    created_at: string | null;
};

type OrderEntry = {
    id: number;
    code: string;
    customer_name: string;
    customer_email: string | null;
    customer_phone: string;
    status: string;
    status_label: string;
    status_tone: string;
    channel: string;
    payment_method: string | null;
    payment_method_label: string | null;
    payment_status: string;
    total: number;
    total_cost: number | null;
    estimated_profit: number | null;
    items_count: number;
    notes: string | null;
    stock_deducted: boolean;
    created_at: string | null;
    payment_transaction: PaymentTransactionSummary | null;
    payment_events: PaymentEventEntry[];
    items: OrderItemEntry[];
};

type StatusOption = {
    value: string;
    label: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedOrders = {
    data: OrderEntry[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

type OrderStats = {
    total: number;
    pending: number;
    delivered: number;
    confirmed_revenue: number;
};

type Props = {
    orders: PaginatedOrders;
    stats: OrderStats;
    statusOptions: StatusOption[];
    filters: {
        busca: string;
        status: string;
    };
    canViewFinancial: boolean;
};

type PageProps = SharedData & {
    flash?: {
        success?: string;
        error?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pedidos',
        href: '/admin/pedidos',
    },
];

const toneClasses: Record<string, string> = {
    warning: 'bg-amber-50 text-amber-800',
    info: 'bg-blue-50 text-blue-700',
    primary: 'bg-[var(--admin-background-soft)] text-[var(--admin-primary)]',
    success: 'bg-green-50 text-green-700',
    danger: 'bg-red-50 text-red-700',
};

const paymentStatusLabels: Record<string, string> = {
    pending: 'Pendente',
    paid: 'Pago',
    refunded: 'Estornado',
};

export default function OrdersIndex({ orders, stats, statusOptions, filters, canViewFinancial }: Props) {
    const { flash } = usePage<PageProps>().props;
    const { can } = usePermissions();
    const canManageOrders = can('manage-orders');

    const [search, setSearch] = useState(filters.busca);
    const [selectedOrderId, setSelectedOrderId] = useState<number | null>(null);
    const [verifyingOrderId, setVerifyingOrderId] = useState<number | null>(null);

    const selectedOrder = selectedOrderId !== null ? (orders.data.find((order) => order.id === selectedOrderId) ?? null) : null;

    const { data, setData, patch, processing, errors, clearErrors } = useForm({
        status: 'pending',
        payment_status: 'pending',
        payment_method: '',
        notes: '',
    });

    useEffect(() => {
        setSearch(filters.busca);
    }, [filters.busca]);

    const applyFilters = (nextSearch: string, nextStatus: string) => {
        const params: Record<string, string> = {};
        const trimmed = nextSearch.trim();

        if (trimmed !== '') {
            params.busca = trimmed;
        }

        if (nextStatus !== 'todos') {
            params.status = nextStatus;
        }

        router.get(route('admin.orders.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleSearchSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, filters.status);
    };

    const openOrderModal = (order: OrderEntry) => {
        clearErrors();
        setData({
            status: order.status,
            payment_status: order.payment_status ?? 'pending',
            payment_method: order.payment_method ?? '',
            notes: order.notes ?? '',
        });
        setSelectedOrderId(order.id);
    };

    const submitOrderUpdate: FormEventHandler = (e) => {
        e.preventDefault();

        if (!selectedOrder || !canManageOrders) {
            return;
        }

        patch(route('admin.orders.update-status', selectedOrder.id), {
            preserveScroll: true,
            onSuccess: () => {
                setSelectedOrderId(null);
            },
        });
    };

    const handleVerifyPayment = (order: OrderEntry) => {
        if (!canManageOrders || verifyingOrderId === order.id) {
            return;
        }

        router.post(
            route('admin.orders.verify-payment', order.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setVerifyingOrderId(order.id),
                onSuccess: (page) => {
                    const nextOrders = (page.props as unknown as Props).orders?.data ?? [];
                    const updated = nextOrders.find((entry) => entry.id === order.id);

                    if (updated) {
                        setData({
                            status: updated.status,
                            payment_status: updated.payment_status ?? 'pending',
                            payment_method: updated.payment_method ?? '',
                            notes: updated.notes ?? '',
                        });
                    }
                },
                onFinish: () => setVerifyingOrderId(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pedidos" />

            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <section>
                    <h1 className="text-2xl font-semibold text-[var(--admin-text)]">Pedidos</h1>

                    <p className="mt-1 text-sm text-[var(--admin-text-muted)]">
                        Acompanhe os pedidos da InfinitePay e do WhatsApp. A confirmação de pagamento ou mudança de status atualiza o estoque automaticamente.
                    </p>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard title="Total de pedidos" value={String(stats.total)} description="Todos os pedidos registrados" icon={ShoppingBag} />

                    <SummaryCard
                        title="Aguardando confirmação"
                        value={String(stats.pending)}
                        description="Pedidos pendentes"
                        icon={Clock3}
                        active={filters.status === 'pending'}
                        onClick={() => applyFilters(search, filters.status === 'pending' ? 'todos' : 'pending')}
                    />

                    <SummaryCard
                        title="Entregues"
                        value={String(stats.delivered)}
                        description="Pedidos concluídos"
                        icon={CheckCircle2}
                        active={filters.status === 'delivered'}
                        onClick={() => applyFilters(search, filters.status === 'delivered' ? 'todos' : 'delivered')}
                    />

                    <SummaryCard
                        title="Receita confirmada"
                        value={formatCurrency(stats.confirmed_revenue)}
                        description="Soma dos pedidos confirmados"
                        icon={DollarSign}
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
                    <div className="flex flex-col gap-4 border-b border-[var(--admin-border)] p-4 lg:flex-row lg:items-center lg:justify-between lg:p-5">
                        <form onSubmit={handleSearchSubmit} className="flex w-full max-w-md items-center gap-2">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-[var(--admin-text-muted)]" />

                                <input
                                    type="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Buscar por código, cliente ou telefone..."
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
                            <button
                                type="button"
                                onClick={() => applyFilters(search, 'todos')}
                                className={`h-9 rounded-xl border px-3 text-xs font-medium transition-colors ${
                                    filters.status === 'todos'
                                        ? 'border-[var(--admin-primary)] bg-[var(--admin-background-soft)] text-[var(--admin-primary)]'
                                        : 'border-[var(--admin-border)] bg-[var(--admin-surface)] text-[var(--admin-text-muted)] hover:bg-[var(--admin-background-soft)]'
                                }`}
                            >
                                Todos
                            </button>

                            {statusOptions.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    onClick={() => applyFilters(search, option.value)}
                                    className={`h-9 rounded-xl border px-3 text-xs font-medium transition-colors ${
                                        filters.status === option.value
                                            ? 'border-[var(--admin-primary)] bg-[var(--admin-background-soft)] text-[var(--admin-primary)]'
                                            : 'border-[var(--admin-border)] bg-[var(--admin-surface)] text-[var(--admin-text-muted)] hover:bg-[var(--admin-background-soft)]'
                                    }`}
                                >
                                    {option.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[820px]">
                            <thead className="bg-[var(--admin-background-soft)]">
                                <tr className="border-b border-[var(--admin-border)]">
                                    <TableHeader>Pedido</TableHeader>
                                    <TableHeader>Cliente</TableHeader>
                                    <TableHeader>Itens / Total</TableHeader>
                                    <TableHeader>Pagamento</TableHeader>
                                    <TableHeader>Status</TableHeader>
                                    <TableHeader className="text-right">Ações</TableHeader>
                                </tr>
                            </thead>

                            <tbody>
                                {orders.data.length > 0 ? (
                                    orders.data.map((order) => (
                                        <tr
                                            key={order.id}
                                            className="border-b border-[var(--admin-border)] transition-colors last:border-b-0 hover:bg-[var(--admin-background-soft)]"
                                        >
                                            <TableCell>
                                                <p className="font-semibold text-[var(--admin-text)]">#{order.code}</p>
                                                <p className="mt-1 text-xs text-[var(--admin-text-muted)]">{order.created_at}</p>
                                            </TableCell>

                                            <TableCell>
                                                <p className="font-medium text-[var(--admin-text)]">{order.customer_name}</p>
                                                <p className="mt-1 text-xs text-[var(--admin-text-muted)]">{order.customer_phone}</p>
                                            </TableCell>

                                            <TableCell>
                                                <p className="font-semibold text-[var(--admin-text)]">{formatCurrency(order.total)}</p>
                                                <p className="mt-1 text-xs text-[var(--admin-text-muted)]">{order.items_count} item(ns)</p>
                                            </TableCell>

                                            <TableCell>
                                                <p className="text-xs font-medium text-[var(--admin-text)]">
                                                    {paymentStatusLabels[order.payment_status] ?? order.payment_status}
                                                </p>
                                                {order.payment_method_label && (
                                                    <p className="mt-0.5 text-[11px] text-[var(--admin-text-muted)]">
                                                        {order.payment_method_label}
                                                        {order.payment_transaction?.installments && order.payment_transaction.installments > 1
                                                            ? ` (${order.payment_transaction.installments}x)`
                                                            : ''}
                                                    </p>
                                                )}
                                                <span className="mt-1 inline-flex rounded-full bg-[var(--admin-background-soft)] px-2 py-0.5 text-[10px] font-medium text-[var(--admin-text-muted)]">
                                                    {order.channel === 'infinitepay' ? 'InfinitePay' : 'WhatsApp'}
                                                </span>
                                            </TableCell>

                                            <TableCell>
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                                                        toneClasses[order.status_tone] ?? 'bg-neutral-100 text-neutral-700'
                                                    }`}
                                                >
                                                    {order.status_label}
                                                </span>
                                            </TableCell>

                                            <TableCell className="text-right">
                                                <button
                                                    type="button"
                                                    onClick={() => openOrderModal(order)}
                                                    className="inline-flex h-9 items-center gap-1.5 rounded-lg border border-[var(--admin-border)] px-3 text-xs font-medium text-[var(--admin-text)] transition-colors hover:border-[var(--admin-primary)] hover:text-[var(--admin-primary)]"
                                                >
                                                    <Eye className="size-3.5" />
                                                    Detalhes
                                                </button>
                                            </TableCell>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-12 text-center">
                                            <p className="text-sm text-[var(--admin-text-muted)]">
                                                Nenhum pedido encontrado para os filtros selecionados.
                                            </p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {orders.last_page > 1 && (
                        <div className="flex flex-col items-center justify-between gap-3 border-t border-[var(--admin-border)] px-4 py-4 sm:flex-row md:px-5">
                            <p className="text-xs text-[var(--admin-text-muted)]">
                                Mostrando {orders.from ?? 0} a {orders.to ?? 0} de {orders.total} pedidos
                            </p>

                            <nav aria-label="Paginação de pedidos" className="flex flex-wrap items-center gap-1">
                                {orders.links.map((link, index) => {
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

            <Dialog open={selectedOrder !== null} onOpenChange={(open) => !open && setSelectedOrderId(null)}>
                <DialogContent className="max-h-[90vh] overflow-y-auto border-[var(--admin-border)] bg-[var(--admin-surface)] sm:max-w-2xl">
                    {selectedOrder && (
                        <>
                            <DialogHeader>
                                <DialogTitle className="text-[var(--admin-text)]">Pedido #{selectedOrder.code}</DialogTitle>
                                <DialogDescription className="text-[var(--admin-text-muted)]">
                                    Cliente: {selectedOrder.customer_name} &bull; {selectedOrder.customer_phone}
                                    {selectedOrder.customer_email ? ` • ${selectedOrder.customer_email}` : ''}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4">
                                <div className="rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background-soft)] p-4">
                                    <h3 className="text-xs font-semibold tracking-wide text-[var(--admin-text-muted)] uppercase">
                                        Itens do pedido
                                    </h3>

                                    <div className="mt-3 space-y-2.5">
                                        {selectedOrder.items.map((item) => (
                                            <div key={item.id} className="flex items-start justify-between gap-4 text-sm">
                                                <div>
                                                    <p className="font-medium text-[var(--admin-text)]">
                                                        {item.product_name} ({item.variant_name})
                                                    </p>
                                                    <p className="text-xs text-[var(--admin-text-muted)]">
                                                        {item.quantity}x {formatCurrency(item.unit_price)}
                                                        {item.sku ? ` • SKU: ${item.sku}` : ''}
                                                    </p>
                                                </div>

                                                <strong className="text-[var(--admin-text)]">{formatCurrency(item.subtotal)}</strong>
                                            </div>
                                        ))}
                                    </div>

                                    <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-[var(--admin-border)] pt-3 text-sm">
                                        <span className="font-medium text-[var(--admin-text-muted)]">Total do pedido</span>
                                        <strong className="text-lg text-[var(--admin-text)]">{formatCurrency(selectedOrder.total)}</strong>
                                    </div>

                                    {canViewFinancial && selectedOrder.estimated_profit !== null && (
                                        <div className="mt-2 flex items-center justify-between text-xs text-[var(--admin-success)]">
                                            <span>Lucro bruto estimado (com base no custo cadastrado)</span>
                                            <strong>{formatCurrency(selectedOrder.estimated_profit)}</strong>
                                        </div>
                                    )}
                                </div>

                                {selectedOrder.payment_transaction && (
                                    <div className="rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] p-4">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <div className="flex items-center gap-2">
                                                <CreditCard className="size-4 text-[var(--admin-primary)]" />
                                                <h3 className="text-xs font-semibold tracking-wide text-[var(--admin-text-muted)] uppercase">
                                                    Pagamento InfinitePay
                                                </h3>
                                            </div>

                                            <div className="flex items-center gap-2">
                                                <span className="rounded-full bg-[var(--admin-background-soft)] px-2 py-0.5 text-[10px] font-medium text-[var(--admin-text-muted)]">
                                                    {selectedOrder.payment_transaction.driver} / {selectedOrder.payment_transaction.auth_mode}
                                                </span>

                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                                        toneClasses[selectedOrder.payment_transaction.status_tone] ??
                                                        'bg-neutral-100 text-neutral-700'
                                                    }`}
                                                >
                                                    {selectedOrder.payment_transaction.status_label}
                                                </span>
                                            </div>
                                        </div>

                                        <dl className="mt-3 grid gap-3 text-xs sm:grid-cols-2">
                                            <div>
                                                <dt className="text-[var(--admin-text-muted)]">Forma &amp; parcelas</dt>
                                                <dd className="mt-0.5 font-medium text-[var(--admin-text)]">
                                                    {selectedOrder.payment_transaction.capture_method_label ?? 'Ainda não escolhido'}
                                                    {selectedOrder.payment_transaction.installments &&
                                                    selectedOrder.payment_transaction.installments > 1
                                                        ? ` (${selectedOrder.payment_transaction.installments}x)`
                                                        : ''}
                                                </dd>
                                            </div>

                                            <div>
                                                <dt className="text-[var(--admin-text-muted)]">Valor cobrado / pago</dt>
                                                <dd className="mt-0.5 font-medium text-[var(--admin-text)]">
                                                    {formatCurrency(selectedOrder.payment_transaction.amount)}
                                                    {selectedOrder.payment_transaction.paid_amount !== null
                                                        ? ` • Pago: ${formatCurrency(selectedOrder.payment_transaction.paid_amount)}`
                                                        : ''}
                                                </dd>
                                            </div>

                                            <div>
                                                <dt className="text-[var(--admin-text-muted)]">Slug</dt>
                                                <dd className="mt-0.5 font-mono text-[11px] break-all text-[var(--admin-text)]">
                                                    {selectedOrder.payment_transaction.slug ?? '—'}
                                                </dd>
                                            </div>

                                            <div>
                                                <dt className="text-[var(--admin-text-muted)]">Transaction NSU</dt>
                                                <dd className="mt-0.5 font-mono text-[11px] break-all text-[var(--admin-text)]">
                                                    {selectedOrder.payment_transaction.transaction_nsu ?? '—'}
                                                </dd>
                                            </div>

                                            <div>
                                                <dt className="text-[var(--admin-text-muted)]">Pago em</dt>
                                                <dd className="mt-0.5 font-medium text-[var(--admin-text)]">
                                                    {selectedOrder.payment_transaction.paid_at ?? '—'}
                                                </dd>
                                            </div>

                                            <div>
                                                <dt className="text-[var(--admin-text-muted)]">Última consulta API</dt>
                                                <dd className="mt-0.5 font-medium text-[var(--admin-text)]">
                                                    {selectedOrder.payment_transaction.last_checked_at ?? '—'}
                                                </dd>
                                            </div>
                                        </dl>

                                        <div className="mt-4 flex flex-wrap items-center gap-2 border-t border-[var(--admin-border)] pt-3">
                                            {canManageOrders && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    disabled={verifyingOrderId === selectedOrder.id}
                                                    onClick={() => handleVerifyPayment(selectedOrder)}
                                                    className="h-9 rounded-xl border-[var(--admin-border)] px-3 text-xs font-semibold"
                                                >
                                                    <RefreshCw
                                                        className={`mr-1.5 size-3.5 ${verifyingOrderId === selectedOrder.id ? 'animate-spin' : ''}`}
                                                    />
                                                    {verifyingOrderId === selectedOrder.id ? 'Consultando...' : 'Verificar na InfinitePay'}
                                                </Button>
                                            )}

                                            {selectedOrder.payment_transaction.receipt_url && (
                                                <a
                                                    href={selectedOrder.payment_transaction.receipt_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex h-9 items-center gap-1.5 rounded-xl border border-[var(--admin-border)] px-3 text-xs font-semibold text-[var(--admin-primary)] transition-colors hover:bg-[var(--admin-background-soft)]"
                                                >
                                                    <ExternalLink className="size-3.5" />
                                                    Abrir comprovante
                                                </a>
                                            )}

                                            {selectedOrder.payment_transaction.status !== 'paid' &&
                                                selectedOrder.payment_transaction.checkout_url && (
                                                    <a
                                                        href={selectedOrder.payment_transaction.checkout_url}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex h-9 items-center gap-1.5 rounded-xl border border-[var(--admin-border)] px-3 text-xs font-medium text-[var(--admin-text-muted)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-text)]"
                                                    >
                                                        <ExternalLink className="size-3.5" />
                                                        Abrir link de checkout
                                                    </a>
                                                )}
                                        </div>
                                    </div>
                                )}

                                {selectedOrder.payment_events.length > 0 && (
                                    <div className="rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] p-4">
                                        <h3 className="text-xs font-semibold tracking-wide text-[var(--admin-text-muted)] uppercase">
                                            Linha do tempo de eventos
                                        </h3>

                                        <ul className="mt-3 space-y-2.5 text-xs">
                                            {selectedOrder.payment_events.map((event) => (
                                                <li
                                                    key={event.id}
                                                    className="flex flex-col gap-1 border-l-2 border-[var(--admin-border)] pl-3"
                                                >
                                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                                        <span className="font-semibold text-[var(--admin-text)]">{event.event_label}</span>
                                                        <span className="text-[11px] text-[var(--admin-text-muted)]">{event.created_at ?? '—'}</span>
                                                    </div>

                                                    <div className="flex flex-wrap items-center gap-2 text-[11px] text-[var(--admin-text-muted)]">
                                                        <span>Origem: {event.source_label}</span>
                                                        {event.external_event_id && (
                                                            <span className="font-mono">&bull; Ref: {event.external_event_id}</span>
                                                        )}
                                                    </div>

                                                    {event.notes && (
                                                        <p className="rounded-lg bg-amber-50 px-2.5 py-1.5 text-[11px] font-medium text-amber-800">
                                                            {event.notes}
                                                        </p>
                                                    )}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}

                                <form onSubmit={submitOrderUpdate} className="space-y-4">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="order-status">Status do pedido</Label>
                                            <select
                                                id="order-status"
                                                value={data.status}
                                                disabled={!canManageOrders}
                                                onChange={(e) => setData('status', e.target.value)}
                                                className="h-10 w-full rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] px-3 text-sm text-[var(--admin-text)] disabled:opacity-60"
                                            >
                                                {statusOptions.map((option) => (
                                                    <option key={option.value} value={option.value}>
                                                        {option.label}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={errors.status} />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="order-payment-status">Pagamento</Label>
                                            <select
                                                id="order-payment-status"
                                                value={data.payment_status}
                                                disabled={!canManageOrders}
                                                onChange={(e) => setData('payment_status', e.target.value)}
                                                className="h-10 w-full rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] px-3 text-sm text-[var(--admin-text)] disabled:opacity-60"
                                            >
                                                <option value="pending">Pendente</option>
                                                <option value="paid">Pago</option>
                                                <option value="refunded">Estornado</option>
                                            </select>
                                            <InputError message={errors.payment_status} />
                                        </div>
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label htmlFor="order-payment-method">Forma combinada (opcional)</Label>
                                        <input
                                            id="order-payment-method"
                                            type="text"
                                            disabled={!canManageOrders}
                                            value={data.payment_method}
                                            onChange={(e) => setData('payment_method', e.target.value)}
                                            placeholder="Ex.: Pix, Cartão na entrega, Dinheiro"
                                            className="h-10 w-full rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] px-3 text-sm text-[var(--admin-text)] disabled:opacity-60"
                                        />
                                        <InputError message={errors.payment_method} />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label htmlFor="order-notes">Observações</Label>
                                        <textarea
                                            id="order-notes"
                                            rows={3}
                                            disabled={!canManageOrders}
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                            className="w-full rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] px-3 py-2 text-sm text-[var(--admin-text)] disabled:opacity-60"
                                        />
                                        <InputError message={errors.notes} />
                                    </div>

                                    <DialogFooter className="gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setSelectedOrderId(null)}
                                            className="h-10 rounded-xl border-[var(--admin-border)]"
                                        >
                                            Fechar
                                        </Button>

                                        {canManageOrders && (
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="auth-primary-button h-10 rounded-xl px-5 font-semibold text-white"
                                            >
                                                {processing ? 'Salvando...' : 'Atualizar pedido'}
                                            </Button>
                                        )}
                                    </DialogFooter>
                                </form>
                            </div>
                        </>
                    )}
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
    active = false,
    onClick,
}: {
    title: string;
    value: string;
    description: string;
    icon: React.ElementType;
    active?: boolean;
    onClick?: () => void;
}) {
    const content = (
        <>
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

    return <article className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm">{content}</article>;
}

function TableHeader({ children, className = '' }: { children: React.ReactNode; className?: string }) {
    return (
        <th className={`px-4 py-3 text-left text-xs font-semibold tracking-wide text-[var(--admin-text-muted)] uppercase ${className}`}>
            {children}
        </th>
    );
}

function TableCell({ children, className = '' }: { children: React.ReactNode; className?: string }) {
    return <td className={`px-4 py-4 text-sm text-[var(--admin-text-muted)] ${className}`}>{children}</td>;
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
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
