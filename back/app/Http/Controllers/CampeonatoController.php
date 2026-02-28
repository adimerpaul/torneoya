<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampeonatoRequest;
use App\Http\Requests\UpdateCampeonatoRequest;
use App\Models\Campeonato;
use App\Models\CategoriaCampeonato;
use App\Models\Deporte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampeonatoController extends Controller
{
    public function deportes(): JsonResponse
    {
        return response()->json(
            Deporte::query()->where('activo', true)->orderBy('nombre')->get()
        );
    }

    public function index(Request $request): JsonResponse
    {
        $rows = Campeonato::query()
            ->with(['deporte:id,nombre,icono', 'categorias.deporte:id,nombre,icono'])
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->get();

        return response()->json($rows);
    }

    public function store(StoreCampeonatoRequest $request): JsonResponse
    {
        $data = $request->validated();
        $categorias = $data['categorias'] ?? [];
        unset($data['categorias']);
        $data['user_id'] = $request->user()->id;
        $data['estado'] = $data['estado'] ?? 'borrador';
        if ($data['tipo'] === 'categorias') {
            $data['deporte_id'] = null;
        }

        $campeonato = DB::transaction(function () use ($data, $categorias) {
            $campeonato = Campeonato::query()->create($data);

            foreach ($categorias as $categoria) {
                $campeonato->categorias()->create([
                    'deporte_id' => $categoria['deporte_id'],
                    'nombre' => $categoria['nombre'],
                    'formato' => $categoria['formato'] ?? null,
                    'estado' => $categoria['estado'] ?? 'borrador',
                ]);
            }

            return $campeonato;
        });

        return response()->json(
            $campeonato->load(['deporte:id,nombre,icono', 'categorias.deporte:id,nombre,icono']),
            201
        );
    }

    public function show(Request $request, Campeonato $campeonato): JsonResponse
    {
        $this->authorizeOwner($request, $campeonato);

        return response()->json(
            $campeonato->load(['deporte:id,nombre,icono', 'categorias.deporte:id,nombre,icono'])
        );
    }

    public function update(UpdateCampeonatoRequest $request, Campeonato $campeonato): JsonResponse
    {
        $this->authorizeOwner($request, $campeonato);
        $data = $request->validated();
        $categorias = $data['categorias'] ?? null;
        unset($data['categorias']);

        if (($data['tipo'] ?? $campeonato->tipo) === 'categorias') {
            $data['deporte_id'] = null;
        }

        DB::transaction(function () use ($campeonato, $data, $categorias) {
            $campeonato->update($data);

            if (is_array($categorias)) {
                $campeonato->categorias()->delete();
                foreach ($categorias as $categoria) {
                    $campeonato->categorias()->create([
                        'deporte_id' => $categoria['deporte_id'],
                        'nombre' => $categoria['nombre'],
                        'formato' => $categoria['formato'] ?? null,
                        'estado' => $categoria['estado'] ?? 'borrador',
                    ]);
                }
            }
        });

        return response()->json(
            $campeonato->fresh()->load(['deporte:id,nombre,icono', 'categorias.deporte:id,nombre,icono'])
        );
    }

    public function destroy(Request $request, Campeonato $campeonato): JsonResponse
    {
        $this->authorizeOwner($request, $campeonato);
        $campeonato->delete();

        return response()->json(['message' => 'Campeonato eliminado correctamente.']);
    }

    public function categorias(Request $request, Campeonato $campeonato): JsonResponse
    {
        $this->authorizeOwner($request, $campeonato);

        return response()->json(
            $campeonato->categorias()->with('deporte:id,nombre,icono')->latest('id')->get()
        );
    }

    public function storeCategoria(Request $request, Campeonato $campeonato): JsonResponse
    {
        $this->authorizeOwner($request, $campeonato);
        abort_unless($campeonato->tipo === 'categorias', 422, 'Este campeonato no es por categorias.');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'deporte_id' => ['required', 'integer', 'exists:deportes,id'],
            'formato' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'in:borrador,publicado,en_juego,finalizado,cancelado'],
        ]);

        $categoria = $campeonato->categorias()->create($data);

        return response()->json($categoria->load('deporte:id,nombre,icono'), 201);
    }

    public function updateCategoria(Request $request, CategoriaCampeonato $categoria): JsonResponse
    {
        $campeonato = Campeonato::query()->findOrFail($categoria->campeonato_id);
        $this->authorizeOwner($request, $campeonato);

        $data = $request->validate([
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'deporte_id' => ['sometimes', 'required', 'integer', 'exists:deportes,id'],
            'formato' => ['sometimes', 'nullable', 'string', 'max:100'],
            'estado' => ['sometimes', 'in:borrador,publicado,en_juego,finalizado,cancelado'],
        ]);

        $categoria->update($data);

        return response()->json($categoria->fresh()->load('deporte:id,nombre,icono'));
    }

    public function destroyCategoria(Request $request, CategoriaCampeonato $categoria): JsonResponse
    {
        $campeonato = Campeonato::query()->findOrFail($categoria->campeonato_id);
        $this->authorizeOwner($request, $campeonato);
        $categoria->delete();

        return response()->json(['message' => 'Categoria eliminada correctamente.']);
    }

    private function authorizeOwner(Request $request, Campeonato $campeonato): void
    {
        abort_unless($campeonato->user_id === $request->user()->id, 403, 'No autorizado');
    }
}
