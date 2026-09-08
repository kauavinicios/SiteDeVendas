import { Appearance, useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { LucideIcon, Monitor, Moon, Sun } from 'lucide-react';
import { HTMLAttributes } from 'react';

export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();

    const tabs: {
        value: Appearance;
        icon: LucideIcon;
        label: string;
    }[] = [
        {
            value: 'light',
            icon: Sun,
            label: 'Claro',
        },
        {
            value: 'dark',
            icon: Moon,
            label: 'Escuro',
        },
        {
            value: 'system',
            icon: Monitor,
            label: 'Sistema',
        },
    ];

    return (
        <div
            className={cn(
                `
                    inline-flex
                    flex-wrap
                    gap-1
                    rounded-xl
                    border
                    border-[var(--admin-border)]
                    bg-[var(--admin-background-soft)]
                    p-1
                `,
                className,
            )}
            {...props}
        >
            {tabs.map(({ value, icon: Icon, label }) => {
                const isActive = appearance === value;

                return (
                    <button
                        key={value}
                        type="button"
                        onClick={() => updateAppearance(value)}
                        className={cn(
                            `
                                flex
                                items-center
                                gap-2
                                rounded-lg
                                px-3.5
                                py-2
                                text-sm
                                font-medium
                                transition-all
                            `,
                            isActive
                                ? `
                                    bg-[var(--admin-surface)]
                                    text-[var(--admin-primary)]
                                    shadow-sm
                                `
                                : `
                                    text-[var(--admin-text-muted)]
                                    hover:bg-[var(--admin-surface)]
                                    hover:text-[var(--admin-text)]
                                `,
                        )}
                    >
                        <Icon
                            className="h-4 w-4"
                            aria-hidden="true"
                        />

                        <span>
                            {label}
                        </span>
                    </button>
                );
            })}
        </div>
    );
}