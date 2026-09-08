import AppLogoIcon from '@/components/app-logo-icon';
import { Link } from '@inertiajs/react';

interface AuthLayoutProps {
    children: React.ReactNode;
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <main className="auth-page flex min-h-screen items-center justify-center px-4 py-10">
            <section className="w-full max-w-md">
                <Link
                    href={route('home')}
                    className="auth-brand mx-auto mb-6 flex w-fit items-center gap-3"
                    aria-label="Voltar para a loja"
                >
                    <div className="auth-brand-icon flex h-11 w-11 items-center justify-center rounded-xl">
                        <AppLogoIcon />
                    </div>

                    <div className="auth-brand-text flex flex-col leading-tight">
                        <strong className="text-base font-semibold">
                            Butique da Jandira
                        </strong>

                        <span className="mt-1 text-xs">
                            Administração
                        </span>
                    </div>
                </Link>

                <div className="auth-card rounded-2xl p-6 md:p-8">
                    <header className="mb-7 text-center">
                        <h1 className="text-2xl font-semibold leading-tight text-[var(--admin-text)]">
                            {title}
                        </h1>

                        {description && (
                            <p className="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-[var(--admin-text-muted)]">
                                {description}
                            </p>
                        )}
                    </header>

                    <div className="w-full">
                        {children}
                    </div>
                </div>

                <Link
                    href={route('home')}
                    className="auth-link mx-auto mt-5 block w-fit text-sm"
                >
                    ← Voltar para a loja
                </Link>
            </section>
        </main>
    );
}