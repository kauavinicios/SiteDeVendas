import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';

import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { useIsMobile } from '@/hooks/use-mobile';
import { type SharedData } from '@/types';

import { usePage } from '@inertiajs/react';
import { ChevronsUpDown } from 'lucide-react';

export function NavUser() {
    const { auth } = usePage<SharedData>().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();

    if (!auth.user) {
        return null;
    }

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="group rounded-xl text-[var(--admin-text)] transition-colors hover:bg-[var(--admin-background-soft)] data-[state=open]:bg-[var(--admin-background-soft)]"
                        >
                            <UserInfo user={auth.user} />

                            <ChevronsUpDown
                                className="ml-auto size-4 text-[var(--admin-text-muted)] transition-colors group-hover:text-[var(--admin-primary)]"
                                aria-hidden="true"
                            />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-xl border-[var(--admin-border)] bg-[var(--admin-surface)] text-[var(--admin-text)] shadow-lg"
                        align="end"
                        side={isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom'}
                    >
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
