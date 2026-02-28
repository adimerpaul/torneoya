<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChampionshipMultiRequest;
use App\Http\Requests\UpdateChampionshipMultiRequest;
use App\Models\ChampionshipMulti;
use App\Models\PublicShareSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChampionshipMultiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            ChampionshipMulti::query()
                ->where('user_id', $request->user()->id)
                ->latest('id')
                ->paginate(20)
        );
    }

    public function store(StoreChampionshipMultiRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['slug'] = $this->buildUniqueSlug($data['name']);
        $data['status'] = $data['status'] ?? 'draft';

        $championship = ChampionshipMulti::query()->create($data);

        PublicShareSetting::query()->create([
            'scope_type' => 'multi',
            'scope_id' => $championship->id,
            'is_public' => false,
            'public_slug' => $championship->slug,
        ]);

        return response()->json($championship, 201);
    }

    public function show(Request $request, ChampionshipMulti $championshipMulti): JsonResponse
    {
        abort_unless($championshipMulti->user_id === $request->user()->id, 403, 'No autorizado');

        return response()->json($championshipMulti);
    }

    public function update(UpdateChampionshipMultiRequest $request, ChampionshipMulti $championshipMulti): JsonResponse
    {
        abort_unless($championshipMulti->user_id === $request->user()->id, 403, 'No autorizado');

        $data = $request->validated();
        if (isset($data['name']) && $data['name'] !== $championshipMulti->name) {
            $data['slug'] = $this->buildUniqueSlug($data['name']);
        }

        $championshipMulti->update($data);

        if (isset($data['slug'])) {
            PublicShareSetting::query()
                ->where('scope_type', 'multi')
                ->where('scope_id', $championshipMulti->id)
                ->update(['public_slug' => $data['slug']]);
        }

        return response()->json($championshipMulti->fresh());
    }

    public function destroy(Request $request, ChampionshipMulti $championshipMulti): JsonResponse
    {
        abort_unless($championshipMulti->user_id === $request->user()->id, 403, 'No autorizado');

        $championshipMulti->delete();

        return response()->json(['message' => 'Campeonato eliminado correctamente.']);
    }

    private function buildUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'campeonato';
        $slug = $base;
        $counter = 1;

        while (ChampionshipMulti::query()->where('slug', $slug)->exists()) {
            $counter++;
            $slug = $base.'-'.$counter;
        }

        return $slug;
    }
}
