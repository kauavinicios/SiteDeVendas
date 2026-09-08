import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, MailCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <AuthLayout
            title="Verifique seu e-mail"
            description="Enviamos um link de verificação para o seu e-mail. Clique nele para confirmar sua conta."
        >
            <Head title="Verificação de e-mail" />

            <div className="mb-6 flex justify-center">
                <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-[var(--admin-background-soft)] text-[var(--admin-primary)]">
                    <MailCheck
                        className="h-7 w-7"
                        aria-hidden="true"
                    />
                </div>
            </div>

            {status === 'verification-link-sent' && (
                <div className="auth-status mb-5 rounded-xl px-4 py-3 text-center text-sm">
                    Um novo link de verificação foi enviado para o seu e-mail.
                </div>
            )}

            <form
                onSubmit={submit}
                className="flex flex-col gap-4"
            >
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
                        disabled:cursor-not-allowed
                        disabled:opacity-60
                    "
                >
                    {processing && (
                        <LoaderCircle className="h-4 w-4 animate-spin" />
                    )}

                    {processing
                        ? 'Enviando...'
                        : 'Reenviar e-mail de verificação'}
                </Button>

                <TextLink
                    href={route('logout')}
                    method="post"
                    className="
                        mx-auto
                        block
                        text-sm
                        text-[var(--admin-text-muted)]
                        no-underline
                        hover:text-[var(--admin-primary)]
                        hover:underline
                    "
                >
                    Sair da conta
                </TextLink>
            </form>
        </AuthLayout>
    );
}