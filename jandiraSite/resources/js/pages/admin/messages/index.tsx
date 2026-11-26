import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarDays, Check, Eye, Inbox, Mail, MailOpen, MessageCircle, MessageSquare, Search } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

type MessageStatusFilter = 'todos' | 'nao-lidas' | 'lidas';

type ContactMessageItem = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    subject: string;
    message: string;
    is_read: boolean;
    read_at: string | null;
    created_at: string | null;
    is_registered_customer: boolean;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedMessages = {
    data: ContactMessageItem[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

type MessageStats = {
    total: number;
    unread: number;
    read: number;
    today: number;
};

type MessageFilters = {
    busca: string;
    status: MessageStatusFilter;
};

type Props = {
    messages: PaginatedMessages;
    stats: MessageStats;
    filters: MessageFilters;
};

type PageProps = SharedData & {
    flash?: {
        success?: string;
        error?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mensagens',
        href: '/admin/mensagens',
    },
];

const statusOptions: { value: MessageStatusFilter; label: string }[] = [
    { value: 'todos', label: 'Todas' },
    { value: 'nao-lidas', label: 'Não lidas' },
    { value: 'lidas', label: 'Lidas' },
];

export default function MessagesIndex({ messages, stats, filters }: Props) {
    const { flash } = usePage<PageProps>().props;

    const [search, setSearch] = useState(filters.busca);
    const [togglingId, setTogglingId] = useState<number | null>(null);
    const [selectedMessage, setSelectedMessage] = useState<ContactMessageItem | null>(null);

    useEffect(() => {
        setSearch(filters.busca);
    }, [filters.busca]);

    const applyFilters = (nextSearch: string, nextStatus: MessageStatusFilter) => {
        const params: Record<string, string> = {};
        const trimmed = nextSearch.trim();

        if (trimmed !== '') {
            params.busca = trimmed;
        }

        if (nextStatus !== 'todos') {
            params.status = nextStatus;
        }

        router.get(route('admin.messages.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleSearchSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, filters.status);
    };

    const handleToggleRead = (item: ContactMessageItem) => {
        if (togglingId === item.id) {
            return;
        }

        router.patch(
            route('admin.messages.toggle-read', item.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setTogglingId(item.id),
                onSuccess: () => {
                    setSelectedMessage((current) => {
                        if (!current || current.id !== item.id) {
                            return current;
                        }

                        return {
                            ...current,
                            is_read: !current.is_read,
                        };
                    });
                },
                onFinish: () => setTogglingId(null),
            },
        );
    };

    const selectedWhatsappUrl = selectedMessage ? formatWhatsappUrl(selectedMessage.phone, selectedMessage.subject) : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mensagens de contato" />

            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <section>
                    <h1 className="text-2xl font-semibold text-[var(--admin-text)]">Mensagens de contato</h1>

                    <p className="mt-1 text-sm text-[var(--admin-text-muted)]">
                        Acompanhe as dúvidas e solicitações enviadas pelos visitantes e clientes na página de Contato.
                    </p>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard title="Total de mensagens" value={stats.total} description="Mensagens registradas pelo site" icon={Inbox} />

                    <SummaryCard title="Não lidas" value={stats.unread} description="Aguardando leitura da equipe" icon={MessageSquare} />

                    <SummaryCard title="Lidas" value={stats.read} description="Já visualizadas e atendidas" icon={MailOpen} />

                    <SummaryCard title="Recebidas hoje" value={stats.today} description="Novos contatos desde 00h" icon={CalendarDays} />
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
                    <div className="flex flex-col gap-4 border-b border-[var(--admin-border)] p-4 lg:flex-row lg:items-center lg:justify-between md:p-5">
                        <form onSubmit={handleSearchSubmit} className="flex w-full max-w-md items-center gap-2">
                            <div className="relative flex-1">
                                <Search className="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-[var(--admin-text-muted)]" />
                                <input
                                    type="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Buscar por nome, e-mail, assunto ou mensagem..."
                                    className="h-10 w-full rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] pr-3 pl-9 text-sm text-[var(--admin-text)] outline-none focus:border-[var(--admin-primary)]"
                                />
                            </div>

                            <Button type="submit" variant="outline" className="h-10 rounded-xl border-[var(--admin-border)] px-4">
                                Buscar
                            </Button>
                        </form>

                        <div className="flex flex-wrap items-center gap-1.5 rounded-xl bg-[var(--admin-background-soft)] p-1">
                            {statusOptions.map((option) => {
                                const isActive = filters.status === option.value;

                                return (
                                    <button
                                        key={option.value}
                                        type="button"
                                        onClick={() => applyFilters(search, option.value)}
                                        className={`rounded-lg px-3 py-1.5 text-xs font-medium transition-colors ${
                                            isActive
                                                ? 'bg-[var(--admin-surface)] text-[var(--admin-primary)] shadow-xs'
                                                : 'text-[var(--admin-text-muted)] hover:text-[var(--admin-text)]'
                                        }`}
                                    >
                                        {option.label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[820px]">
                            <thead className="bg-[var(--admin-background-soft)]">
                                <tr className="border-b border-[var(--admin-border)]">
                                    <TableHeader>Remetente</TableHeader>
                                    <TableHeader>Assunto &amp; Prévia</TableHeader>
                                    <TableHeader>Status</TableHeader>
                                    <TableHeader>Recebida em</TableHeader>
                                    <TableHeader className="text-right">Ações</TableHeader>
                                </tr>
                            </thead>

                            <tbody>
                                {messages.data.length > 0 ? (
                                    messages.data.map((item) => (
                                        <tr
                                            key={item.id}
                                            className={`border-b border-[var(--admin-border)] transition-colors last:border-b-0 hover:bg-[var(--admin-background-soft)] ${
                                                !item.is_read ? 'bg-[#fcf8f6]' : ''
                                            }`}
                                        >
                                            <TableCell>
                                                <div className="min-w-0">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <p className="truncate font-medium text-[var(--admin-text)]">{item.name}</p>
                                                        {item.is_registered_customer && (
                                                            <span className="inline-flex rounded-full bg-[var(--admin-background-soft)] px-2 py-0.5 text-[10px] font-semibold text-[var(--admin-primary)]">
                                                                Cliente
                                                            </span>
                                                        )}
                                                    </div>
                                                    <p className="mt-0.5 truncate text-xs text-[var(--admin-text-muted)]">{item.email}</p>
                                                    {item.phone && (
                                                        <p className="mt-0.5 truncate text-xs text-[var(--admin-text-muted)]">{item.phone}</p>
                                                    )}
                                                </div>
                                            </TableCell>

                                            <TableCell className="max-w-sm">
                                                <p className="truncate font-medium text-[var(--admin-text)]">{item.subject}</p>
                                                <p className="mt-0.5 truncate text-xs text-[var(--admin-text-muted)]">{item.message}</p>
                                            </TableCell>

                                            <TableCell>
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                                                        item.is_read ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-800'
                                                    }`}
                                                >
                                                    {item.is_read ? 'Lida' : 'Não lida'}
                                                </span>
                                            </TableCell>

                                            <TableCell>
                                                <span className="text-xs">{item.created_at ?? '—'}</span>
                                            </TableCell>

                                            <TableCell className="text-right">
                                                <div className="inline-flex items-center justify-end gap-1.5">
                                                    <button
                                                        type="button"
                                                        onClick={() => setSelectedMessage(item)}
                                                        className="inline-flex h-9 items-center gap-1.5 rounded-lg border border-[var(--admin-border)] px-3 text-xs font-medium text-[var(--admin-text)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-primary)]"
                                                    >
                                                        <Eye className="size-3.5" />
                                                        Ler
                                                    </button>

                                                    <button
                                                        type="button"
                                                        onClick={() => handleToggleRead(item)}
                                                        disabled={togglingId === item.id}
                                                        className="inline-flex size-9 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-primary)] disabled:opacity-50"
                                                        title={item.is_read ? 'Marcar como não lida' : 'Marcar como lida'}
                                                        aria-label={
                                                            item.is_read ? `Marcar mensagem de ${item.name} como não lida` : `Marcar mensagem de ${item.name} como lida`
                                                        }
                                                    >
                                                        {item.is_read ? <Mail className="size-4" /> : <Check className="size-4" />}
                                                    </button>
                                                </div>
                                            </TableCell>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-12 text-center">
                                            <p className="text-sm text-[var(--admin-text-muted)]">
                                                Nenhuma mensagem encontrada para os filtros selecionados.
                                            </p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {messages.last_page > 1 && (
                        <div className="flex flex-col items-center justify-between gap-3 border-t border-[var(--admin-border)] px-4 py-4 sm:flex-row md:px-5">
                            <p className="text-xs text-[var(--admin-text-muted)]">
                                Mostrando {messages.from ?? 0} a {messages.to ?? 0} de {messages.total} mensagens
                            </p>

                            <nav aria-label="Paginação de mensagens" className="flex flex-wrap items-center gap-1">
                                {messages.links.map((link, index) => {
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

            <Dialog open={selectedMessage !== null} onOpenChange={(open) => !open && setSelectedMessage(null)}>
                <DialogContent className="border-[var(--admin-border)] bg-[var(--admin-surface)] sm:max-w-xl">
                    {selectedMessage && (
                        <>
                            <DialogHeader>
                                <div className="flex flex-wrap items-center gap-2">
                                    <span
                                        className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                            selectedMessage.is_read ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-800'
                                        }`}
                                    >
                                        {selectedMessage.is_read ? 'Lida' : 'Não lida'}
                                    </span>

                                    {selectedMessage.is_registered_customer && (
                                        <span className="inline-flex rounded-full bg-[var(--admin-background-soft)] px-2.5 py-0.5 text-xs font-medium text-[var(--admin-primary)]">
                                            Cliente cadastrado
                                        </span>
                                    )}
                                </div>

                                <DialogTitle className="pt-1 text-[var(--admin-text)]">{selectedMessage.subject}</DialogTitle>

                                <DialogDescription className="text-[var(--admin-text-muted)]">
                                    Enviada por <strong className="text-[var(--admin-text)]">{selectedMessage.name}</strong> ({selectedMessage.email})
                                    {selectedMessage.created_at ? ` em ${selectedMessage.created_at}` : ''}.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4">
                                <div className="grid gap-3 rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background-soft)] p-3.5 text-xs sm:grid-cols-2">
                                    <div>
                                        <p className="font-semibold text-[var(--admin-text-muted)] uppercase">E-mail</p>
                                        <p className="mt-0.5 font-medium text-[var(--admin-text)]">{selectedMessage.email}</p>
                                    </div>

                                    <div>
                                        <p className="font-semibold text-[var(--admin-text-muted)] uppercase">Telefone / WhatsApp</p>
                                        <p className="mt-0.5 font-medium text-[var(--admin-text)]">
                                            {selectedMessage.phone ?? 'Não informado'}
                                        </p>
                                    </div>
                                </div>

                                <div className="max-h-72 overflow-y-auto rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] p-4 text-sm leading-relaxed whitespace-pre-wrap text-[var(--admin-text)]">
                                    {selectedMessage.message}
                                </div>
                            </div>

                            <DialogFooter className="flex-wrap gap-2 pt-2 sm:justify-between">
                                <div className="flex flex-wrap gap-2">
                                    <a
                                        href={`mailto:${selectedMessage.email}?subject=${encodeURIComponent(`Re: ${selectedMessage.subject}`)}`}
                                        className="inline-flex h-10 items-center gap-1.5 rounded-xl border border-[var(--admin-border)] px-3.5 text-xs font-semibold text-[var(--admin-text)] transition-colors hover:bg-[var(--admin-background-soft)]"
                                    >
                                        <Mail className="size-3.5" />
                                        Responder por e-mail
                                    </a>

                                    {selectedWhatsappUrl && (
                                        <a
                                            href={selectedWhatsappUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex h-10 items-center gap-1.5 rounded-xl border border-[var(--admin-border)] px-3.5 text-xs font-semibold text-[var(--admin-primary)] transition-colors hover:bg-[var(--admin-background-soft)]"
                                        >
                                            <MessageCircle className="size-3.5" />
                                            Abrir WhatsApp
                                        </a>
                                    )}
                                </div>

                                <Button
                                    type="button"
                                    onClick={() => handleToggleRead(selectedMessage)}
                                    disabled={togglingId === selectedMessage.id}
                                    className="auth-primary-button h-10 rounded-xl px-4 text-xs font-semibold text-white"
                                >
                                    {selectedMessage.is_read ? 'Marcar como não lida' : 'Marcar como lida'}
                                </Button>
                            </DialogFooter>
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

function formatPaginationLabel(raw: string): string {
    if (raw.includes('Previous') || raw.includes('&laquo;')) {
        return 'Anterior';
    }

    if (raw.includes('Next') || raw.includes('&raquo;')) {
        return 'Próxima';
    }

    return raw;
}

function formatWhatsappUrl(phone: string | null, subject: string): string | null {
    if (!phone) {
        return null;
    }

    const digits = phone.replace(/\D+/g, '');

    if (digits.length < 10) {
        return null;
    }

    const normalized = digits.startsWith('55') ? digits : `55${digits}`;
    const text = encodeURIComponent(`Olá! Sobre sua mensagem "${subject}" enviada pelo site da Butique da Jandira:`);

    return `https://wa.me/${normalized}?text=${text}`;
}
