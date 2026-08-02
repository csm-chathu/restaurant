<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@store.local'],
            [
                'name'                   => 'Super Admin',
                'password'               => Hash::make('superadmin123'),
                'role'                   => 'admin',
                'branch_id'              => 1,
                'is_super_admin'         => true,
                'is_active'              => true,
                'can_override_gold_rate' => true,
                'can_delete_transactions'=> true,
            ]
        );
    }
}
