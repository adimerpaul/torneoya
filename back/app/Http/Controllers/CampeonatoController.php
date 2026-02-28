<?php

namespace App\Http\Controllers;

use App\Models\Campeonato;
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
            ->with(['categorias', 'user:id,name,username,avatar,telefono_contacto_1'])
            ->first();

        if (!$campeonato) {
            return response()->json(['message' => 'Codigo no encontrado'], 404);
        }

        return response()->json($campeonato);
    }
}
