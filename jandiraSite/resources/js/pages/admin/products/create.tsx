import AppLayout from '@/layouts/app-layout';
import ProductForm, {
    type Category,
    type ProductFormData,
} from '@/components/product-form';

import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { FormEventHandler } from 'react';

type Props = {
    categories: Category[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Produtos',
        href: '/admin/produtos',
    },
    {
        title: 'Novo produto',
        href: '/admin/produtos/create',
    },
];

export default function ProductsCreate({
    categories,
}: Props) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
    } = useForm<ProductFormData>({
        name: '',
        category_id: '',
        description: '',
        active: true,
        featured: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('admin.products.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Novo produto" />

            <main className="flex flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8">
                <section>
                    <Link
                        href="/admin/produtos"
                        className="
                            mb-3
                            inline-flex
                            items-center
                            gap-2
                            text-sm
                            font-medium
                            text-[var(--admin-text-muted)]
                            transition-colors
                            hover:text-[var(--admin-primary)]
                        "
                    >
                        <ArrowLeft
                            className="size-4"
                            aria-hidden="true"
                        />

                        Voltar para produtos
                    </Link>

                    <h1 className="text-2xl font-semibold text-[var(--admin-text)]">
                        Novo produto
                    </h1>

                    <p className="mt-1 text-sm text-[var(--admin-text-muted)]">
                        Cadastre as informações principais do produto.
                    </p>
                </section>

                <ProductForm
                    data={data}
                    categories={categories}
                    errors={errors}
                    processing={processing}
                    submitLabel="Salvar produto"
                    onChange={setData}
                    onSubmit={submit}
                />
            </main>
        </AppLayout>
    );
}