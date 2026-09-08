import { SidebarInset } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import * as React from 'react';

interface AppContentProps extends React.ComponentProps<'div'> {
    children: React.ReactNode;
}

export function AppContent({
    children,
    className,
    ...props
}: AppContentProps) {
    return (
        <SidebarInset
            className={cn(
                `
                    min-w-0
                    bg-[var(--admin-background-soft)]
                    text-[var(--admin-text)]
                `,
                className,
            )}
            {...props}
        >
            {children}
        </SidebarInset>
    );
}