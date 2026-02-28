<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::query()
            ->where('username', $credentials['username'])
            ->where('active', true)
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Usuario o contraseña incorrectos.',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->storeAudit(
            action: 'login',
            request: $request,
            userId: $user->id,
            auditableType: User::class,
            auditableId: $user->id,
        );

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        $this->storeAudit(
            action: 'logout',
            request: $request,
            userId: $request->user()?->id,
            auditableType: User::class,
            auditableId: $request->user()?->id,
        );

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
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

        $this->storeAudit(
            action: 'users.create',
            request: $request,
            userId: $request->user()?->id,
            auditableType: User::class,
            auditableId: $user->id,
            newValues: $user->toArray(),
        );

        return response()->json($user, 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $oldValues = $user->toArray();
        $data = $request->validated();
        if (array_key_exists('avatar', $data) && empty($data['avatar'])) {
            $data['avatar'] = 'avatar.png';
        }
        $user->update($data);

        $this->storeAudit(
            action: 'users.update',
            request: $request,
            userId: $request->user()?->id,
            auditableType: User::class,
            auditableId: $user->id,
            oldValues: $oldValues,
            newValues: $user->fresh()?->toArray() ?? [],
        );

        return response()->json($user->fresh());
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $oldValues = $user->toArray();
        $user->delete();

        $this->storeAudit(
            action: 'users.delete',
            request: $request,
            userId: $request->user()?->id,
            auditableType: User::class,
            auditableId: $user->id,
            oldValues: $oldValues,
            newValues: ['deleted_at' => now()->toDateTimeString()],
        );

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    private function storeAudit(
        string $action,
        Request $request,
        ?int $userId = null,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        Audit::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
