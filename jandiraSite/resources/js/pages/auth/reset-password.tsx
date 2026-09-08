import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, LockKeyhole, Mail } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

interface ResetPasswordProps {
    token: string;
    email: string;
}

interface ResetPasswordForm {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
    [key: string]: string;
}

export default function ResetPassword({
    token,
    email,
}: ResetPasswordProps) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
    } = useForm<ResetPasswordForm>({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.store'), {
            onFinish: () =>
                reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            title="Redefinir senha"
            description="Crie uma nova senha para voltar a acessar sua conta."
        >
            <Head title="Redefinir senha" />

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
                            readOnly
                            onChange={(e) =>
                                setData('email', e.target.value)
                            }
                            className="
                                h-11
                                rounded-xl
                                border-[var(--admin-border)]
                                bg-[var(--admin-background-soft)]
                                pl-10
                                text-[var(--admin-text-muted)]
                                cursor-not-allowed
                            "
                        />
                    </div>

                    <InputError message={errors.email} />
                </div>

                <div className="flex flex-col gap-2">
                    <Label
                        htmlFor="password"
                        className="text-sm font-medium text-[var(--admin-text-soft)]"
                    >
                        Nova senha
                    </Label>

                    <div className="relative">
                        <LockKeyhole
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
                            id="password"
                            type="password"
                            name="password"
                            autoComplete="new-password"
                            value={data.password}
                            autoFocus
                            required
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            placeholder="Digite sua nova senha"
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

                    <InputError message={errors.password} />
                </div>

                <div className="flex flex-col gap-2">
                    <Label
                        htmlFor="password_confirmation"
                        className="text-sm font-medium text-[var(--admin-text-soft)]"
                    >
                        Confirmar nova senha
                    </Label>

                    <div className="relative">
                        <LockKeyhole
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
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            required
                            onChange={(e) =>
                                setData(
                                    'password_confirmation',
                                    e.target.value,
                                )
                            }
                            placeholder="Confirme sua nova senha"
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

                    <InputError
                        message={errors.password_confirmation}
                    />
                </div>

                <Button
                    type="submit"
                    disabled={processing}
                    className="
                        auth-primary-button
                        mt-1
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
                        ? 'Redefinindo...'
                        : 'Redefinir senha'}
                </Button>
            </form>
        </AuthLayout>
    );
}