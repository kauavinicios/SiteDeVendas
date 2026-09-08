import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

export default function DeleteUser() {
    const passwordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        delete: destroy,
        processing,
        reset,
        errors,
        clearErrors,
    } = useForm({
        password: '',
    });

    const deleteUser: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        clearErrors();
        reset();
    };

    return (
        <div className="space-y-6">
            <HeadingSmall
                title="Excluir conta"
                description="Remova permanentemente sua conta de acesso ao painel."
            />

            <div
                className="
                    space-y-4
                    rounded-2xl
                    border
                    border-red-200
                    bg-red-50/70
                    p-5
                "
            >
                <div className="space-y-1">
                    <p className="font-semibold text-red-700">
                        Atenção
                    </p>

                    <p className="text-sm leading-relaxed text-red-600">
                        Esta ação é permanente e não poderá ser desfeita.
                        Confirme apenas se realmente deseja excluir sua conta.
                    </p>
                </div>

                <Dialog>
                    <DialogTrigger asChild>
                        <Button
                            type="button"
                            className="
                                rounded-xl
                                bg-red-600
                                font-semibold
                                text-white
                                hover:bg-red-700
                            "
                        >
                            Excluir conta
                        </Button>
                    </DialogTrigger>

                    <DialogContent
                        className="
                            border-[var(--admin-border)]
                            bg-[var(--admin-surface)]
                            text-[var(--admin-text)]
                        "
                    >
                        <DialogTitle className="text-[var(--admin-text)]">
                            Deseja realmente excluir sua conta?
                        </DialogTitle>

                        <DialogDescription className="text-[var(--admin-text-muted)]">
                            Ao excluir sua conta, seus dados de acesso serão
                            removidos permanentemente. Digite sua senha para
                            confirmar esta ação.
                        </DialogDescription>

                        <form
                            className="space-y-6"
                            onSubmit={deleteUser}
                        >
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="delete-account-password"
                                    className="text-sm font-medium text-[var(--admin-text-soft)]"
                                >
                                    Senha atual
                                </Label>

                                <Input
                                    id="delete-account-password"
                                    type="password"
                                    name="password"
                                    ref={passwordInput}
                                    value={data.password}
                                    onChange={(e) =>
                                        setData(
                                            'password',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Digite sua senha"
                                    autoComplete="current-password"
                                    autoFocus
                                    required
                                    className="
                                        h-11
                                        rounded-xl
                                        border-[var(--admin-border)]
                                        bg-[var(--admin-background)]
                                        text-[var(--admin-text)]
                                        placeholder:text-[var(--admin-text-muted)]
                                        focus-visible:border-red-500
                                        focus-visible:ring-red-500/15
                                    "
                                />

                                <InputError
                                    message={errors.password}
                                />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={closeModal}
                                        className="rounded-xl"
                                    >
                                        Cancelar
                                    </Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="
                                        rounded-xl
                                        bg-red-600
                                        font-semibold
                                        text-white
                                        hover:bg-red-700
                                        disabled:cursor-not-allowed
                                        disabled:opacity-60
                                    "
                                >
                                    {processing
                                        ? 'Excluindo...'
                                        : 'Excluir permanentemente'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
}