import { type BreadcrumbItem, type SharedData } from '@/types';

import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Perfil',
        href: '/settings/profile',
    },
];

export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
    const { auth } = usePage<SharedData>().props;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        name: auth.user?.name ?? '',
        email: auth.user?.email ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Perfil" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Informações do perfil" description="Atualize seu nome e endereço de e-mail." />

                    <form
                        onSubmit={submit}
                        className="space-y-6 rounded-2xl border border-[var(--admin-border)] bg-[var(--admin-surface)] p-6 shadow-sm"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="name" className="text-sm font-medium text-[var(--admin-text-soft)]">
                                Nome
                            </Label>

                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoComplete="name"
                                placeholder="Seu nome completo"
                                className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)] text-[var(--admin-text)] placeholder:text-[var(--admin-text-muted)] focus-visible:border-[var(--admin-primary)] focus-visible:ring-[var(--admin-primary)]/15"
                            />

                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email" className="text-sm font-medium text-[var(--admin-text-soft)]">
                                E-mail
                            </Label>

                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                autoComplete="username"
                                placeholder="seu@email.com"
                                className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)] text-[var(--admin-text)] placeholder:text-[var(--admin-text-muted)] focus-visible:border-[var(--admin-primary)] focus-visible:ring-[var(--admin-primary)]/15"
                            />

                            <InputError message={errors.email} />
                        </div>

                        {mustVerifyEmail && auth.user?.email_verified_at === null && (
                            <div className="rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background-soft)] px-4 py-3">
                                <p className="text-sm leading-relaxed text-[var(--admin-text-muted)]">
                                    Seu endereço de e-mail ainda não foi verificado.{' '}
                                    <Link
                                        href={route('verification.send')}
                                        method="post"
                                        as="button"
                                        className="font-medium text-[var(--admin-primary)] underline underline-offset-2 hover:text-[var(--admin-primary-dark)]"
                                    >
                                        Reenviar e-mail de verificação
                                    </Link>
                                </p>

                                {status === 'verification-link-sent' && (
                                    <div className="auth-status mt-3 rounded-lg px-3 py-2 text-sm">
                                        Um novo link de verificação foi enviado para o seu e-mail.
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="flex items-center gap-4">
                            <Button
                                type="submit"
                                disabled={processing}
                                className="auth-primary-button h-10 rounded-xl px-5 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {processing ? 'Salvando...' : 'Salvar alterações'}
                            </Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm font-medium text-[var(--admin-success)]">Alterações salvas.</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
