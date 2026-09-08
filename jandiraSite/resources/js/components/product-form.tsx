import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { Link } from '@inertiajs/react';
import { Save } from 'lucide-react';

export type Category = {
    id: number;
    name: string;
    children: Category[];
};

export type ProductFormData = {
    name: string;
    category_id: string;
    description: string;
    active: boolean;
    featured: boolean;
};

type ProductFormProps = {
    data: ProductFormData;
    categories: Category[];
    errors: Partial<Record<keyof ProductFormData, string>>;
    processing: boolean;
    submitLabel: string;
    onChange: <K extends keyof ProductFormData>(
        field: K,
        value: ProductFormData[K],
    ) => void;
    onSubmit: (e: React.FormEvent<HTMLFormElement>) => void;
};

export default function ProductForm({
    data,
    categories,
    errors,
    processing,
    submitLabel,
    onChange,
    onSubmit,
}: ProductFormProps) {
    return (
        <form
            onSubmit={onSubmit}
            className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]"
        >
            <div className="space-y-6">
                <section
                    className="
                        rounded-2xl
                        border
                        border-[var(--admin-border)]
                        bg-[var(--admin-surface)]
                        p-5
                        shadow-sm
                        md:p-6
                    "
                >
                    <div className="mb-6">
                        <h2 className="text-base font-semibold text-[var(--admin-text)]">
                            Informações do produto
                        </h2>

                        <p className="mt-1 text-sm text-[var(--admin-text-muted)]">
                            Dados exibidos no catálogo da loja.
                        </p>
                    </div>

                    <div className="space-y-5">
                        <div className="grid gap-2">
                            <Label htmlFor="name">
                                Nome do produto
                            </Label>

                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    onChange('name', e.target.value)
                                }
                                required
                                autoFocus
                                placeholder="Ex.: Lençol Casal 200 fios"
                                className="
                                    h-11
                                    rounded-xl
                                    border-[var(--admin-border)]
                                    bg-[var(--admin-background)]
                                    text-[var(--admin-text)]
                                    focus-visible:border-[var(--admin-primary)]
                                    focus-visible:ring-[var(--admin-primary)]/15
                                "
                            />

                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="category_id">
                                Categoria
                            </Label>

                            <select
                                id="category_id"
                                value={data.category_id}
                                onChange={(e) =>
                                    onChange(
                                        'category_id',
                                        e.target.value,
                                    )
                                }
                                required
                                className="
                                    h-11
                                    w-full
                                    rounded-xl
                                    border
                                    border-[var(--admin-border)]
                                    bg-[var(--admin-background)]
                                    px-3
                                    text-sm
                                    text-[var(--admin-text)]
                                    outline-none
                                    focus:border-[var(--admin-primary)]
                                    focus:ring-2
                                    focus:ring-[var(--admin-primary)]/10
                                "
                            >
                                <option value="">
                                    Selecione uma categoria
                                </option>

                                {renderCategoryOptions(categories)}
                            </select>

                            <InputError
                                message={errors.category_id}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">
                                Descrição
                            </Label>

                            <textarea
                                id="description"
                                value={data.description}
                                onChange={(e) =>
                                    onChange(
                                        'description',
                                        e.target.value,
                                    )
                                }
                                rows={6}
                                placeholder="Descreva as principais características do produto."
                                className="
                                    w-full
                                    resize-y
                                    rounded-xl
                                    border
                                    border-[var(--admin-border)]
                                    bg-[var(--admin-background)]
                                    px-3
                                    py-3
                                    text-sm
                                    leading-relaxed
                                    text-[var(--admin-text)]
                                    outline-none
                                    focus:border-[var(--admin-primary)]
                                    focus:ring-2
                                    focus:ring-[var(--admin-primary)]/10
                                "
                            />

                            <InputError
                                message={errors.description}
                            />
                        </div>
                    </div>
                </section>
            </div>

            <aside className="space-y-6">
                <section
                    className="
                        rounded-2xl
                        border
                        border-[var(--admin-border)]
                        bg-[var(--admin-surface)]
                        p-5
                        shadow-sm
                    "
                >
                    <div className="mb-5">
                        <h2 className="text-base font-semibold text-[var(--admin-text)]">
                            Publicação
                        </h2>

                        <p className="mt-1 text-sm text-[var(--admin-text-muted)]">
                            Controle a visibilidade do produto.
                        </p>
                    </div>

                    <div className="space-y-4">
                        <label
                            htmlFor="active"
                            className="
                                flex
                                cursor-pointer
                                items-start
                                justify-between
                                gap-4
                                rounded-xl
                                border
                                border-[var(--admin-border)]
                                p-4
                            "
                        >
                            <div>
                                <p className="text-sm font-medium text-[var(--admin-text)]">
                                    Produto ativo
                                </p>

                                <p className="mt-1 text-xs text-[var(--admin-text-muted)]">
                                    Define se o produto aparece na loja.
                                </p>
                            </div>

                            <input
                                id="active"
                                type="checkbox"
                                checked={data.active}
                                onChange={(e) =>
                                    onChange(
                                        'active',
                                        e.target.checked,
                                    )
                                }
                                className="mt-0.5 size-4 accent-[var(--admin-primary)]"
                            />
                        </label>

                        <label
                            htmlFor="featured"
                            className="
                                flex
                                cursor-pointer
                                items-start
                                justify-between
                                gap-4
                                rounded-xl
                                border
                                border-[var(--admin-border)]
                                p-4
                            "
                        >
                            <div>
                                <p className="text-sm font-medium text-[var(--admin-text)]">
                                    Produto em destaque
                                </p>

                                <p className="mt-1 text-xs text-[var(--admin-text-muted)]">
                                    Exibe o produto em áreas de destaque.
                                </p>
                            </div>

                            <input
                                id="featured"
                                type="checkbox"
                                checked={data.featured}
                                onChange={(e) =>
                                    onChange(
                                        'featured',
                                        e.target.checked,
                                    )
                                }
                                className="mt-0.5 size-4 accent-[var(--admin-primary)]"
                            />
                        </label>
                    </div>
                </section>

                <section
                    className="
                        rounded-2xl
                        border
                        border-[var(--admin-border)]
                        bg-[var(--admin-surface)]
                        p-5
                        shadow-sm
                    "
                >
                    <div className="flex flex-col gap-3">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="
                                auth-primary-button
                                h-11
                                w-full
                                rounded-xl
                                font-semibold
                                text-white
                            "
                        >
                            <Save className="size-4" />

                            {processing
                                ? 'Salvando...'
                                : submitLabel}
                        </Button>

                        <Link
                            href="/admin/produtos"
                            className="
                                flex
                                h-10
                                items-center
                                justify-center
                                rounded-xl
                                border
                                border-[var(--admin-border)]
                                text-sm
                                font-medium
                                text-[var(--admin-text-muted)]
                                transition-colors
                                hover:bg-[var(--admin-background-soft)]
                                hover:text-[var(--admin-text)]
                            "
                        >
                            Cancelar
                        </Link>
                    </div>
                </section>
            </aside>
        </form>
    );
}

function renderCategoryOptions(
    categories: Category[],
    level = 0,
): React.ReactNode[] {
    return categories.flatMap((category) => [
        <option
            key={category.id}
            value={category.id}
        >
            {level > 0
                ? `${'— '.repeat(level)}${category.name}`
                : category.name}
        </option>,

        ...renderCategoryOptions(
            category.children ?? [],
            level + 1,
        ),
    ]);
}