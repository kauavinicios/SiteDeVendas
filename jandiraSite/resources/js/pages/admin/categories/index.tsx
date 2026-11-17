import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FolderTree, Pencil, Plus, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type Category = {
    id: number;
    name: string;
    slug: string;
    parent_id: number | null;
    parent?: Category | null;
    products_count: number;
};

type Props = {
    categories: Category[];
};

type PageProps = {
    flash?: {
        success?: string;
        error?: string;
    };
};

type CategoryFormData = {
    name: string;
    slug: string;
    parent_id: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categorias',
        href: '/admin/categorias',
    },
];

export default function CategoriesIndex({ categories }: Props) {
    const { flash } = usePage<PageProps>().props;
    const [open, setOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState<Category | null>(null);

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm<CategoryFormData>({
        name: '',
        slug: '',
        parent_id: '',
    });

    const closeModal = () => {
        setOpen(false);
        setEditingCategory(null);
        reset();
        clearErrors();
    };

    const openCreateModal = () => {
        setEditingCategory(null);
        setData({
            name: '',
            slug: '',
            parent_id: '',
        });
        clearErrors();
        setOpen(true);
    };

    const openEditModal = (category: Category) => {
        setEditingCategory(category);
        setData({
            name: category.name,
            slug: category.slug,
            parent_id: category.parent_id ? String(category.parent_id) : '',
        });
        clearErrors();
        setOpen(true);
    };

    const handleDelete = (category: Category) => {
        if (
            confirm(
                `Deseja realmente excluir a categoria "${category.name}"? Esta ação não pode ser desfeita.`,
            )
        ) {
            router.delete(route('admin.categories.destroy', category.id), {
                preserveScroll: true,
            });
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        };

        if (editingCategory) {
            put(route('admin.categories.update', editingCategory.id), options);
            return;
        }

        post(route('admin.categories.store'), options);
    };

    // Filtra para não permitir selecionar a própria categoria como pai na edição
    const availableParents = categories.filter(
        (c) => !editingCategory || c.id !== editingCategory.id,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Categorias" />

            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <section className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-[var(--admin-text)]">
                            Categorias
                        </h1>
                        <p className="mt-1 text-sm text-[var(--admin-text-muted)]">
                            Gerencie as categorias e departamentos para organizar os produtos.
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={openCreateModal}
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
                        Nova categoria
                    </button>
                </section>

                {flash?.success && (
                    <div className="rounded-xl border border-[var(--admin-success-border)] bg-[var(--admin-success-background)] px-4 py-3 text-sm font-medium text-[var(--admin-success)]">
                        {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        {flash.error}
                    </div>
                )}

                <section className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[640px] text-left">
                            <thead className="border-b border-[var(--admin-border)] bg-[var(--admin-background-soft)]/50">
                                <tr>
                                    <TableHeader>Nome</TableHeader>
                                    <TableHeader>Slug</TableHeader>
                                    <TableHeader>Categoria Pai</TableHeader>
                                    <TableHeader>Produtos</TableHeader>
                                    <TableHeader className="text-right">Ações</TableHeader>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-[var(--admin-border)]">
                                {categories.length > 0 ? (
                                    categories.map((category) => (
                                        <tr
                                            key={category.id}
                                            className="transition-colors hover:bg-[var(--admin-background-soft)]/50"
                                        >
                                            <TableCell className="font-medium text-[var(--admin-text)]">
                                                <div className="flex items-center gap-2">
                                                    <FolderTree className="size-4 text-[var(--admin-text-muted)]" />
                                                    <span>{category.name}</span>
                                                </div>
                                            </TableCell>

                                            <TableCell>
                                                <code className="rounded-md bg-[var(--admin-background-soft)] px-2 py-0.5 text-xs text-[var(--admin-text-muted)]">
                                                    {category.slug}
                                                </code>
                                            </TableCell>

                                            <TableCell>
                                                {category.parent ? (
                                                    <span className="text-sm text-[var(--admin-text)]">
                                                        {category.parent.name}
                                                    </span>
                                                ) : (
                                                    <span className="text-xs text-[var(--admin-text-muted)] italic">
                                                        Principal (sem pai)
                                                    </span>
                                                )}
                                            </TableCell>

                                            <TableCell>
                                                <span className="inline-flex rounded-full bg-[var(--admin-background-soft)] px-2.5 py-0.5 text-xs font-medium text-[var(--admin-text)]">
                                                    {category.products_count}{' '}
                                                    {category.products_count === 1
                                                        ? 'produto'
                                                        : 'produtos'}
                                                </span>
                                            </TableCell>

                                            <TableCell className="text-right">
                                                <div className="inline-flex items-center gap-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => openEditModal(category)}
                                                        className="inline-flex size-9 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-primary)]"
                                                        title="Editar categoria"
                                                    >
                                                        <Pencil className="size-4" />
                                                    </button>

                                                    <button
                                                        type="button"
                                                        onClick={() => handleDelete(category)}
                                                        className="inline-flex size-9 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors hover:bg-red-50 hover:text-red-600"
                                                        title="Excluir categoria"
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </button>
                                                </div>
                                            </TableCell>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-12 text-center">
                                            <p className="text-sm text-[var(--admin-text-muted)]">
                                                Nenhuma categoria cadastrada.
                                            </p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>

            <Dialog open={open} onOpenChange={(val) => !val && closeModal()}>
                <DialogContent className="max-w-md border-[var(--admin-border)] bg-[var(--admin-surface)] p-6">
                    <DialogHeader>
                        <DialogTitle className="text-lg font-semibold text-[var(--admin-text)]">
                            {editingCategory ? 'Editar categoria' : 'Nova categoria'}
                        </DialogTitle>
                        <DialogDescription className="text-sm text-[var(--admin-text-muted)]">
                            {editingCategory
                                ? 'Atualize as informações da categoria.'
                                : 'Preencha os dados para criar uma nova categoria.'}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submit} className="mt-4 space-y-4">
                        <div className="space-y-2">
                            <Label
                                htmlFor="cat-name"
                                className="text-sm font-medium text-[var(--admin-text)]"
                            >
                                Nome da categoria *
                            </Label>
                            <Input
                                id="cat-name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Ex: Cama, Mesa, Perfumaria..."
                                className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)]"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="space-y-2">
                            <Label
                                htmlFor="cat-slug"
                                className="text-sm font-medium text-[var(--admin-text)]"
                            >
                                Slug (URL amigável)
                            </Label>
                            <Input
                                id="cat-slug"
                                value={data.slug}
                                onChange={(e) => setData('slug', e.target.value)}
                                placeholder="Deixe em branco para gerar automaticamente"
                                className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)]"
                            />
                            <InputError message={errors.slug} />
                        </div>

                        <div className="space-y-2">
                            <Label
                                htmlFor="cat-parent"
                                className="text-sm font-medium text-[var(--admin-text)]"
                            >
                                Categoria Pai (Opcional)
                            </Label>
                            <select
                                id="cat-parent"
                                value={data.parent_id}
                                onChange={(e) => setData('parent_id', e.target.value)}
                                className="h-11 w-full rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)] px-3 text-sm text-[var(--admin-text)] outline-none focus:ring-2 focus:ring-[var(--admin-primary)]"
                            >
                                <option value="">Nenhuma (Categoria Principal)</option>
                                {availableParents.map((parent) => (
                                    <option key={parent.id} value={parent.id}>
                                        {parent.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.parent_id} />
                        </div>

                        <DialogFooter className="gap-2 sm:gap-2 pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={closeModal}
                                disabled={processing}
                                className="rounded-xl border-[var(--admin-border)]"
                            >
                                Cancelar
                            </Button>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="auth-primary-button rounded-xl text-white"
                            >
                                {processing
                                    ? 'Salvando...'
                                    : editingCategory
                                      ? 'Salvar alterações'
                                      : 'Criar categoria'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
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
            className={`px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--admin-text-muted)] ${className}`}
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
        <td className={`px-4 py-4 text-sm text-[var(--admin-text-muted)] ${className}`}>
            {children}
        </td>
    );
}

