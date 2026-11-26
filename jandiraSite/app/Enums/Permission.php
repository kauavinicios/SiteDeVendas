<?php

namespace App\Enums;

enum Permission: string
{
    case AccessAdmin = 'access-admin';
    case ViewProducts = 'view-products';
    case ManageProducts = 'manage-products';
    case ViewCategories = 'view-categories';
    case ManageCategories = 'manage-categories';
    case DeleteCategories = 'delete-categories';
    case ViewStock = 'view-stock';
    case ManageStock = 'manage-stock';
    case ViewCustomers = 'view-customers';
    case ManageCampaigns = 'manage-campaigns';
    case ViewOrders = 'view-orders';
    case ManageOrders = 'manage-orders';
    case ViewFinancialReports = 'view-financial-reports';
    case ManageUsers = 'manage-users';
}
