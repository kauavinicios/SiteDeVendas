import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BarChart3, Boxes, LayoutDashboard, Mail, Package, ShieldCheck, ShoppingBag, Tags, Users } from 'lucide-react';

import AppLogo from './app-logo';

interface ProtectedNavItem extends NavItem {
    permission: string;
}

const mainNavItems: ProtectedNavItem[] = [
    {
        title: 'Dashboard',
        url: '/admin/dashboard',
        icon: LayoutDashboard,
        permission: 'access-admin',
    },
    {
        title: 'Pedidos',
        url: '/admin/pedidos',
        icon: ShoppingBag,
        permission: 'view-orders',
    },
    {
        title: 'Relatórios',
        url: '/admin/relatorios',
        icon: BarChart3,
        permission: 'view-financial-reports',
    },
    {
        title: 'Produtos',
        url: '/admin/produtos',
        icon: Package,
        permission: 'view-products',
    },
    {
        title: 'Categorias',
        url: '/admin/categorias',
        icon: Tags,
        permission: 'view-categories',
    },
    {
        title: 'Estoque',
        url: '/admin/estoque',
        icon: Boxes,
        permission: 'view-stock',
    },
    {
        title: 'Clientes',
        url: '/admin/clientes',
        icon: Users,
        permission: 'view-customers',
    },
    {
        title: 'Novidades',
        url: '/admin/novidades',
        icon: Mail,
        permission: 'manage-campaigns',
    },
    {
        title: 'Usuários',
        url: '/admin/usuarios',
        icon: ShieldCheck,
        permission: 'manage-users',
    },
];

export function AppSidebar() {
    const { can, roleLabel } = usePermissions();

    const visibleItems: NavItem[] = mainNavItems.filter((item) => can(item.permission)).map(({ title, url, icon }) => ({ title, url, icon }));

    return (
        <Sidebar collapsible="icon" variant="inset" className="border-r border-[var(--admin-border)]">
            <SidebarHeader className="px-2 py-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="rounded-xl hover:bg-[var(--admin-background-soft)]">
                            <Link href="/admin/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="px-2">
                <NavMain items={visibleItems} />
            </SidebarContent>

            <SidebarFooter className="gap-2 px-2 pb-3">
                {roleLabel && (
                    <div className="px-2 group-data-[collapsible=icon]:hidden">
                        <span className="inline-flex w-full items-center justify-center rounded-lg border border-[var(--admin-border)] bg-[var(--admin-background-soft)] px-2.5 py-1 text-[11px] font-medium text-[var(--admin-text-muted)]">
                            Cargo: {roleLabel}
                        </span>
                    </div>
                )}

                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
