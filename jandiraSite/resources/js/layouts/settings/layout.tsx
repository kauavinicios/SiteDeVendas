import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Perfil',
        url: '/settings/profile',
        icon: null,
    },
    {
        title: 'Senha',
        url: '/settings/password',
        icon: null,
    },
    {
        title: 'Aparência',
        url: '/settings/appearance',
        icon: null,
    },
];

export default function SettingsLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const currentPath = window.location.pathname;

    return (
        <div className="px-4 py-6 md:px-6 lg:px-8">
            <div className="mb-8">
                <Heading
                    title="Configurações"
                    description="Gerencie seus dados de acesso e preferências do painel."
                />
            </div>

            <div className="flex flex-col gap-8 lg:flex-row lg:gap-12">
                <aside className="w-full lg:w-56">
                    <nav className="flex flex-col gap-1">
                        {sidebarNavItems.map((item) => {
                            const isActive = currentPath === item.url;

                            return (
                                <Button
                                    key={item.url}
                                    size="sm"
                                    variant="ghost"
                                    asChild
                                    className={cn(
                                        `
                                            h-10
                                            w-full
                                            justify-start
                                            rounded-xl
                                            px-3
                                            text-sm
                                            font-medium
                                            text-[var(--admin-text-muted)]
                                            transition-colors
                                            hover:bg-[var(--admin-background-soft)]
                                            hover:text-[var(--admin-primary)]
                                        `,
                                        isActive &&
                                            `
                                                bg-[var(--admin-background-soft)]
                                                text-[var(--admin-primary)]
                                            `,
                                    )}
                                >
                                    <Link href={item.url} prefetch>
                                        {item.title}
                                    </Link>
                                </Button>
                            );
                        })}
                    </nav>
                </aside>

                <Separator className="md:hidden" />

                <div className="min-w-0 flex-1">
                    <section className="max-w-2xl space-y-10">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}