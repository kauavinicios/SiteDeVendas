import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, LockKeyhole, Mail, Phone, User } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

interface RegisterForm {
    name: string;
    email: string;
    phone: string;
    password: string;
    password_confirmation: string;
    marketing_opt_in: boolean;
    [key: string]: string | boolean;
}

export default function Register() {
    const { data, setData, post, processing, errors, reset } =
        useForm<RegisterForm>({
            name: '',
            email: '',
            phone: '',
            password: '',
            password_confirmation: '',
            marketing_opt_in: false,
        });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            title="Criar conta"
            description="Cadastre-se para acompanhar sua conta e receber novidades da loja."
        >
            <Head title="Criar conta" />

            <form onSubmit={submit} className="flex flex-col gap-4">
                <div className="flex flex-col gap-2">
                    <Label
                        htmlFor="name"
                        className="text-sm font-medium text-[var(--admin-text-soft)]"
                    >
                        Nome completo
                    </Label>

                    <div className="relative">
                        <User
                            className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-[var(--admin-text-muted)]"
                            aria-hidden="true"
                        />

                        <Input
                            id="name"
                            type="text"
                            required
                            autoFocus
                            autoComplete="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Seu nome completo"
                            className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)] pl-10 text-[var(--admin-text)] placeholder:text-[var(--admin-text-muted)] focus-visible:border-[var(--admin-primary)] focus-visible:ring-[var(--admin-primary)]/15"
                        />
                    </div>

                    <InputError message={errors.name} />
                </div>

                <div className="flex flex-col gap-2">
                    <Label
                        htmlFor="email"
                        className="text-sm font-medium text-[var(--admin-text-soft)]"
                    >
                        E-mail
                    </Label>

                    <div className="relative">
                        <Mail
                            className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-[var(--admin-text-muted)]"
                            aria-hidden="true"
                        />

                        <Input
                            id="email"
                            type="email"
                            required
                            autoComplete="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="seu@email.com"
                            className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)] pl-10 text-[var(--admin-text)] placeholder:text-[var(--admin-text-muted)] focus-visible:border-[var(--admin-primary)] focus-visible:ring-[var(--admin-primary)]/15"
                        />
                    </div>

                    <InputError message={errors.email} />
                </div>

                <div className="flex flex-col gap-2">
                    <Label
                        htmlFor="phone"
                        className="text-sm font-medium text-[var(--admin-text-soft)]"
                    >
                        Telefone / WhatsApp (opcional)
                    </Label>

                    <div className="relative">
                        <Phone
                            className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-[var(--admin-text-muted)]"
                            aria-hidden="true"
                        />

                        <Input
                            id="phone"
                            type="text"
                            autoComplete="tel"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            placeholder="(67) 99999-9999"
                            className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)] pl-10 text-[var(--admin-text)] placeholder:text-[var(--admin-text-muted)] focus-visible:border-[var(--admin-primary)] focus-visible:ring-[var(--admin-primary)]/15"
                        />
                    </div>

                    <InputError message={errors.phone} />
                </div>

                <div className="flex flex-col gap-2">
                    <Label
                        htmlFor="password"
                        className="text-sm font-medium text-[var(--admin-text-soft)]"
                    >
                        Senha
                    </Label>

                    <div className="relative">
                        <LockKeyhole
                            className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-[var(--admin-text-muted)]"
                            aria-hidden="true"
                        />

                        <Input
                            id="password"
                            type="password"
                            required
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            placeholder="Mínimo 8 caracteres"
                            className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)] pl-10 text-[var(--admin-text)] placeholder:text-[var(--admin-text-muted)] focus-visible:border-[var(--admin-primary)] focus-visible:ring-[var(--admin-primary)]/15"
                        />
                    </div>

                    <InputError message={errors.password} />
                </div>

                <div className="flex flex-col gap-2">
                    <Label
                        htmlFor="password_confirmation"
                        className="text-sm font-medium text-[var(--admin-text-soft)]"
                    >
                        Confirmar senha
                    </Label>

                    <div className="relative">
                        <LockKeyhole
                            className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-[var(--admin-text-muted)]"
                            aria-hidden="true"
                        />

                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData('password_confirmation', e.target.value)
                            }
                            placeholder="Repita sua senha"
                            className="h-11 rounded-xl border-[var(--admin-border)] bg-[var(--admin-background)] pl-10 text-[var(--admin-text)] placeholder:text-[var(--admin-text-muted)] focus-visible:border-[var(--admin-primary)] focus-visible:ring-[var(--admin-primary)]/15"
                        />
                    </div>

                    <InputError message={errors.password_confirmation} />
                </div>

                <label
                    htmlFor="marketing_opt_in"
                    className="mt-1 flex cursor-pointer items-start gap-3 rounded-xl border border-[var(--admin-border)] bg-[var(--admin-background-soft)] p-3.5"
                >
                    <Checkbox
                        id="marketing_opt_in"
                        name="marketing_opt_in"
                        checked={data.marketing_opt_in}
                        onCheckedChange={(checked) =>
                            setData('marketing_opt_in', checked === true)
                        }
                        className="mt-0.5 border-[var(--admin-border-hover)] data-[state=checked]:border-[var(--admin-primary)] data-[state=checked]:bg-[var(--admin-primary)]"
                    />

                    <span className="text-xs leading-relaxed text-[var(--admin-text-muted)]">
                        Quero receber novidades, lançamentos e promoções da{' '}
                        <strong className="font-medium text-[var(--admin-text)]">
                            Butique da Jandira
                        </strong>{' '}
                        por e-mail. Você pode cancelar quando quiser na sua
                        conta.
                    </span>
                </label>

                <Button
                    type="submit"
                    disabled={processing}
                    className="auth-primary-button mt-1 h-11 w-full rounded-xl font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {processing && (
                        <LoaderCircle className="h-4 w-4 animate-spin" />
                    )}

                    {processing ? 'Criando conta...' : 'Criar conta'}
                </Button>

                <p className="mt-2 text-center text-sm text-[var(--admin-text-muted)]">
                    Já tem uma conta?{' '}
                    <TextLink
                        href={route('login')}
                        className="font-medium text-[var(--admin-primary)] no-underline hover:text-[var(--admin-primary-dark)] hover:underline"
                    >
                        Entrar
                    </TextLink>
                </p>
            </form>
        </AuthLayout>
    );
}
