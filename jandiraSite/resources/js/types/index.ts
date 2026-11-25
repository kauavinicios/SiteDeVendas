import { LucideIcon } from 'lucide-react';

export type UserRole =
    | 'admin'
    | 'manager'
    | 'storekeeper'
    | 'support'
    | 'customer';

export interface Auth {
    user: User | null;
    permissions?: string[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    phone?: string | null;
    role?: UserRole;
    role_label?: string;
    is_staff?: boolean;
    active?: boolean;
    marketing_opt_in?: boolean;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
