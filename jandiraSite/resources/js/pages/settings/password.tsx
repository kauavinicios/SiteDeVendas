import InputError from '@/components/input-error';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

import { type BreadcrumbItem } from '@/types';

import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Senha',
        href: '/settings/password',
    },
];

export default function Password() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        errors,
        put,
        reset,
        processing,
        recentlySuccessful,
    } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,

            onSuccess: () => reset(),

            onError: (errors) => {
                if (errors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Alterar senha" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Alterar senha"
                        description="Mantenha sua conta protegida utilizando uma senha segura."
                    />

                    <form
                        onSubmit={updatePassword}
                        className="
                            space-y-6
                            rounded-2xl
                            border
                            border-[var(--admin-border)]
                            bg-[var(--admin-surface)]
                            p-6
                            shadow-sm
                        "
                    >
                        <div className="grid gap-2">
                            <Label
                                htmlFor="current_password"
                                className="text-sm font-medium text-[var(--admin-text-soft)]"
                            >
                                Senha atual
                            </Label>

                            <Input
                                id="current_password"
                                ref={currentPasswordInput}
                                value={data.current_password}
                                onChange={(e) =>
                                    setData(
                                        'current_password',
                                        e.target.value,
                                    )
                                }
                                type="password"
                                autoComplete="current-password"
                                placeholder="Digite sua senha atual"
                                className="
                                    h-11
                                    rounded-xl
                                    border-[var(--admin-border)]
                                    bg-[var(--admin-background)]
                                    text-[var(--admin-text)]
                                    placeholder:text-[var(--admin-text-muted)]
                                    focus-visible:border-[var(--admin-primary)]
                                    focus-visible:ring-[var(--admin-primary)]/15
                                "
                            />

                            <InputError
                                message={errors.current_password}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor="password"
                                className="text-sm font-medium text-[var(--admin-text-soft)]"
                            >
                                Nova senha
                            </Label>

                            <Input
                                id="password"
                                ref={passwordInput}
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                type="password"
                                autoComplete="new-password"
                                placeholder="Digite sua nova senha"
                                className="
                                    h-11
                                    rounded-xl
                                    border-[var(--admin-border)]
                                    bg-[var(--admin-background)]
                                    text-[var(--admin-text)]
                                    placeholder:text-[var(--admin-text-muted)]
                                    focus-visible:border-[var(--admin-primary)]
                                    focus-visible:ring-[var(--admin-primary)]/15
                                "
                            />

                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor="password_confirmation"
                                className="text-sm font-medium text-[var(--admin-text-soft)]"
                            >
                                Confirmar nova senha
                            </Label>

                            <Input
                                id="password_confirmation"
                                value={data.password_confirmation}
                                onChange={(e) =>
                                    setData(
                                        'password_confirmation',
                                        e.target.value,
                                    )
                                }
                                type="password"
                                autoComplete="new-password"
                                placeholder="Repita sua nova senha"
                                className="
                                    h-11
                                    rounded-xl
                                    border-[var(--admin-border)]
                                    bg-[var(--admin-background)]
                                    text-[var(--admin-text)]
                                    placeholder:text-[var(--admin-text-muted)]
                                    focus-visible:border-[var(--admin-primary)]
                                    focus-visible:ring-[var(--admin-primary)]/15
                                "
                            />

                            <InputError
                                message={
                                    errors.password_confirmation
                                }
                            />
                        </div>

                        <div className="flex items-center gap-4">
                            <Button
                                type="submit"
                                disabled={processing}
                                className="
                                    auth-primary-button
                                    h-10
                                    rounded-xl
                                    px-5
                                    font-semibold
                                    text-white
                                    disabled:cursor-not-allowed
                                    disabled:opacity-60
                                "
                            >
                                {processing
                                    ? 'Salvando...'
                                    : 'Salvar nova senha'}
                            </Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm font-medium text-[var(--admin-success)]">
                                    Senha atualizada.
                                </p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}