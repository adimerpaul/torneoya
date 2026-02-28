<?php

namespace App\Http\Controllers;

use App\Models\Campeonato;
use App\Models\CampeonatoEquipo;
use App\Models\CampeonatoFase;
use App\Models\CampeonatoFecha;
use App\Models\CampeonatoGrupo;
use App\Models\CampeonatoJugador;
use App\Models\CampeonatoMensaje;
use App\Models\CampeonatoPartido;
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

    private function ensureDefaultFase(Campeonato $campeonato): CampeonatoFase
    {
        $fase = CampeonatoFase::where('campeonato_id', $campeonato->id)->orderBy('orden')->first();
        if ($fase) return $fase;

        return CampeonatoFase::create([
            'campeonato_id' => $campeonato->id,
            'nombre' => '1° Fase',
            'tipo' => 'liga',
            'orden' => 1,
            'agrupar_por_grupo' => true,
        ]);
    }

    private function standingsForFase(Campeonato $campeonato, CampeonatoFase $fase): array
    {
        $equipos = CampeonatoEquipo::where('campeonato_id', $campeonato->id)->with('grupo')->get();
        $partidos = CampeonatoPartido::where('campeonato_fase_id', $fase->id)
            ->with(['local:id,nombre,campeonato_grupo_id', 'visita:id,nombre,campeonato_grupo_id'])
            ->get();

        $groups = [];
        foreach ($equipos as $eq) {
            $groupName = $fase->agrupar_por_grupo
                ? ($eq->grupo?->nombre ?: 'Sin grupo')
                : 'General';
            if (!isset($groups[$groupName])) $groups[$groupName] = [];
            $groups[$groupName][$eq->id] = [
                'equipo_id' => $eq->id,
                'equipo' => $eq->nombre,
                'pj' => 0,
                'pg' => 0,
                'pe' => 0,
                'pp' => 0,
                'gf' => 0,
                'gc' => 0,
                'dif' => 0,
                'pts' => 0,
                'porcentaje' => 0,
            ];
        }

        foreach ($partidos as $p) {
            if ($p->estado !== 'jugado') continue;
            if ($p->goles_local === null || $p->goles_visita === null) continue;
            if (!$p->local_equipo_id || !$p->visita_equipo_id) continue;

            $groupName = $fase->agrupar_por_grupo ? ($p->grupo_nombre ?: 'Sin grupo') : 'General';
            if (!isset($groups[$groupName])) $groups[$groupName] = [];
            if (!isset($groups[$groupName][$p->local_equipo_id])) continue;
            if (!isset($groups[$groupName][$p->visita_equipo_id])) continue;

            $home = &$groups[$groupName][$p->local_equipo_id];
            $away = &$groups[$groupName][$p->visita_equipo_id];

            $home['pj']++;
            $away['pj']++;
            $home['gf'] += (int)$p->goles_local;
            $home['gc'] += (int)$p->goles_visita;
            $away['gf'] += (int)$p->goles_visita;
            $away['gc'] += (int)$p->goles_local;

            if ($p->goles_local > $p->goles_visita) {
                $home['pg']++;
                $home['pts'] += 3;
                $away['pp']++;
            } elseif ($p->goles_local < $p->goles_visita) {
                $away['pg']++;
                $away['pts'] += 3;
                $home['pp']++;
            } else {
                $home['pe']++;
                $away['pe']++;
                $home['pts']++;
                $away['pts']++;
            }
            unset($home, $away);
        }

        $result = [];
        foreach ($groups as $groupName => $rows) {
            $list = array_values($rows);
            foreach ($list as &$r) {
                $r['dif'] = $r['gf'] - $r['gc'];
                $r['porcentaje'] = $r['pj'] > 0 ? round(($r['pts'] / ($r['pj'] * 3)) * 100, 1) : 0;
            }
            unset($r);

            usort($list, function ($a, $b) {
                return [$b['pts'], $b['dif'], $b['gf'], $a['equipo']] <=> [$a['pts'], $a['dif'], $a['gf'], $b['equipo']];
            });

            $result[] = [
                'grupo' => $groupName,
                'rows' => $list,
            ];
        }

        usort($result, fn($a, $b) => strcmp($a['grupo'], $b['grupo']));
        return $result;
    }

    private function roundRobinPairs(array $teamIds): array
    {
        $teams = array_values($teamIds);
        if (count($teams) < 2) return [];
        if (count($teams) % 2 === 1) $teams[] = null;

        $rounds = count($teams) - 1;
        $half = count($teams) / 2;
        $result = [];

        for ($r = 0; $r < $rounds; $r++) {
            $pairs = [];
            for ($i = 0; $i < $half; $i++) {
                $home = $teams[$i];
                $away = $teams[count($teams) - 1 - $i];
                if ($home !== null && $away !== null) {
                    $pairs[] = [$home, $away];
                }
            }
            $result[] = $pairs;

            $fixed = array_shift($teams);
            $last = array_pop($teams);
            array_unshift($teams, $fixed);
            array_splice($teams, 1, 0, [$last]);
        }

        return $result;
    }

    public function clasificacionPublic(string $code)
    {
        $campeonato = Campeonato::where('codigo', strtoupper($code))->first();
        if (!$campeonato) {
            return response()->json(['message' => 'Codigo no encontrado'], 404);
        }

        $this->ensureDefaultFase($campeonato);
        $fases = CampeonatoFase::where('campeonato_id', $campeonato->id)
            ->with(['fechas', 'partidos.local', 'partidos.visita'])
            ->orderBy('orden')
            ->get();

        $out = $fases->map(function ($fase) use ($campeonato) {
            return [
                'id' => $fase->id,
                'nombre' => $fase->nombre,
                'tipo' => $fase->tipo,
                'orden' => $fase->orden,
                'agrupar_por_grupo' => (bool)$fase->agrupar_por_grupo,
                'fechas' => $fase->fechas,
                'partidos' => $fase->partidos,
                'tabla' => $this->standingsForFase($campeonato, $fase),
            ];
        });

        return response()->json($out);
    }

    public function fasesIndex(Request $request, Campeonato $campeonato)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $this->ensureDefaultFase($campeonato);
        return CampeonatoFase::where('campeonato_id', $campeonato->id)->orderBy('orden')->get();
    }

    public function fasesStore(Request $request, Campeonato $campeonato)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $validated = $request->validate([
            'nombre' => 'required|string|max:120',
            'tipo' => 'nullable|in:liga,eliminatoria',
            'agrupar_por_grupo' => 'nullable|boolean',
        ]);

        $orden = (int)CampeonatoFase::where('campeonato_id', $campeonato->id)->max('orden') + 1;
        $fase = CampeonatoFase::create([
            'campeonato_id' => $campeonato->id,
            'nombre' => trim($validated['nombre']),
            'tipo' => $validated['tipo'] ?? 'liga',
            'orden' => $orden ?: 1,
            'agrupar_por_grupo' => (bool)($validated['agrupar_por_grupo'] ?? true),
        ]);

        return response()->json($fase, 201);
    }

    public function fasesUpdate(Request $request, Campeonato $campeonato, CampeonatoFase $fase)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int)$fase->campeonato_id !== (int)$campeonato->id) {
            return response()->json(['message' => 'Fase no pertenece al campeonato'], 422);
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:120',
            'tipo' => 'nullable|in:liga,eliminatoria',
            'agrupar_por_grupo' => 'nullable|boolean',
        ]);

        $fase->nombre = trim($validated['nombre']);
        if (isset($validated['tipo'])) $fase->tipo = $validated['tipo'];
        if (array_key_exists('agrupar_por_grupo', $validated)) $fase->agrupar_por_grupo = (bool)$validated['agrupar_por_grupo'];
        $fase->save();

        return response()->json($fase);
    }

    public function fasesDestroy(Request $request, Campeonato $campeonato, CampeonatoFase $fase)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int)$fase->campeonato_id !== (int)$campeonato->id) {
            return response()->json(['message' => 'Fase no pertenece al campeonato'], 422);
        }
        $fase->delete();
        return response()->json(['message' => 'Fase eliminada']);
    }

    public function fechasStore(Request $request, Campeonato $campeonato, CampeonatoFase $fase)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int)$fase->campeonato_id !== (int)$campeonato->id) {
            return response()->json(['message' => 'Fase no pertenece al campeonato'], 422);
        }

        $orden = (int)CampeonatoFecha::where('campeonato_fase_id', $fase->id)->max('orden') + 1;
        $fecha = CampeonatoFecha::create([
            'campeonato_fase_id' => $fase->id,
            'nombre' => $orden . '° Fecha',
            'orden' => $orden ?: 1,
        ]);
        return response()->json($fecha, 201);
    }

    public function partidosGenerar(Request $request, Campeonato $campeonato, CampeonatoFase $fase)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int)$fase->campeonato_id !== (int)$campeonato->id) {
            return response()->json(['message' => 'Fase no pertenece al campeonato'], 422);
        }

        $validated = $request->validate([
            'modo' => 'required|in:ida,ida_vuelta',
        ]);

        $equipos = CampeonatoEquipo::where('campeonato_id', $campeonato->id)->with('grupo')->orderBy('id')->get();
        if ($equipos->count() < 2) {
            return response()->json(['message' => 'Se requieren al menos 2 equipos'], 422);
        }

        CampeonatoPartido::where('campeonato_fase_id', $fase->id)->delete();
        CampeonatoFecha::where('campeonato_fase_id', $fase->id)->delete();

        $buckets = [];
        foreach ($equipos as $eq) {
            $key = $fase->agrupar_por_grupo ? ($eq->grupo?->nombre ?: 'Sin grupo') : 'General';
            if (!isset($buckets[$key])) $buckets[$key] = [];
            $buckets[$key][] = $eq->id;
        }

        $globalRound = 1;
        foreach ($buckets as $groupName => $teamIds) {
            $rounds = $this->roundRobinPairs($teamIds);
            if (empty($rounds)) continue;

            $legs = $validated['modo'] === 'ida_vuelta' ? 2 : 1;
            for ($leg = 1; $leg <= $legs; $leg++) {
                foreach ($rounds as $pairs) {
                    $fecha = CampeonatoFecha::create([
                        'campeonato_fase_id' => $fase->id,
                        'nombre' => $globalRound . '° Fecha',
                        'orden' => $globalRound,
                    ]);

                    foreach ($pairs as [$home, $away]) {
                        $local = $leg === 1 ? $home : $away;
                        $visit = $leg === 1 ? $away : $home;

                        CampeonatoPartido::create([
                            'campeonato_id' => $campeonato->id,
                            'campeonato_fase_id' => $fase->id,
                            'campeonato_fecha_id' => $fecha->id,
                            'local_equipo_id' => $local,
                            'visita_equipo_id' => $visit,
                            'grupo_nombre' => $groupName,
                            'estado' => 'pendiente',
                        ]);
                    }
                    $globalRound++;
                }
            }
        }

        return response()->json(['message' => 'Partidos generados']);
    }

    public function partidosStore(Request $request, Campeonato $campeonato, CampeonatoFase $fase)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int)$fase->campeonato_id !== (int)$campeonato->id) {
            return response()->json(['message' => 'Fase no pertenece al campeonato'], 422);
        }

        $validated = $request->validate([
            'campeonato_fecha_id' => 'nullable|integer|exists:campeonato_fechas,id',
            'local_equipo_id' => 'nullable|integer|exists:campeonato_equipos,id',
            'visita_equipo_id' => 'nullable|integer|exists:campeonato_equipos,id',
            'grupo_nombre' => 'nullable|string|max:120',
            'goles_local' => 'nullable|integer|min:0|max:99',
            'goles_visita' => 'nullable|integer|min:0|max:99',
            'estado' => 'nullable|in:pendiente,jugado',
        ]);

        $partido = CampeonatoPartido::create([
            'campeonato_id' => $campeonato->id,
            'campeonato_fase_id' => $fase->id,
            'campeonato_fecha_id' => $validated['campeonato_fecha_id'] ?? null,
            'local_equipo_id' => $validated['local_equipo_id'] ?? null,
            'visita_equipo_id' => $validated['visita_equipo_id'] ?? null,
            'grupo_nombre' => $validated['grupo_nombre'] ?? null,
            'goles_local' => $validated['goles_local'] ?? null,
            'goles_visita' => $validated['goles_visita'] ?? null,
            'estado' => $validated['estado'] ?? 'pendiente',
        ]);

        return response()->json($partido->load(['local', 'visita', 'fecha']), 201);
    }

    public function partidosUpdate(Request $request, Campeonato $campeonato, CampeonatoPartido $partido)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int)$partido->campeonato_id !== (int)$campeonato->id) {
            return response()->json(['message' => 'Partido no pertenece al campeonato'], 422);
        }

        $validated = $request->validate([
            'campeonato_fecha_id' => 'nullable|integer|exists:campeonato_fechas,id',
            'local_equipo_id' => 'nullable|integer|exists:campeonato_equipos,id',
            'visita_equipo_id' => 'nullable|integer|exists:campeonato_equipos,id',
            'grupo_nombre' => 'nullable|string|max:120',
            'goles_local' => 'nullable|integer|min:0|max:99',
            'goles_visita' => 'nullable|integer|min:0|max:99',
            'estado' => 'nullable|in:pendiente,jugado',
        ]);

        $partido->fill($validated);
        $partido->save();
        return response()->json($partido->load(['local', 'visita', 'fecha']));
    }

    public function partidosDestroy(Request $request, Campeonato $campeonato, CampeonatoPartido $partido)
    {
        if (!$this->canAccess($request, $campeonato)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ((int)$partido->campeonato_id !== (int)$campeonato->id) {
            return response()->json(['message' => 'Partido no pertenece al campeonato'], 422);
        }

        $partido->delete();
        return response()->json(['message' => 'Partido eliminado']);
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
