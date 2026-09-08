import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';

import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { type User } from '@/types';

import { Link } from '@inertiajs/react';
import { LogOut, Settings } from 'lucide-react';

interface UserMenuContentProps {
    user: User;
}

export function UserMenuContent({
    user,
}: UserMenuContentProps) {
    const cleanup = useMobileNavigation();

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-2 py-2 text-left text-sm">
                    <UserInfo
                        user={user}
                        showEmail={true}
                    />
                </div>
            </DropdownMenuLabel>

            <DropdownMenuSeparator className="bg-[var(--admin-border)]" />

            <DropdownMenuGroup>
                <DropdownMenuItem
                    asChild
                    className="
                        cursor-pointer
                        rounded-lg
                        text-[var(--admin-text-muted)]
                        transition-colors
                        focus:bg-[var(--admin-background-soft)]
                        focus:text-[var(--admin-primary)]
                    "
                >
                    <Link
                        className="flex w-full items-center"
                        href={route('profile.edit')}
                        as="button"
                        prefetch
                        onClick={cleanup}
                    >
                        <Settings
                            className="mr-2 size-4"
                            aria-hidden="true"
                        />

                        Configurações
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>

            <DropdownMenuSeparator className="bg-[var(--admin-border)]" />

            <DropdownMenuItem
                asChild
                className="
                    cursor-pointer
                    rounded-lg
                    text-red-600
                    transition-colors
                    focus:bg-red-50
                    focus:text-red-700
                "
            >
                <Link
                    className="flex w-full items-center"
                    method="post"
                    href={route('logout')}
                    as="button"
                    onClick={cleanup}
                >
                    <LogOut
                        className="mr-2 size-4"
                        aria-hidden="true"
                    />

                    Sair
                </Link>
            </DropdownMenuItem>
        </>
    );
}