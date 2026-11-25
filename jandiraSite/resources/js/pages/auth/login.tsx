import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, LockKeyhole, Mail } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

interface LoginForm {
    email: string;
    password: string;
    remember: boolean;
    [key: string]: string | boolean;
}

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({
    status,
    canResetPassword,
}: LoginProps) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
    } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout
            title="Bem-vindo de volta"
            description="Entre com seus dados para acessar sua conta."
        >
            <Head title="Entrar" />

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
                            required
                            autoFocus
                            tabIndex={1}
                            autoComplete="email"
                            value={data.email}
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

                <div className="flex flex-col gap-2">
                    <div className="flex items-center justify-between gap-4">
                        <Label
                            htmlFor="password"
                            className="text-sm font-medium text-[var(--admin-text-soft)]"
                        >
                            Senha
                        </Label>

                        {canResetPassword && (
                            <TextLink
                                href={route('password.request')}
                                tabIndex={5}
                                className="
                                    text-xs
                                    font-medium
                                    text-[var(--admin-primary)]
                                    no-underline
                                    transition-colors
                                    hover:text-[var(--admin-primary-dark)]
                                    hover:underline
                                "
                            >
                                Esqueceu sua senha?
                            </TextLink>
                        )}
                    </div>

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
                            required
                            tabIndex={2}
                            autoComplete="current-password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            placeholder="Digite sua senha"
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

                <div className="flex items-center gap-3">
                    <Checkbox
                        id="remember"
                        name="remember"
                        tabIndex={3}
                        checked={data.remember}
                        onCheckedChange={(checked) =>
                            setData('remember', checked === true)
                        }
                        className="
                            border-[var(--admin-border-hover)]
                            data-[state=checked]:border-[var(--admin-primary)]
                            data-[state=checked]:bg-[var(--admin-primary)]
                        "
                    />

                    <Label
                        htmlFor="remember"
                        className="
                            cursor-pointer
                            text-sm
                            font-normal
                            text-[var(--admin-text-muted)]
                        "
                    >
                        Manter conectado
                    </Label>
                </div>

                <Button
                    type="submit"
                    tabIndex={4}
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

                    {processing ? 'Entrando...' : 'Entrar'}
                </Button>

                <p className="mt-2 text-center text-sm text-[var(--admin-text-muted)]">
                    Ainda não tem uma conta?{' '}
                    <TextLink
                        href={route('register')}
                        className="font-medium text-[var(--admin-primary)] no-underline hover:text-[var(--admin-primary-dark)] hover:underline"
                    >
                        Criar conta
                    </TextLink>
                </p>
            </form>
        </AuthLayout>
    );
}