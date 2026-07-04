<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Support\TenancyPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        $teams = $user->teams()->select('teams.id', 'teams.name')->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'teams' => $teams->map(fn (Team $team): array => $this->teamPayload($user, $team)),
            'token' => $token,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $teams = $user->teams()->select('teams.id', 'teams.name')->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'teams' => $teams->map(fn (Team $team): array => $this->teamPayload($user, $team)),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesion cerrada correctamente.']);
    }

    private function teamPayload(User $user, Team $team): array
    {
        return [
            'id' => $team->id,
            'name' => $team->name,
            'roles' => TenancyPermissions::withTeam(
                $team,
                fn () => $user->getRoleNames()->values(),
            ),
        ];
    }
}
