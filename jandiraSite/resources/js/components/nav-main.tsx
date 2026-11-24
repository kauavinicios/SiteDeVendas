import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';

import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage();
    const currentPath = page.url.split('?')[0];

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel className="mb-2 px-2 text-xs font-semibold tracking-wide text-[var(--admin-text-muted)] uppercase">
                Administração
            </SidebarGroupLabel>

            <SidebarMenu className="gap-1">
                {items.map((item) => {
                    const isActive = currentPath === item.url || currentPath.startsWith(`${item.url}/`);

                    return (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={isActive}
                                className="h-10 rounded-xl px-3 text-[var(--admin-text-muted)] transition-colors hover:bg-[var(--admin-background-soft)] hover:text-[var(--admin-primary)] data-[active=true]:bg-[var(--admin-background-soft)] data-[active=true]:font-semibold data-[active=true]:text-[var(--admin-primary)]"
                            >
                                <Link href={item.url} prefetch>
                                    {item.icon && <item.icon className="size-4" aria-hidden="true" />}

                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
