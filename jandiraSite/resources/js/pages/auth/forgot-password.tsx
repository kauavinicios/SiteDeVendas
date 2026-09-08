import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, Mail } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <AuthLayout
            title="Recuperar senha"
            description="Informe seu e-mail para receber o link de redefinição de senha."
        >
            <Head title="Recuperar senha" />

            {status && (
                <div className="auth-status mb-5 rounded-xl px-4 py-3 text-sm">
                    {status}
                </div>
            )}

            <form
                onSubmit={submit}
                className="flex flex-col gap-5"
            >
                <div className="flex flex-col gap-2">
                    <Label
                        htmlFor="email"
                        className="text-sm font-medium text-[var(--admin-text-soft)]"
                    >
                        E-mail
                    </Label>

                    <div className="relative">
                        <Mail
                            className="
                                pointer-events-none
                                absolute
                                left-3
                                top-1/2
                                h-4
                                w-4
                                -translate-y-1/2
                                text-[var(--admin-text-muted)]
                            "
                            aria-hidden="true"
                        />

                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autoComplete="email"
                            value={data.email}
                            autoFocus
                            required
                            onChange={(e) =>
                                setData('email', e.target.value)
                            }
                            placeholder="seu@email.com"
                            className="
                                h-11
                                rounded-xl
                                border-[var(--admin-border)]
                                bg-[var(--admin-background)]
                                pl-10
                                text-[var(--admin-text)]
                                placeholder:text-[var(--admin-text-muted)]
                                focus-visible:border-[var(--admin-primary)]
                                focus-visible:ring-[var(--admin-primary)]/15
                            "
                        />
                    </div>

                    <InputError message={errors.email} />
                </div>

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
                        : 'Enviar link de recuperação'}
                </Button>
            </form>

            <div className="mt-6 text-center text-sm text-[var(--admin-text-muted)]">
                <span>Lembrou sua senha? </span>

                <TextLink
                    href={route('login')}
                    className="
                        text-[var(--admin-primary)]
                        no-underline
                        hover:text-[var(--admin-primary-dark)]
                        hover:underline
                    "
                >
                    Voltar para o login
                </TextLink>
            </div>
        </AuthLayout>
    );
}