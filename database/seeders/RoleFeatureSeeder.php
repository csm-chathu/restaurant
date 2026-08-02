<?php

namespace Database\Seeders;

use App\Models\RoleFeature;
use Illuminate\Database\Seeder;

class RoleFeatureSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleFeature::DEFAULTS as $role => $features) {
            RoleFeature::updateOrCreate(
                ['role' => $role],
                ['features' => $features]
            );
        }
    }
}
