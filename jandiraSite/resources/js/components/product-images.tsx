import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    ImagePlus,
    Star,
    Trash2,
    Upload,
    X,
} from 'lucide-react';
import { ChangeEvent, useEffect, useMemo, useRef, useState } from 'react';

export type ProductImageItem = {
    id: number;
    path: string;
    url: string;
    position: number;
};

type ProductImagesProps = {
    productId: number;
    images: ProductImageItem[];
};

export default function ProductImages({
    productId,
    images,
}: ProductImagesProps) {
    const fileInputRef = useRef<HTMLInputElement | null>(null);
    const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
    const [uploading, setUploading] = useState(false);
    const [mutating, setMutating] = useState(false);
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    const previews = useMemo(
        () =>
            selectedFiles.map((file) => ({
                name: file.name,
                url: URL.createObjectURL(file),
            })),
        [selectedFiles],
    );

    useEffect(() => {
        return () => {
            previews.forEach((preview) => URL.revokeObjectURL(preview.url));
        };
    }, [previews]);

    const busy = uploading || mutating;

    const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(e.target.files ?? []);

        if (files.length === 0) {
            return;
        }

        setSelectedFiles((prev) => [...prev, ...files].slice(0, 10));
        setErrorMessage(null);
        e.target.value = '';
    };

    const removeSelectedFile = (index: number) => {
        setSelectedFiles((prev) => prev.filter((_, i) => i !== index));
        setErrorMessage(null);
    };

    const clearSelection = () => {
        setSelectedFiles([]);
        setErrorMessage(null);
    };

    const handleUpload = () => {
        if (selectedFiles.length === 0 || busy) {
            return;
        }

        router.post(
            route('admin.product-images.store', productId),
            { images: selectedFiles },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => {
                    setUploading(true);
                    setErrorMessage(null);
                },
                onFinish: () => setUploading(false),
                onSuccess: () => {
                    setSelectedFiles([]);
                },
                onError: (errors) => {
                    const firstError =
                        errors.images ??
                        Object.entries(errors).find(([key]) =>
                            key.startsWith('images.'),
                        )?.[1];

                    setErrorMessage(
                        firstError ??
                            'Não foi possível enviar as imagens selecionadas.',
                    );
                },
            },
        );
    };

    const submitReorder = (nextOrder: number[]) => {
        if (busy) {
            return;
        }

        router.patch(
            route('admin.product-images.reorder', productId),
            { order: nextOrder },
            {
                preserveScroll: true,
                onStart: () => {
                    setMutating(true);
                    setErrorMessage(null);
                },
                onFinish: () => setMutating(false),
                onError: () => {
                    setErrorMessage(
                        'Não foi possível atualizar a ordem das imagens.',
                    );
                },
            },
        );
    };

    const moveImage = (fromIndex: number, toIndex: number) => {
        if (toIndex < 0 || toIndex >= images.length) {
            return;
        }

        const ids = images.map((img) => img.id);
        const [moved] = ids.splice(fromIndex, 1);
        ids.splice(toIndex, 0, moved);
        submitReorder(ids);
    };

    const makePrimary = (index: number) => {
        if (index <= 0 || index >= images.length) {
            return;
        }

        const ids = images.map((img) => img.id);
        const [target] = ids.splice(index, 1);
        ids.unshift(target);
        submitReorder(ids);
    };

    const handleDelete = (image: ProductImageItem) => {
        if (busy) {
            return;
        }

        if (!confirm('Deseja realmente excluir esta imagem do produto?')) {
            return;
        }

        router.delete(
            route('admin.product-images.destroy', {
                product: productId,
                image: image.id,
            }),
            {
                preserveScroll: true,
                onStart: () => {
                    setMutating(true);
                    setErrorMessage(null);
                },
                onFinish: () => setMutating(false),
                onError: () => {
                    setErrorMessage('Não foi possível excluir a imagem.');
                },
            },
        );
    };

    return (
        <section className="rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-5 shadow-sm md:p-6">
            <div className="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-base font-semibold text-[var(--admin-text)]">
                        Imagens do produto
                    </h2>

                    <p className="mt-1 text-sm text-[var(--admin-text-muted)]">
                        Envie até 10 imagens por vez (JPG, PNG ou WEBP, máximo
                        de 5 MB cada). A primeira imagem será exibida como
                        principal.
                    </p>
                </div>

                <div>
                    <input
                        ref={fileInputRef}
                        type="file"
                        multiple
                        accept="image/jpeg,image/png,image/webp"
                        onChange={handleFileChange}
                        className="hidden"
                    />

                    <button
                        type="button"
                        disabled={busy}
                        onClick={() => fileInputRef.current?.click()}
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
                            disabled:opacity-50
                        "
                    >
                        <ImagePlus className="size-4" aria-hidden="true" />
                        Selecionar imagens
                    </button>
                </div>
            </div>

            {errorMessage && (
                <div className="mb-4">
                    <InputError message={errorMessage} />
                </div>
            )}

            {previews.length > 0 && (
                <div className="mb-6 rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background-soft)] p-4">
                    <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm font-medium text-[var(--admin-text)]">
                            {previews.length}{' '}
                            {previews.length === 1
                                ? 'imagem selecionada para envio'
                                : 'imagens selecionadas para envio'}
                        </p>

                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                disabled={busy}
                                onClick={clearSelection}
                                className="h-9 rounded-xl border-[var(--admin-border)] text-xs"
                            >
                                Limpar seleção
                            </Button>

                            <Button
                                type="button"
                                disabled={busy}
                                onClick={handleUpload}
                                className="auth-primary-button h-9 rounded-xl text-xs font-semibold text-white"
                            >
                                <Upload className="size-3.5" />
                                {uploading ? 'Enviando...' : 'Enviar imagens'}
                            </Button>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
                        {previews.map((preview, index) => (
                            <div
                                key={`${preview.name}-${index}`}
                                className="group relative overflow-hidden rounded-xl border border-[var(--admin-border)] bg-[var(--admin-surface)]"
                            >
                                <div className="aspect-square w-full overflow-hidden bg-[var(--admin-background)]">
                                    <img
                                        src={preview.url}
                                        alt={preview.name}
                                        className="size-full object-cover"
                                    />
                                </div>

                                <button
                                    type="button"
                                    disabled={busy}
                                    onClick={() => removeSelectedFile(index)}
                                    aria-label={`Remover ${preview.name} da seleção`}
                                    className="
                                        absolute
                                        right-1.5
                                        top-1.5
                                        inline-flex
                                        size-7
                                        items-center
                                        justify-center
                                        rounded-full
                                        bg-black/65
                                        text-white
                                        transition-colors
                                        hover:bg-black/80
                                    "
                                >
                                    <X className="size-3.5" />
                                </button>

                                <p className="truncate px-2 py-1.5 text-xs text-[var(--admin-text-muted)]">
                                    {preview.name}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {images.length > 0 ? (
                <div className="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    {images.map((image, index) => (
                        <article
                            key={image.id}
                            className="overflow-hidden rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background)]"
                        >
                            <div className="relative aspect-square w-full overflow-hidden bg-[var(--admin-background-soft)]">
                                <img
                                    src={image.url}
                                    alt={`Imagem ${index + 1} do produto`}
                                    className="size-full object-cover"
                                />

                                <span
                                    className={`
                                        absolute
                                        left-2.5
                                        top-2.5
                                        inline-flex
                                        rounded-full
                                        px-2.5
                                        py-0.5
                                        text-xs
                                        font-semibold
                                        shadow-sm
                                        ${
                                            index === 0
                                                ? 'bg-[var(--admin-primary)] text-white'
                                                : 'bg-white/90 text-[var(--admin-text)]'
                                        }
                                    `}
                                >
                                    {index === 0 ? 'Principal' : `#${index + 1}`}
                                </span>
                            </div>

                            <div className="flex items-center justify-between gap-1 border-t border-[var(--admin-border)] bg-[var(--admin-surface)] p-2">
                                <div className="flex items-center gap-1">
                                    <button
                                        type="button"
                                        disabled={busy || index === 0}
                                        onClick={() =>
                                            moveImage(index, index - 1)
                                        }
                                        aria-label="Mover imagem para esquerda"
                                        title="Mover para esquerda"
                                        className="inline-flex size-8 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-text)] disabled:opacity-35"
                                    >
                                        <ArrowLeft className="size-4" />
                                    </button>

                                    <button
                                        type="button"
                                        disabled={
                                            busy || index === images.length - 1
                                        }
                                        onClick={() =>
                                            moveImage(index, index + 1)
                                        }
                                        aria-label="Mover imagem para direita"
                                        title="Mover para direita"
                                        className="inline-flex size-8 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-text)] disabled:opacity-35"
                                    >
                                        <ArrowRight className="size-4" />
                                    </button>

                                    {index > 0 && (
                                        <button
                                            type="button"
                                            disabled={busy}
                                            onClick={() => makePrimary(index)}
                                            aria-label="Definir como imagem principal"
                                            title="Definir como principal"
                                            className="inline-flex size-8 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-primary)] disabled:opacity-35"
                                        >
                                            <Star className="size-4" />
                                        </button>
                                    )}
                                </div>

                                <button
                                    type="button"
                                    disabled={busy}
                                    onClick={() => handleDelete(image)}
                                    aria-label="Excluir imagem"
                                    title="Excluir imagem"
                                    className="inline-flex size-8 items-center justify-center rounded-lg text-[var(--admin-text-muted)] transition-colors hover:bg-red-50 hover:text-red-600 disabled:opacity-35"
                                >
                                    <Trash2 className="size-4" />
                                </button>
                            </div>
                        </article>
                    ))}
                </div>
            ) : (
                <div className="flex min-h-44 flex-col items-center justify-center rounded-xl border border-dashed border-[var(--admin-border)] bg-[var(--admin-background-soft)] px-6 text-center">
                    <ImagePlus
                        className="mb-3 size-8 text-[var(--admin-text-muted)]"
                        aria-hidden="true"
                    />

                    <p className="text-sm font-medium text-[var(--admin-text)]">
                        Nenhuma imagem cadastrada
                    </p>

                    <p className="mt-1 max-w-sm text-xs leading-relaxed text-[var(--admin-text-muted)]">
                        Selecione uma ou mais fotos para montar a galeria deste
                        produto na loja.
                    </p>
                </div>
            )}
        </section>
    );
}
