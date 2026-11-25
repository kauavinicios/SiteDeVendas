import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export function usePermissions() {
    const { auth } = usePage<SharedData>().props;
    const permissions = auth?.permissions ?? [];
    const role = auth?.user?.role;
    const roleLabel = auth?.user?.role_label;

    const can = (permission: string): boolean => permissions.includes(permission);

    return {
        permissions,
        role,
        roleLabel,
        can,
    };
}
