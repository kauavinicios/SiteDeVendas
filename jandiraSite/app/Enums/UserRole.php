<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Storekeeper = 'storekeeper';
    case Support = 'support';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Manager => 'Gerente',
            self::Storekeeper => 'Lojista',
            self::Support => 'Suporte',
            self::Customer => 'Cliente',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }

    /**
     * @return list<self>
     */
    public static function staffRoles(): array
    {
        return [
            self::Admin,
            self::Manager,
            self::Storekeeper,
            self::Support,
        ];
    }

    /**
     * @return list<string>
     */
    public static function staffValues(): array
    {
        return array_map(
            fn (self $role): string => $role->value,
            self::staffRoles(),
        );
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => Permission::cases(),
            self::Manager => [
                Permission::AccessAdmin,
                Permission::ViewProducts,
                Permission::ManageProducts,
                Permission::ViewCategories,
                Permission::ManageCategories,
                Permission::DeleteCategories,
                Permission::ViewStock,
                Permission::ManageStock,
                Permission::ViewCustomers,
                Permission::ViewMessages,
                Permission::ManageCampaigns,
                Permission::ViewOrders,
                Permission::ManageOrders,
                Permission::ViewFinancialReports,
            ],
            self::Storekeeper => [
                Permission::AccessAdmin,
                Permission::ViewProducts,
                Permission::ManageProducts,
                Permission::ViewCategories,
                Permission::ViewStock,
                Permission::ManageStock,
                Permission::ViewOrders,
                Permission::ManageOrders,
            ],
            self::Support => [
                Permission::AccessAdmin,
                Permission::ViewProducts,
                Permission::ViewCategories,
                Permission::ViewStock,
                Permission::ViewCustomers,
                Permission::ViewMessages,
                Permission::ViewOrders,
            ],
            self::Customer => [],
        };
    }
}
