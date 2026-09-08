import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';

import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import {
    Boxes,
    LayoutDashboard,
    Package,
    Tags,
} from 'lucide-react';

import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: '/admin/dashboard',
        icon: LayoutDashboard,
    },
    {
        title: 'Produtos',
        url: '/admin/produtos',
        icon: Package,
    },
    {
        title: 'Categorias',
        url: '/admin/categorias',
        icon: Tags,
    },
    {
        title: 'Estoque',
        url: '/admin/estoque',
        icon: Boxes,
    },
];

export function AppSidebar() {
    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className="border-r border-[var(--admin-border)]"
        >
            <SidebarHeader className="px-2 py-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className="
                                rounded-xl
                                hover:bg-[var(--admin-background-soft)]
                            "
                        >
                            <Link href="/admin/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="px-2">
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter className="px-2 pb-3">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}