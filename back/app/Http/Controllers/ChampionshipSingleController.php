<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChampionshipSingleRequest;
use App\Http\Requests\UpdateChampionshipSingleRequest;
use App\Models\ChampionshipSingle;
use App\Models\PublicShareSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChampionshipSingleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            ChampionshipSingle::query()
                ->where('user_id', $request->user()->id)
                ->latest('id')
                ->paginate(20)
        );
    }

    public function store(StoreChampionshipSingleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['slug'] = $this->buildUniqueSlug($data['name']);
        $data['status'] = $data['status'] ?? 'draft';

        $championship = ChampionshipSingle::query()->create($data);

        PublicShareSetting::query()->create([
            'scope_type' => 'single',
            'scope_id' => $championship->id,
            'is_public' => false,
            'public_slug' => $championship->slug,
        ]);

        return response()->json($championship, 201);
    }

    public function show(Request $request, ChampionshipSingle $championshipSingle): JsonResponse
    {
        abort_unless($championshipSingle->user_id === $request->user()->id, 403, 'No autorizado');

        return response()->json($championshipSingle);
    }

    public function update(UpdateChampionshipSingleRequest $request, ChampionshipSingle $championshipSingle): JsonResponse
    {
        abort_unless($championshipSingle->user_id === $request->user()->id, 403, 'No autorizado');

        $data = $request->validated();
        if (isset($data['name']) && $data['name'] !== $championshipSingle->name) {
            $data['slug'] = $this->buildUniqueSlug($data['name']);
        }

        $championshipSingle->update($data);

        if (isset($data['slug'])) {
            PublicShareSetting::query()
                ->where('scope_type', 'single')
                ->where('scope_id', $championshipSingle->id)
                ->update(['public_slug' => $data['slug']]);
        }

        return response()->json($championshipSingle->fresh());
    }

    public function destroy(Request $request, ChampionshipSingle $championshipSingle): JsonResponse
    {
        abort_unless($championshipSingle->user_id === $request->user()->id, 403, 'No autorizado');

        $championshipSingle->delete();

        return response()->json(['message' => 'Campeonato eliminado correctamente.']);
    }

    private function buildUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'campeonato';
        $slug = $base;
        $counter = 1;

        while (ChampionshipSingle::query()->where('slug', $slug)->exists()) {
            $counter++;
            $slug = $base.'-'.$counter;
        }

        return $slug;
    }
}
