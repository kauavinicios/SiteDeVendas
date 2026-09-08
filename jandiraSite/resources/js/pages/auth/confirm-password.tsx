import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, LockKeyhole } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

export default function ConfirmPassword() {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
    } = useForm({
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout
            title="Confirme sua senha"
            description="Por segurança, confirme sua senha antes de continuar."
        >
            <Head title="Confirmar senha" />

            <form
                onSubmit={submit}
                className="flex flex-col gap-5"
            >
                <div className="flex flex-col gap-2">
                    <Label
                        htmlFor="password"
                        className="text-sm font-medium text-[var(--admin-text-soft)]"
                    >
                        Senha
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
                            placeholder="Digite sua senha"
                            autoComplete="current-password"
                            value={data.password}
                            autoFocus
                            required
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
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
                        ? 'Confirmando...'
                        : 'Confirmar senha'}
                </Button>
            </form>
        </AuthLayout>
    );
}