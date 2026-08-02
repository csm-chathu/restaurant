<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoleFeature;
use Illuminate\Http\Request;

class RoleFeaturesController extends Controller
{
    private const MANAGEABLE_ROLES = ['admin', 'owner', 'manager', 'cashier', 'store_keeper'];

    public function index(Request $request)
    {
        if (!$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $saved = RoleFeature::all()->keyBy('role');

        $result = collect(self::MANAGEABLE_ROLES)->map(function ($role) use ($saved) {
            return [
                'role'     => $role,
                'features' => $saved->has($role)
                    ? $saved[$role]->features
                    : (RoleFeature::DEFAULTS[$role] ?? []),
            ];
        });

        return response()->json([
            'roles'        => $result,
            'all_features' => RoleFeature::ALL_FEATURES,
        ]);
    }

    public function update(Request $request, string $role)
    {
        if (!$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!in_array($role, self::MANAGEABLE_ROLES, true)) {
            return response()->json(['message' => 'Invalid role'], 422);
        }

        $features = collect($request->input('features', []))
            ->filter(fn($f) => in_array($f, RoleFeature::ALL_FEATURES, true))
            ->values()
            ->all();

        RoleFeature::updateOrCreate(
            ['role' => $role],
            ['features' => $features]
        );

        return response()->json(['role' => $role, 'features' => $features]);
    }
}
