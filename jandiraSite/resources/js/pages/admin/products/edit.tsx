import ProductForm, { type Category, type ProductFormData } from '@/components/product-form';
import AppLayout from '@/layouts/app-layout';

import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { FormEventHandler } from 'react';

import ProductImages, { type ProductImageItem } from '@/components/product-images';
import ProductVariants, { type ProductVariant } from '@/components/product-variants';

type Product = {
    id: number;
    name: string;
    category_id: number;
    description: string | null;
    active: boolean;
    featured: boolean;
    images: ProductImageItem[];
    variants: ProductVariant[];
};

type Props = {
    product: Product;
    categories: Category[];
};

type PageProps = {
    flash?: {
        success?: string;
        error?: string;
    };
};

export default function ProductsEdit({ product, categories }: Props) {
    const { flash } = usePage<PageProps>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Produtos',
            href: '/admin/produtos',
        },
        {
            title: product.name,
            href: `/admin/produtos/${product.id}/edit`,
        },
    ];

    const { data, setData, put, processing, errors } = useForm<ProductFormData>({
        name: product.name,
        category_id: String(product.category_id),
        description: product.description ?? '',
        active: product.active,
        featured: product.featured,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('admin.products.update', product.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${product.name}`} />

            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <section>
                    <Link
                        href="/admin/produtos"
                        className="mb-3 inline-flex items-center gap-2 text-sm font-medium text-[var(--admin-text-muted)] transition-colors hover:text-[var(--admin-primary)]"
                    >
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Voltar para produtos
                    </Link>

                    <h1 className="text-2xl font-semibold text-[var(--admin-text)]">Editar produto</h1>

                    <p className="mt-1 text-sm text-[var(--admin-text-muted)]">
                        Atualize as informações principais de <span className="font-medium text-[var(--admin-text)]">{product.name}</span>.
                    </p>
                </section>

                {flash?.success && (
                    <div className="rounded-xl border border-[var(--admin-success-border)] bg-[var(--admin-success-background)] px-4 py-3 text-sm font-medium text-[var(--admin-success)]">
                        {flash.success}
                    </div>
                )}

                {flash?.error && (
                    <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{flash.error}</div>
                )}

                <ProductForm
                    data={data}
                    categories={categories}
                    errors={errors}
                    processing={processing}
                    submitLabel="Salvar alterações"
                    onChange={setData}
                    onSubmit={submit}
                />
                <ProductImages productId={product.id} images={product.images} />
                <ProductVariants productId={product.id} variants={product.variants} />
            </main>
        </AppLayout>
    );
}
