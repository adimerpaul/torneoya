<?php

namespace App\Http\Controllers;

//use App\Mail\UserCreatedMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller{
    private function compressAndSaveAvatar($file, int $userId): string
    {
        $filename = time() . '_' . $userId . '.webp';
        $path = public_path('images/' . $filename);
        $manager = new ImageManager(new Driver());

        $manager->read($file->getPathname())
            ->cover(256, 256)
            ->toWebp(45)
            ->save($path);

        return $filename;
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'required|string|min:6',
            'telefono_contacto_1' => 'required|string|max:30',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
//        verificar si existe el nombre
        if (User::where('username', $validatedData['username'])->exists()) {
            return response()->json(['message' => 'El nombre de usuario ya existe'], 422);
        }

        $user = User::create([
            'name' => $validatedData['name'],
            'username' => $validatedData['username'],
            'email' => $validatedData['email'] ?? null,
            'password' => $validatedData['password'],
            'clave' => $validatedData['password'],
            'telefono_contacto_1' => $validatedData['telefono_contacto_1'] ?? null,
            'role' => 'Usuario',
            'avatar' => 'default.png',
        ]);

        if ($request->hasFile('avatar')) {
            $filename = $this->compressAndSaveAvatar($request->file('avatar'), (int) $user->id);
            $user->avatar = $filename;
            $user->save();
        }

        $permisos = ['Dashboard', 'Graderias'];
        $permissions = Permission::whereIn('name', $permisos)->get();
        $user->syncPermissions($permissions);
        $user->load('permissions:id,name');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function changeMyPasswordDialog(Request $request)
    {
        return $this->changeMyPassword($request);
    }

    public function changeMyPassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed', // requiere password_confirmation
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta.']
            ]);
        }

        // Cambia password
        $user->password = Hash::make($request->password);
        $user->clave = $request->password; // si usas campo 'clave' también
        $user->save();

        // ? Bota a TODOS (incluyéndolo a él): revoca todos los tokens
        $user = $request->user();

        $currentTokenId = $request->user()->currentAccessToken()?->id;

        $user->tokens()
            ->when($currentTokenId, fn ($q) => $q->where('id', '!=', $currentTokenId))
            ->delete();


        return response()->json([
            'message' => 'Contraseña actualizada. Se cerraron todas las sesiones.'
        ]);
    }

    public function adminResetPassword(Request $request, User $user)
    {
        // ? Asegura permisos (ajusta a tu sistema)
        // Ejemplo simple por permission:
        if (!$request->user()->can('Usuarios')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
            // opcional: motivo
            'reason' => 'nullable|string|max:255',
        ]);

        // Resetea password del usuario objetivo
        $user->password = Hash::make($request->password);
        $user->clave = $request->password; // si usas campo 'clave' también
        $user->save();

        // ? Bota a TODOS: revoca tokens del usuario objetivo
        $user = $request->user();

        $currentTokenId = $request->user()->currentAccessToken()?->id;

        $user->tokens()
            ->when($currentTokenId, fn ($q) => $q->where('id', '!=', $currentTokenId))
            ->delete();


        // ? Auditoría (sin guardar password)
        // Puedes registrar en logs o una tabla password_resets_admin
        // \Log::info('Admin reset password', ['admin_id' => $request->user()->id, 'user_id' => $user->id, 'reason' => $request->reason]);

        return response()->json([
            'message' => 'Contraseña reseteada. Se cerraron todas las sesiones del usuario.'
        ]);
    }
    function updateUserPermissions(Request $request, $userId){
        $user = User::findOrFail($userId);
        $permissions = Permission::whereIn('id', $request->permissions)->get();
        $user->syncPermissions($permissions);
        return $user->permissions()->pluck('name');
    }
    function userPermissions(Request $request, $userId){
        $user = User::findOrFail($userId);
        return $user->permissions()->pluck('id');
    }
    function permissions(){
        return  Permission::all();
    }
    public function updateAvatar(Request $request, $userId)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        if ($request->hasFile('avatar')) {
            $filename = $this->compressAndSaveAvatar($request->file('avatar'), (int) $user->id);
            $user->avatar = $filename;
            $user->save();

            return response()->json(['message' => 'Avatar actualizado', 'avatar' => $filename]);
        }

        return response()->json(['message' => 'No se ha enviado un archivo'], 400);
    }
    function login(Request $request){
        $credentials = $request->only('username', 'password');
        $user = User::where('username', $credentials['username'])
            ->with('permissions:id,name')
            ->first();
        if (!$user || !password_verify($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Usuario o contraseña incorrectos',
            ], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
    function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Token eliminado',
        ]);
    }
    function me(Request $request){
//        $user = $request->user();
//        $user->load('permissions,establecimiento,area');
        $user = User::where('id', $request->user()->id)
            ->with('permissions:id,name')
            ->first();
        return response()->json($user);
    }
    function index(){
        return User::where('id', '!=', 0)
            ->with('permissions:id,name')
            ->orderBy('id', 'desc')
            ->get();
    }
    function update(Request $request, $id){
        $user = User::find($id);
        $validatedData = $request->validate([
            'name' => 'sometimes|nullable|string|max:255',
            'username' => 'sometimes|nullable|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'avatar' => 'sometimes|nullable|string|max:255',
            'role' => 'sometimes|nullable|string|max:100',
            'telefono_contacto_1' => 'sometimes|nullable|string|max:30',
            'telefono_contacto_2' => 'sometimes|nullable|string|max:30',
        ]);
        $user->update($validatedData);
        error_log('User' . json_encode($user));
        return $user;
    }
    function updatePassword(Request $request, $id){
        $user = User::find($id);
        $user->update([
            'password' => bcrypt($request->password),
            'clave' => $request->password,
        ]);
        return $user;
    }
    function store(Request $request){
        $validatedData = $request->validate([
            'username' => 'required',
            'password' => 'required',
            'name' => 'required',
            'telefono_contacto_1' => 'nullable|string|max:30',
            'telefono_contacto_2' => 'nullable|string|max:30',
//            'email' => 'required|email|unique:users',
        ]);
        if (User::where('username', $request->username)->exists()) {
            return response()->json(['message' => 'El nombre de usuario ya existe'], 422);
        }
        $user = User::create($validatedData);
//        crear los permisos de
//        'Dashboard',
//            'Graderias',
        $permisos = ['Dashboard', 'Graderias'];
        $permissions = Permission::whereIn('name', $permisos)->get();
        $user->syncPermissions($permissions);
        return $user;
    }
    function destroy($id){
        return User::destroy($id);
    }
    public function getPermissions($userId)
    {
        $user = User::findOrFail($userId);
        // devuelve IDs de permisos del usuario
        return $user->permissions()->pluck('id');
    }

    public function syncPermissions(Request $request, $userId)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $user = User::findOrFail($userId);
        $perms = Permission::whereIn('id', $request->permissions ?? [])->get();
        $user->syncPermissions($perms);

        return response()->json([
            'message' => 'Permisos actualizados',
            'permissions' => $user->permissions()->pluck('name'),
        ]);
    }
}
