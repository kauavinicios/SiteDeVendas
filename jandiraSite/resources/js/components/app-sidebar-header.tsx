import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header
            className="
                flex
                h-16
                shrink-0
                items-center
                border-b
                border-[var(--admin-border)]
                bg-[var(--admin-surface)]
                px-4
                transition-[width,height]
                ease-linear
                md:px-6
                group-has-data-[collapsible=icon]/sidebar-wrapper:h-14
            "
        >
            <div className="flex min-w-0 items-center gap-3">
                <SidebarTrigger
                    className="
                        -ml-1
                        rounded-lg
                        text-[var(--admin-text-muted)]
                        hover:bg-[var(--admin-background-soft)]
                        hover:text-[var(--admin-primary)]
                    "
                />

                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
        </header>
    );
}