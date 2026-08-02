<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleFeature extends Model
{
    protected $fillable = ['role', 'features'];

    protected $casts = ['features' => 'array'];

    // All feature keys in the system
    public const ALL_FEATURES = [
        'dashboard',
        'pos_billing',
        'products',
        'menu_categories',
        'guests',
        'tables',
        'suppliers',
        'open_bottles',
        'my_shift',
        'reports',
        'purchases',
        'price_matrix',
        'opening_balance',
        'grn',
        'supplier_returns',
        'bottle_deposits',
        'finance',
        'shift_summary',
        'damages',
        'stock_ledger',
        'users_roles',
        'settings',
    ];

    // Default features per role (mirrors current hardcoded sidebar behaviour)
    public const DEFAULTS = [
        'admin' => self::ALL_FEATURES,
        'owner' => self::ALL_FEATURES,
        'manager' => [
            'dashboard', 'pos_billing', 'products', 'menu_categories',
            'guests', 'tables', 'suppliers', 'open_bottles', 'reports',
            'purchases', 'shift_summary', 'damages',
        ],
        'cashier' => [
            'dashboard', 'pos_billing', 'open_bottles', 'my_shift',
        ],
        'store_keeper' => [
            'dashboard', 'products', 'purchases', 'stock_ledger',
        ],
    ];

    public static function featuresForRole(string $role): array
    {
        $record = static::where('role', $role)->first();
        return $record ? $record->features : (static::DEFAULTS[$role] ?? []);
    }
}
