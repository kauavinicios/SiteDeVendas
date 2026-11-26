import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { usePermissions } from '@/hooks/use-permissions';
import { router, useForm } from '@inertiajs/react';
import { Boxes, Pencil, Plus, Power } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

export type ProductVariant = {
    id: number;
    name: string;
    sku: string | null;
    price: string;
    cost_price: string | null;
    stock: number;
    active: boolean;
};

type VariantFormData = {
    name: string;
    sku: string;
    price: string;
    cost_price: string;
    stock: string;
    active: boolean;
};

type ProductVariantsProps = {
    productId: number;
    variants: ProductVariant[];
};

export default function ProductVariants({ productId, variants }: ProductVariantsProps) {
    const { can } = usePermissions();
    const canViewFinancial = can('view-financial-reports');

    const [open, setOpen] = useState(false);

    const [editingVariant, setEditingVariant] = useState<ProductVariant | null>(null);
    const [togglingId, setTogglingId] = useState<number | null>(null);

    const { data, setData, post, put, processing, errors, reset, clearErrors } = useForm<VariantFormData>({
        name: '',
        sku: '',
        price: '',
        cost_price: '',
        stock: '0',
        active: true,
    });

    const closeModal = () => {
        setOpen(false);
        setEditingVariant(null);
        reset();
        clearErrors();
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,

            onSuccess: () => {
                closeModal();
            },
        };

        if (editingVariant) {
            put(
                route('admin.product-variants.update', {
                    product: productId,
                    variant: editingVariant.id,
                }),
                options,
            );

            return;
        }

        post(route('admin.product-variants.store', productId), options);
    };

    const handleOpenChange = (value: boolean) => {
        if (!value) {
            closeModal();
            return;
        }

        setOpen(true);
    };

    const openCreateModal = () => {
        setEditingVariant(null);

        setData({
            name: '',
            sku: '',
            price: '',
            cost_price: '',
            stock: '0',
            active: true,
        });

        clearErrors();
        setOpen(true);
    };

    const openEditModal = (variant: ProductVariant) => {
        setEditingVariant(variant);

        setData({
            name: variant.name,
            sku: variant.sku ?? '',
            price: String(variant.price),
            cost_price: variant.cost_price ?? '',
            stock: String(variant.stock),
            active: variant.active,
        });

        clearErrors();
        setOpen(true);
    };

    const handleToggleActive = (variant: ProductVariant) => {
        if (togglingId === variant.id) {
            return;
        }

        router.put(
            route('admin.product-variants.update', {
                product: productId,
                variant: variant.id,
            }),
            {
                name: variant.name,
                sku: variant.sku ?? '',
                price: variant.price,
                ...(canViewFinancial ? { cost_price: variant.cost_price ?? '' } : {}),
                stock: variant.stock,
                active: !variant.active,
            },
            {
                preserveScroll: true,
                onStart: () => setTogglingId(variant.id),
                onFinish: () => setTogglingId(null),
            },
        );
    };

    return (
        <>
            <section className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm md:p-6">
                <div className="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-base font-semibold text-[var(--admin-text)]">Variações</h2>

                        <p className="mt-1 text-sm text-[var(--admin-text-muted)]">Gerencie preço, estoque e opções deste produto.</p>
                    </div>

                    <button
                        type="button"
                        onClick={openCreateModal}
                        className="auth-primary-button inline-flex h-10 items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold text-white"
                    >
                        <Plus className="size-4" aria-hidden="true" />
                        Adicionar variação
                    </button>
                </div>

                {variants.length > 0 ? (
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[680px]">
                            <thead>
                                <tr className="border-b border-[var(--admin-border)] bg-[var(--admin-background-soft)]">
                                    <TableHeader>Variação</TableHeader>

                                    <TableHeader>SKU</TableHeader>

                                    <TableHeader>Preço</TableHeader>

                                    {canViewFinancial && <TableHeader>Custo</TableHeader>}

                                    <TableHeader>Estoque</TableHeader>

                                    <TableHeader>Status</TableHeader>

                                    <TableHeader className="text-right">Ações</TableHeader>
                                </tr>
                            </thead>

                            <tbody>
                                {variants.map((variant) => (
                                    <tr
                                        key={variant.id}
                                        className="border-b border-[var(--admin-border)] transition-colors last:border-b-0 hover:bg-[var(--admin-background-soft)]"
                                    >
                                        <TableCell>
                                            <span className="font-medium text-[var(--admin-text)]">{variant.name}</span>
                                        </TableCell>

                                        <TableCell>{variant.sku || '—'}</TableCell>

                                        <TableCell>{formatPrice(variant.price)}</TableCell>

                                        {canViewFinancial && (
                                            <TableCell>{variant.cost_price !== null ? formatPrice(variant.cost_price) : '—'}</TableCell>
                                        )}

                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <Boxes className="size-4" />

                                                <span>{variant.stock}</span>
                                            </div>
                                        </TableCell>

                                        <TableCell>
                                            <StatusBadge active={variant.active} />
                                        </TableCell>

                                        <TableCell className="text-right">
                                            <div className="inline-flex items-center gap-1">
                                                <button
                                                    type="button"
                                                    onClick={() => openEditModal(variant)}
                                                    className="inline-flex size-9 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-primary)]"
                                                    aria-label={`Editar ${variant.name}`}
                                                    title="Editar variação"
                                                >
                                                    <Pencil className="size-4" aria-hidden="true" />
                                                </button>

                                                <button
                                                    type="button"
                                                    onClick={() => handleToggleActive(variant)}
                                                    disabled={togglingId === variant.id}
                                                    className={`inline-flex size-9 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors disabled:opacity-40 ${
                                                        variant.active
                                                            ? 'hover:bg-amber-50 hover:text-amber-700'
                                                            : 'hover:bg-green-50 hover:text-green-700'
                                                    } `}
                                                    aria-label={variant.active ? `Desativar ${variant.name}` : `Ativar ${variant.name}`}
                                                    title={variant.active ? 'Desativar variação' : 'Ativar variação'}
                                                >
                                                    <Power className="size-4" aria-hidden="true" />
                                                </button>
                                            </div>
                                        </TableCell>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div className="flex min-h-44 flex-col items-center justify-center rounded-xl border border-dashed border-[var(--admin-border)] bg-[var(--admin-background-soft)] px-6 text-center">
                        <Boxes className="mb-3 size-8 text-[var(--admin-text-muted)]" aria-hidden="true" />

                        <p className="text-sm font-medium text-[var(--admin-text)]">Nenhuma variação cadastrada</p>

                        <p className="mt-1 max-w-sm text-xs leading-relaxed text-[var(--admin-text-muted)]">
                            Adicione uma variação para definir preço, estoque e SKU deste produto.
                        </p>
                    </div>
                )}
            </section>
            <Dialog open={open} onOpenChange={handleOpenChange}>
                <DialogContent className="border-[var(--admin-border)] bg-[var(--admin-surface)] sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="text-[var(--admin-text)]">{editingVariant ? 'Editar variação' : 'Adicionar variação'}</DialogTitle>

                        <DialogDescription className="text-[var(--admin-text-muted)]">
                            {editingVariant
                                ? 'Atualize o preço, estoque e demais dados desta variação.'
                                : 'Informe o preço, estoque e demais dados desta opção do produto.'}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submit} className="space-y-5">
                        <div className="grid gap-2">
                            <Label htmlFor="variant-name">Nome da variação</Label>

                            <Input
                                id="variant-name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Ex.: Casal, Queen, 100 ml"
                                required
                                className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)]"
                            />

                            <p className="text-xs text-[var(--admin-text-muted)]">Use algo que diferencie esta opção das demais.</p>

                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="variant-sku">SKU</Label>

                            <Input
                                id="variant-sku"
                                value={data.sku}
                                onChange={(e) => setData('sku', e.target.value)}
                                placeholder="Ex.: PERF-NIGHT-100"
                                className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)]"
                            />

                            <p className="text-xs text-[var(--admin-text-muted)]">Opcional. Código interno para identificar a variação.</p>

                            <InputError message={errors.sku} />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="variant-price">Preço</Label>

                                <Input
                                    id="variant-price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.price}
                                    onChange={(e) => setData('price', e.target.value)}
                                    placeholder="0,00"
                                    required
                                    className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)]"
                                />

                                <InputError message={errors.price} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="variant-stock">Estoque</Label>

                                <Input
                                    id="variant-stock"
                                    type="number"
                                    min="0"
                                    step="1"
                                    value={data.stock}
                                    onChange={(e) => setData('stock', e.target.value)}
                                    required
                                    className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)]"
                                />

                                <InputError message={errors.stock} />
                            </div>
                        </div>

                        {canViewFinancial && (
                            <div className="grid gap-2">
                                <Label htmlFor="variant-cost-price">Preço de custo (R$ — opcional)</Label>

                                <Input
                                    id="variant-cost-price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={data.cost_price}
                                    onChange={(e) => setData('cost_price', e.target.value)}
                                    placeholder="0,00"
                                    className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)]"
                                />

                                <p className="text-xs text-[var(--admin-text-muted)]">
                                    Uso interno de Administração e Gerência para estimar lucros nos relatórios.
                                </p>

                                <InputError message={errors.cost_price} />
                            </div>
                        )}

                        <label
                            htmlFor="variant-active"
                            className="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] p-4"
                        >
                            <div>
                                <p className="text-sm font-medium text-[var(--admin-text)]">Variação ativa</p>

                                <p className="mt-1 text-xs text-[var(--admin-text-muted)]">Permite que esta opção seja exibida para o cliente.</p>
                            </div>

                            <input
                                id="variant-active"
                                type="checkbox"
                                checked={data.active}
                                onChange={(e) => setData('active', e.target.checked)}
                                className="size-4 accent-[var(--admin-primary)]"
                            />
                        </label>

                        <DialogFooter className="gap-2 sm:gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={closeModal}
                                disabled={processing}
                                className="rounded-xl border-[var(--admin-border)]"
                            >
                                Cancelar
                            </Button>

                            <Button type="submit" disabled={processing} className="auth-primary-button rounded-xl text-white">
                                {processing ? 'Salvando...' : editingVariant ? 'Salvar alterações' : 'Adicionar variação'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
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

function StatusBadge({ active }: { active: boolean }) {
    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${
                active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'
            } `}
        >
            {active ? 'Ativa' : 'Inativa'}
        </span>
    );
}

function formatPrice(price: string | number) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(Number(price));
}
