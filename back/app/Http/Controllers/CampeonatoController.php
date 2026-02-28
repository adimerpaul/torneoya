<?php

namespace App\Http\Controllers;

use App\Models\Campeonato;
use App\Models\CampeonatoEquipo;
use App\Models\CampeonatoGrupo;
use App\Models\CampeonatoJugador;
use App\Models\CampeonatoMensaje;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class CampeonatoController extends Controller
{
    private const DEFAULT_IMAGE = 'torneoImagen.jpg';
    private const DEFAULT_BANNER = 'torneoBanner.jpg';

    private function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (Campeonato::where('codigo', $code)->exists());

        return $code;
    }

    private function saveImage($file, string $prefix): string
    {
        $filename = $prefix . '_' . time() . '.webp';
        $path = public_path('images/' . $filename);
        $manager = new ImageManager(new Driver());

        $manager->read($file->getPathname())
            ->scaleDown(width: 1080, height: 1080)
            ->toWebp(60)
            ->save($path);

        return $filename;
    }

    private function canAccess(Request $request, Campeonato $campeonato): bool
    {
        $auth = $request->user();
        if (!$auth) return false;
        if ($auth->role === 'Administrador') return true;
        return (int) $campeonato->user_id === (int) $auth->id;
    }

    private function canModerate(Request $request, Campeonato $campeonato): bool
    {
        $auth = $request->user();
        if (!$auth) return false;
        return $auth->role === 'Administrador' || (int) $campeonato->user_id === (int) $auth->id;
    }

    public function deportes()
    {
        $data = [];
        foreach (Campeonato::deportesCatalogo() as $key => $info) {
            $data[] = [
                'key' => $key,
                'nombre' => $info['nombre'],
                'icono' => $info['icono'],
            ];
        }
        return response()->json($data);
    }

    public function index()
    {
        return Campeonato::whereNull('parent_id')
            ->where('user_id', request()->user()->id)
            ->withCount('categorias')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:180',
            'tipo' => 'required|in:unico,categorias',
            'deporte' => 'nullable|string|in:' . implode(',', array_keys(Campeonato::deportesCatalogo())),
            'descripcion' => 'nullable|string|max:1000',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if ($validated['tipo'] === 'unico' && empty($validated['deporte'])) {
            return response()->json(['message' => 'El deporte es obligatorio para campeonato unico'], 422);
        }
        if ($validated['tipo'] === 'categorias') {
            $validated['deporte'] = null;
        }

        $campeonato = Campeonato::create([
            'user_id' => $request->user()?->id,
            'nombre' => $validated['nombre'],
            'tipo' => $validated['tipo'],
            'deporte' => $validated['deporte'] ?? null,
            'descripcion' => $validated['descripcion'] ?? null,
            'fecha_inicio' => $validated['fecha_inicio'] ?? null,
            'fecha_fin' => $validated['fecha_fin'] ?? null,
            'codigo' => $this->generateCode(),
            'imagen' => self::DEFAULT_IMAGE,
            'banner' => self::DEFAULT_BANNER,
        ]);

        if ($request->hasFile('imagen')) {
            $campeonato->imagen = $this->saveImage($request->file('imagen'), 'camp');
        }
        if ($request->hasFile('banner')) {
            $campeonato->banner = $this->saveImage($request->file('banner'), 'ban');
        }
        $campeonato->save();

        return response()->json($campeonato->fresh(), 201);
    }

    public function update(Request $request, Campeonato $campeonato)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ($campeonato->parent_id) {
            return response()->json(['message' => 'Use el endpoint de categorias para actualizar este item'], 422);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:180',
            'tipo' => 'required|in:unico,categorias',
            'deporte' => 'nullable|string|in:' . implode(',', array_keys(Campeonato::deportesCatalogo())),
            'descripcion' => 'nullable|string|max:1000',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if ($validated['tipo'] === 'unico' && empty($validated['deporte'])) {
            return response()->json(['message' => 'El deporte es obligatorio para campeonato unico'], 422);
        }
        if ($validated['tipo'] === 'categorias') {
            $validated['deporte'] = null;
        }

        $campeonato->fill($validated);
        if (!$campeonato->codigo) {
            $campeonato->codigo = $this->generateCode();
        }
        if ($request->hasFile('imagen')) {
            $campeonato->imagen = $this->saveImage($request->file('imagen'), 'camp');
        }
        if ($request->hasFile('banner')) {
            $campeonato->banner = $this->saveImage($request->file('banner'), 'ban');
        }
        if (!$campeonato->imagen) {
            $campeonato->imagen = self::DEFAULT_IMAGE;
        }
        if (!$campeonato->banner) {
            $campeonato->banner = self::DEFAULT_BANNER;
        }
        $campeonato->save();

        return response()->json($campeonato->fresh());
    }

    public function destroy(Request $request, Campeonato $campeonato)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $campeonato->delete();
        return response()->json(['message' => 'Campeonato eliminado']);
    }

    public function categorias(Request $request, Campeonato $campeonato)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        return $campeonato->categorias()->get();
    }

    public function categoriaStore(Request $request, Campeonato $campeonato)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ($campeonato->tipo !== 'categorias') {
            return response()->json(['message' => 'Este campeonato no es por categorias'], 422);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:180',
            'deporte' => 'required|string|in:' . implode(',', array_keys(Campeonato::deportesCatalogo())),
            'descripcion' => 'nullable|string|max:1000',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $categoria = Campeonato::create([
            'user_id' => $request->user()?->id,
            'parent_id' => $campeonato->id,
            'nombre' => $validated['nombre'],
            'tipo' => 'categoria_item',
            'deporte' => $validated['deporte'],
            'descripcion' => $validated['descripcion'] ?? null,
            'fecha_inicio' => $validated['fecha_inicio'] ?? null,
            'fecha_fin' => $validated['fecha_fin'] ?? null,
            'codigo' => $this->generateCode(),
            'imagen' => self::DEFAULT_IMAGE,
            'banner' => self::DEFAULT_BANNER,
        ]);

        if ($request->hasFile('imagen')) {
            $categoria->imagen = $this->saveImage($request->file('imagen'), 'cat');
        }
        if ($request->hasFile('banner')) {
            $categoria->banner = $this->saveImage($request->file('banner'), 'catb');
        }
        $categoria->save();

        return response()->json($categoria->fresh(), 201);
    }

    public function categoriaUpdate(Request $request, Campeonato $campeonato, Campeonato $categoria)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int) $categoria->parent_id !== (int) $campeonato->id) {
            return response()->json(['message' => 'Categoria no pertenece al campeonato'], 422);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:180',
            'deporte' => 'required|string|in:' . implode(',', array_keys(Campeonato::deportesCatalogo())),
            'descripcion' => 'nullable|string|max:1000',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $categoria->fill($validated);
        if ($request->hasFile('imagen')) {
            $categoria->imagen = $this->saveImage($request->file('imagen'), 'cat');
        }
        if ($request->hasFile('banner')) {
            $categoria->banner = $this->saveImage($request->file('banner'), 'catb');
        }
        if (!$categoria->imagen) {
            $categoria->imagen = self::DEFAULT_IMAGE;
        }
        if (!$categoria->banner) {
            $categoria->banner = self::DEFAULT_BANNER;
        }
        $categoria->save();

        return response()->json($categoria->fresh());
    }

    public function categoriaDestroy(Request $request, Campeonato $campeonato, Campeonato $categoria)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int) $categoria->parent_id !== (int) $campeonato->id) {
            return response()->json(['message' => 'Categoria no pertenece al campeonato'], 422);
        }

        $categoria->delete();
        return response()->json(['message' => 'Categoria eliminada']);
    }

    public function gruposIndex(Request $request, Campeonato $campeonato)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return $campeonato->grupos()->get();
    }

    public function gruposStore(Request $request, Campeonato $campeonato)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        $exists = CampeonatoGrupo::where('campeonato_id', $campeonato->id)
            ->whereRaw('LOWER(nombre) = ?', [Str::lower(trim($validated['nombre']))])
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Ya existe una categoria/grupo con ese nombre'], 422);
        }

        $grupo = CampeonatoGrupo::create([
            'campeonato_id' => $campeonato->id,
            'nombre' => trim($validated['nombre']),
        ]);

        return response()->json($grupo, 201);
    }

    public function gruposDefaultsStore(Request $request, Campeonato $campeonato)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'cantidad' => 'nullable|integer|min:1|max:26',
        ]);
        $cantidad = (int)($validated['cantidad'] ?? 3);

        $letters = range('A', 'Z');
        $created = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $name = 'Grupo ' . $letters[$i];
            $exists = CampeonatoGrupo::where('campeonato_id', $campeonato->id)->where('nombre', $name)->exists();
            if (!$exists) {
                $created[] = CampeonatoGrupo::create([
                    'campeonato_id' => $campeonato->id,
                    'nombre' => $name,
                ]);
            }
        }

        return response()->json([
            'message' => 'Categorias/grupos por defecto aplicados',
            'created' => $created,
        ]);
    }

    public function gruposUpdate(Request $request, Campeonato $campeonato, CampeonatoGrupo $grupo)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int)$grupo->campeonato_id !== (int)$campeonato->id) {
            return response()->json(['message' => 'Categoria/grupo no pertenece al campeonato'], 422);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        $exists = CampeonatoGrupo::where('campeonato_id', $campeonato->id)
            ->where('id', '!=', $grupo->id)
            ->whereRaw('LOWER(nombre) = ?', [Str::lower(trim($validated['nombre']))])
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Ya existe una categoria/grupo con ese nombre'], 422);
        }

        $grupo->nombre = trim($validated['nombre']);
        $grupo->save();
        return response()->json($grupo);
    }

    public function gruposDestroy(Request $request, Campeonato $campeonato, CampeonatoGrupo $grupo)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int)$grupo->campeonato_id !== (int)$campeonato->id) {
            return response()->json(['message' => 'Categoria/grupo no pertenece al campeonato'], 422);
        }

        $grupo->delete();
        return response()->json(['message' => 'Categoria/grupo eliminado']);
    }

    public function equiposIndex(Request $request, Campeonato $campeonato)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return CampeonatoEquipo::where('campeonato_id', $campeonato->id)
            ->with(['grupo', 'jugadores'])
            ->orderBy('nombre')
            ->get();
    }

    public function equiposStore(Request $request, Campeonato $campeonato)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:160',
            'entrenador' => 'nullable|string|max:160',
            'campeonato_grupo_id' => 'nullable|integer|exists:campeonato_grupos,id',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $groupId = $validated['campeonato_grupo_id'] ?? null;
        if ($groupId) {
            $belongs = CampeonatoGrupo::where('id', $groupId)->where('campeonato_id', $campeonato->id)->exists();
            if (!$belongs) {
                return response()->json(['message' => 'La categoria/grupo no pertenece al campeonato'], 422);
            }
        }

        $equipo = CampeonatoEquipo::create([
            'campeonato_id' => $campeonato->id,
            'campeonato_grupo_id' => $groupId,
            'nombre' => trim($validated['nombre']),
            'entrenador' => $validated['entrenador'] ?? null,
            'imagen' => null,
        ]);

        if ($request->hasFile('imagen')) {
            $equipo->imagen = $this->saveImage($request->file('imagen'), 'eq');
            $equipo->save();
        }

        return response()->json($equipo->load(['grupo', 'jugadores']), 201);
    }

    public function equiposUpdate(Request $request, Campeonato $campeonato, CampeonatoEquipo $equipo)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int)$equipo->campeonato_id !== (int)$campeonato->id) {
            return response()->json(['message' => 'Equipo no pertenece al campeonato'], 422);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:160',
            'entrenador' => 'nullable|string|max:160',
            'campeonato_grupo_id' => 'nullable|integer|exists:campeonato_grupos,id',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $groupId = $validated['campeonato_grupo_id'] ?? null;
        if ($groupId) {
            $belongs = CampeonatoGrupo::where('id', $groupId)->where('campeonato_id', $campeonato->id)->exists();
            if (!$belongs) {
                return response()->json(['message' => 'La categoria/grupo no pertenece al campeonato'], 422);
            }
        }

        $equipo->nombre = trim($validated['nombre']);
        $equipo->entrenador = $validated['entrenador'] ?? null;
        $equipo->campeonato_grupo_id = $groupId;
        if ($request->hasFile('imagen')) {
            $equipo->imagen = $this->saveImage($request->file('imagen'), 'eq');
        }
        $equipo->save();

        return response()->json($equipo->load(['grupo', 'jugadores']));
    }

    public function equiposDestroy(Request $request, Campeonato $campeonato, CampeonatoEquipo $equipo)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int)$equipo->campeonato_id !== (int)$campeonato->id) {
            return response()->json(['message' => 'Equipo no pertenece al campeonato'], 422);
        }

        $equipo->delete();
        return response()->json(['message' => 'Equipo eliminado']);
    }

    public function jugadoresStore(Request $request, Campeonato $campeonato, CampeonatoEquipo $equipo)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int)$equipo->campeonato_id !== (int)$campeonato->id) {
            return response()->json(['message' => 'Equipo no pertenece al campeonato'], 422);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:160',
            'abreviado' => 'nullable|string|max:40',
            'posicion' => 'nullable|string|max:80',
            'numero_camiseta' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'documento' => 'nullable|string|max:60',
            'celular' => 'nullable|string|max:30',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $jugador = CampeonatoJugador::create([
            'campeonato_equipo_id' => $equipo->id,
            'nombre' => trim($validated['nombre']),
            'abreviado' => $validated['abreviado'] ?? null,
            'posicion' => $validated['posicion'] ?? null,
            'numero_camiseta' => $validated['numero_camiseta'] ?? null,
            'fecha_nacimiento' => $validated['fecha_nacimiento'] ?? null,
            'documento' => $validated['documento'] ?? null,
            'celular' => $validated['celular'] ?? null,
            'foto' => null,
        ]);

        if ($request->hasFile('foto')) {
            $jugador->foto = $this->saveImage($request->file('foto'), 'jug');
            $jugador->save();
        }

        return response()->json($jugador, 201);
    }

    public function jugadoresUpdate(Request $request, Campeonato $campeonato, CampeonatoEquipo $equipo, CampeonatoJugador $jugador)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int)$equipo->campeonato_id !== (int)$campeonato->id || (int)$jugador->campeonato_equipo_id !== (int)$equipo->id) {
            return response()->json(['message' => 'Jugador no pertenece al equipo/campeonato'], 422);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:160',
            'abreviado' => 'nullable|string|max:40',
            'posicion' => 'nullable|string|max:80',
            'numero_camiseta' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'documento' => 'nullable|string|max:60',
            'celular' => 'nullable|string|max:30',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $jugador->fill($validated);
        if ($request->hasFile('foto')) {
            $jugador->foto = $this->saveImage($request->file('foto'), 'jug');
        }
        $jugador->save();

        return response()->json($jugador);
    }

    public function jugadoresDestroy(Request $request, Campeonato $campeonato, CampeonatoEquipo $equipo, CampeonatoJugador $jugador)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int)$equipo->campeonato_id !== (int)$campeonato->id || (int)$jugador->campeonato_equipo_id !== (int)$equipo->id) {
            return response()->json(['message' => 'Jugador no pertenece al equipo/campeonato'], 422);
        }

        $jugador->delete();
        return response()->json(['message' => 'Jugador eliminado']);
    }

    public function publicMensajes(Request $request, string $code)
    {
        $campeonato = Campeonato::where('codigo', strtoupper($code))->first();
        if (!$campeonato) {
            return response()->json(['message' => 'Codigo no encontrado'], 404);
        }

        $query = CampeonatoMensaje::where('campeonato_id', $campeonato->id)->with('user:id,name,username');
        if (!$this->canModerate($request, $campeonato)) {
            $query->where('visible', true);
        }

        return $query->latest()->get();
    }

    public function publicMensajeStore(Request $request, string $code)
    {
        $campeonato = Campeonato::where('codigo', strtoupper($code))->first();
        if (!$campeonato) {
            return response()->json(['message' => 'Codigo no encontrado'], 404);
        }

        $validated = $request->validate([
            'mensaje' => 'required|string|max:1000',
            'autor_nombre' => 'nullable|string|max:120',
        ]);

        $user = $request->user();
        $autor = $validated['autor_nombre'] ?? ($user?->name ?: $user?->username ?: 'Publico');

        $msg = CampeonatoMensaje::create([
            'campeonato_id' => $campeonato->id,
            'user_id' => $user?->id,
            'autor_nombre' => $autor,
            'mensaje' => $validated['mensaje'],
            'visible' => true,
        ]);

        return response()->json($msg->load('user:id,name,username'), 201);
    }

    public function mensajeToggleVisible(Request $request, Campeonato $campeonato, CampeonatoMensaje $mensaje)
    {
        if (!$this->canModerate($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int) $mensaje->campeonato_id !== (int) $campeonato->id) {
            return response()->json(['message' => 'Mensaje no pertenece al campeonato'], 422);
        }

        $mensaje->visible = !$mensaje->visible;
        $mensaje->save();

        return response()->json($mensaje);
    }

    public function publicShowByCode(string $code)
    {
        $campeonato = Campeonato::where('codigo', strtoupper($code))
            ->with([
                'parent:id,nombre,codigo',
                'categorias',
                'grupos',
                'equipos.jugadores',
                'equipos.grupo',
                'user:id,name,username,avatar,telefono_contacto_1',
            ])
            ->first();

        if (!$campeonato) {
            return response()->json(['message' => 'Codigo no encontrado'], 404);
        }

        return response()->json($campeonato);
    }
}
