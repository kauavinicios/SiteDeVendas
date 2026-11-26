import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Eye, FileEdit, MailCheck, Pencil, Plus, Send, Users } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type CampaignStatus = 'draft' | 'sent';

type CampaignItem = {
    id: number;
    subject: string;
    title: string;
    body: string;
    cta_label: string | null;
    cta_url: string | null;
    status: CampaignStatus;
    recipients_count: number;
    sent_at: string | null;
    created_at: string | null;
    creator_name: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedCampaigns = {
    data: CampaignItem[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

type CampaignStats = {
    eligible_recipients: number;
    total_opted_in: number;
    sent_campaigns: number;
    draft_campaigns: number;
};

type Props = {
    campaigns: PaginatedCampaigns;
    stats: CampaignStats;
};

type PageProps = SharedData & {
    flash?: {
        success?: string;
        error?: string;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Novidades',
        href: '/admin/novidades',
    },
];

export default function CampaignsIndex({ campaigns, stats }: Props) {
    const { flash } = usePage<PageProps>().props;

    const [modalOpen, setModalOpen] = useState(false);
    const [editingCampaign, setEditingCampaign] = useState<CampaignItem | null>(null);
    const [sendingId, setSendingId] = useState<number | null>(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm({
        subject: '',
        title: '',
        body: '',
        cta_label: '',
        cta_url: '',
    });

    const openCreateModal = () => {
        setEditingCampaign(null);
        clearErrors();
        reset();
        setModalOpen(true);
    };

    const openEditModal = (campaign: CampaignItem) => {
        if (campaign.status === 'sent') {
            return;
        }

        setEditingCampaign(campaign);
        clearErrors();
        setData({
            subject: campaign.subject,
            title: campaign.title,
            body: campaign.body,
            cta_label: campaign.cta_label ?? '',
            cta_url: campaign.cta_url ?? '',
        });
        setModalOpen(true);
    };

    const submitCampaign: FormEventHandler = (e) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setModalOpen(false);
                reset();
                setEditingCampaign(null);
            },
        };

        if (editingCampaign) {
            put(route('admin.campaigns.update', editingCampaign.id), options);
        } else {
            post(route('admin.campaigns.store'), options);
        }
    };

    const handleSendCampaign = (campaign: CampaignItem) => {
        if (campaign.status === 'sent' || sendingId === campaign.id) {
            return;
        }

        if (stats.eligible_recipients === 0) {
            alert('Não há clientes elegíveis no momento (ativos, com e-mail verificado e inscritos em novidades).');

            return;
        }

        if (
            !confirm(
                `Deseja enviar a campanha "${campaign.subject}" agora para ${stats.eligible_recipients} cliente(s) elegível(is)?`,
            )
        ) {
            return;
        }

        router.post(
            route('admin.campaigns.send', campaign.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setSendingId(campaign.id),
                onFinish: () => setSendingId(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Novidades por e-mail" />

            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <section className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-[var(--admin-text)]">Novidades por e-mail</h1>

                        <p className="mt-1 text-sm text-[var(--admin-text-muted)]">
                            Crie e dispare campanhas usando o visual oficial da boutique para clientes verificados e inscritos (LGPD).
                        </p>
                    </div>

                    <Button
                        type="button"
                        onClick={openCreateModal}
                        className="auth-primary-button h-10 rounded-xl px-4 text-sm font-semibold text-white"
                    >
                        <Plus className="mr-1.5 size-4" />
                        Nova campanha
                    </Button>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <SummaryCard
                        title="Elegíveis para envio"
                        value={stats.eligible_recipients}
                        description="Ativos, verificados e inscritos"
                        icon={MailCheck}
                    />

                    <SummaryCard
                        title="Total inscritos (LGPD)"
                        value={stats.total_opted_in}
                        description="Clientes com opt-in ativo"
                        icon={Users}
                    />

                    <SummaryCard
                        title="Campanhas enviadas"
                        value={stats.sent_campaigns}
                        description="Disparos concluídos"
                        icon={CheckCircle2}
                    />

                    <SummaryCard
                        title="Rascunhos"
                        value={stats.draft_campaigns}
                        description="Aguardando disparo"
                        icon={FileEdit}
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
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[760px]">
                            <thead className="bg-[var(--admin-background-soft)]">
                                <tr className="border-b border-[var(--admin-border)]">
                                    <TableHeader>Campanha</TableHeader>
                                    <TableHeader>Status</TableHeader>
                                    <TableHeader>Destinatários</TableHeader>
                                    <TableHeader>Criação / Envio</TableHeader>
                                    <TableHeader className="text-right">Ações</TableHeader>
                                </tr>
                            </thead>

                            <tbody>
                                {campaigns.data.length > 0 ? (
                                    campaigns.data.map((campaign) => {
                                        const isSent = campaign.status === 'sent';

                                        return (
                                            <tr
                                                key={campaign.id}
                                                className="border-b border-[var(--admin-border)] transition-colors last:border-b-0 hover:bg-[var(--admin-background-soft)]"
                                            >
                                                <TableCell>
                                                    <div className="min-w-0">
                                                        <p className="truncate font-medium text-[var(--admin-text)]">{campaign.subject}</p>
                                                        <p className="mt-1 truncate text-xs text-[var(--admin-text-muted)]">{campaign.title}</p>
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <span
                                                        className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                                                            isSent ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-800'
                                                        }`}
                                                    >
                                                        {isSent ? 'Enviada' : 'Rascunho'}
                                                    </span>
                                                </TableCell>

                                                <TableCell>{isSent ? `${campaign.recipients_count} cliente(s)` : '—'}</TableCell>

                                                <TableCell>
                                                    <div className="text-xs">
                                                        <p>{isSent ? `Enviada em ${campaign.sent_at}` : `Criada em ${campaign.created_at}`}</p>
                                                        {campaign.creator_name && (
                                                            <p className="mt-0.5 text-[11px] text-[var(--admin-text-muted)]">
                                                                Por {campaign.creator_name}
                                                            </p>
                                                        )}
                                                    </div>
                                                </TableCell>

                                                <TableCell className="text-right">
                                                    <div className="inline-flex items-center justify-end gap-1.5">
                                                        <a
                                                            href={route('admin.campaigns.preview', campaign.id)}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="inline-flex size-9 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-primary)]"
                                                            title="Pré-visualizar e-mail"
                                                            aria-label={`Pré-visualizar ${campaign.subject}`}
                                                        >
                                                            <Eye className="size-4" />
                                                        </a>

                                                        {!isSent && (
                                                            <>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => openEditModal(campaign)}
                                                                    className="inline-flex size-9 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-primary)]"
                                                                    title="Editar rascunho"
                                                                    aria-label={`Editar ${campaign.subject}`}
                                                                >
                                                                    <Pencil className="size-4" />
                                                                </button>

                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleSendCampaign(campaign)}
                                                                    disabled={sendingId === campaign.id}
                                                                    className="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[var(--admin-primary)] px-3 text-xs font-semibold text-white transition-colors hover:bg-[var(--admin-primary-dark)] disabled:opacity-50"
                                                                    title="Enviar para clientes elegíveis"
                                                                >
                                                                    <Send className="size-3.5" />
                                                                    {sendingId === campaign.id ? 'Enviando...' : 'Enviar'}
                                                                </button>
                                                            </>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </tr>
                                        );
                                    })
                                ) : (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-12 text-center">
                                            <p className="text-sm text-[var(--admin-text-muted)]">
                                                Nenhuma campanha criada ainda. Clique em <strong>Nova campanha</strong> para começar.
                                            </p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {campaigns.last_page > 1 && (
                        <div className="flex flex-col items-center justify-between gap-3 border-t border-[var(--admin-border)] px-4 py-4 sm:flex-row md:px-5">
                            <p className="text-xs text-[var(--admin-text-muted)]">
                                Mostrando {campaigns.from ?? 0} a {campaigns.to ?? 0} de {campaigns.total} campanhas
                            </p>

                            <nav aria-label="Paginação de campanhas" className="flex flex-wrap items-center gap-1">
                                {campaigns.links.map((link, index) => {
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

            <Dialog open={modalOpen} onOpenChange={setModalOpen}>
                <DialogContent className="border-[var(--admin-border)] bg-[var(--admin-surface)] sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle className="text-[var(--admin-text)]">
                            {editingCampaign ? 'Editar campanha' : 'Nova campanha de novidades'}
                        </DialogTitle>
                        <DialogDescription className="text-[var(--admin-text-muted)]">
                            O e-mail será renderizado automaticamente com a identidade visual da Butique da Jandira e link de descadastro LGPD.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitCampaign} className="space-y-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="campaign-subject">Assunto do e-mail</Label>
                            <Input
                                id="campaign-subject"
                                value={data.subject}
                                onChange={(e) => setData('subject', e.target.value)}
                                placeholder="Ex.: Novidades da semana na Butique da Jandira"
                                required
                                className="h-10 rounded-xl border-[var(--admin-border)]"
                            />
                            <InputError message={errors.subject} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="campaign-title">Título interno do e-mail</Label>
                            <Input
                                id="campaign-title"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                placeholder="Ex.: Chegaram novas fragrâncias e jogos de cama!"
                                required
                                className="h-10 rounded-xl border-[var(--admin-border)]"
                            />
                            <InputError message={errors.title} />
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="campaign-body">Mensagem</Label>
                            <textarea
                                id="campaign-body"
                                rows={5}
                                value={data.body}
                                onChange={(e) => setData('body', e.target.value)}
                                placeholder="Escreva as novidades, lançamentos ou condições especiais para suas clientes..."
                                required
                                className="w-full rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] px-3.5 py-2.5 text-sm text-[var(--admin-text)] outline-none focus:border-[var(--admin-primary)]"
                            />
                            <InputError message={errors.body} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-1.5">
                                <Label htmlFor="campaign-cta-label">Texto do botão (opcional)</Label>
                                <Input
                                    id="campaign-cta-label"
                                    value={data.cta_label}
                                    onChange={(e) => setData('cta_label', e.target.value)}
                                    placeholder="Ex.: Ver catálogo"
                                    className="h-10 rounded-xl border-[var(--admin-border)]"
                                />
                                <InputError message={errors.cta_label} />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="campaign-cta-url">Link do botão (opcional)</Label>
                                <Input
                                    id="campaign-cta-url"
                                    type="url"
                                    value={data.cta_url}
                                    onChange={(e) => setData('cta_url', e.target.value)}
                                    placeholder="https://..."
                                    className="h-10 rounded-xl border-[var(--admin-border)]"
                                />
                                <InputError message={errors.cta_url} />
                            </div>
                        </div>

                        <DialogFooter className="gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setModalOpen(false)}
                                className="h-10 rounded-xl border-[var(--admin-border)]"
                            >
                                Cancelar
                            </Button>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="auth-primary-button h-10 rounded-xl px-5 font-semibold text-white"
                            >
                                {processing ? 'Salvando...' : editingCampaign ? 'Salvar alterações' : 'Salvar rascunho'}
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
