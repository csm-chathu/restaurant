<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user  = Auth::user()->load('branch:id,name,code');
        if (!$user->is_active) {
            Auth::logout();
            return response()->json(['message' => 'Your account is inactive. Contact an administrator.'], 403);
        }
        $token = $user->createToken('spa-token')->plainTextToken;

        $user->allowed_features = $user->isSuperAdmin()
            ? \App\Models\RoleFeature::ALL_FEATURES
            : \App\Models\RoleFeature::featuresForRole($user->role);

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => "required|string|max:255|unique:users,email,{$user->id}",
            'password' => 'nullable|string|min:3',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        $user->allowed_features = $user->isSuperAdmin()
            ? \App\Models\RoleFeature::ALL_FEATURES
            : \App\Models\RoleFeature::featuresForRole($user->role);

        return response()->json($user->load('branch:id,name,code'));
    }
}
