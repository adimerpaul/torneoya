<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $login = $credentials['login'] ?? $credentials['username'];

        $user = User::query()
            ->where(function ($query) use ($login): void {
                $query->where('username', $login)
                    ->orWhere('email', $login);
            })
            ->where('active', true)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Usuario o contrasena incorrectos.',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
//        return response()->json([
//            'message' => 'Registro exitoso.',
//        ], 201);
        $data = $request->validated();
        $data['role'] = 'Usuario';
        $data['active'] = true;
        $data['avatar'] = 'avatar.png';

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = now()->format('YmdHis').'_'.Str::random(8).'.'.$file->getClientOriginalExtension();
            $destination = public_path('images');
            if (! is_dir($destination)) {
                mkdir($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $data['avatar'] = $filename;
        }

        $user = User::query()->create($data);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Cuenta creada correctamente.',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesion cerrada correctamente.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json(
            User::query()
                ->latest('id')
                ->get()
        );
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['role'] = $data['role'] ?? 'Usuario';
        $data['active'] = $data['active'] ?? true;
        $data['avatar'] = $data['avatar'] ?? 'avatar.png';

        $user = User::query()->create($data);

        return response()->json($user, 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();
        if (array_key_exists('avatar', $data) && empty($data['avatar'])) {
            $data['avatar'] = 'avatar.png';
        }

        $user->update($data);

        return response()->json($user->fresh());
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }
}
