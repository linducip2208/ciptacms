<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends ApiController
{
    public function login(Request $r)
    {
        $d = $r->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::where('email', $d['email'])->first();

        if (! $user || ! Hash::check($d['password'], $user->password)) {
            // Same wording for both cases so the endpoint does not confirm
            // which email addresses exist.
            return $this->error('Invalid credentials.', 401);
        }

        if (! $user->isActive()) {
            return $this->error('Account is '.$user->status.'.', 403);
        }

        if ($user->two_factor_enabled) {
            return $this->error('This account requires two-factor authentication. Sign in through the web login.', 409);
        }

        $token = $user->createToken($d['device_name'] ?? 'api')->plainTextToken;

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $r->ip(),
        ])->save();

        return $this->data([
            'token' => $token,
            'user' => $this->present($user),
        ]);
    }

    /**
     * Self-registration through the API is off unless an operator enables
     * `api.allow_registration`. The web form has its own switch; this one
     * governs the JSON endpoint so the two can differ.
     */
    public function register(Request $r)
    {
        if (! setting('api.allow_registration', false)) {
            return $this->error('Registration is disabled.', 403);
        }

        $d = $r->validate([
            'name' => 'required|string|max:190',
            'email' => 'required|email|max:190|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'name' => $d['name'],
            'email' => $d['email'],
            'password' => Hash::make($d['password']),
            'status' => 'active',
            'is_active' => true,
        ]);

        try {
            $role = \App\Models\Role::where('slug', 'member')->first();
            if ($role) {
                $user->roles()->attach($role);
            }
        } catch (\Throwable $e) {
            // Roles are optional for a public registration endpoint.
        }

        return response()->json([
            'data' => [
                'token' => $user->createToken($d['device_name'] ?? 'api')->plainTextToken,
                'user' => $this->present($user),
            ],
        ], 201);
    }

    public function me(Request $r)
    {
        return $this->data($this->present($r->user()));
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()?->delete();

        return $this->data(['revoked' => true]);
    }

    protected function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'roles' => $user->roles()->pluck('slug'),
            'last_login_at' => optional($user->last_login_at)->toIso8601String(),
        ];
    }
}
