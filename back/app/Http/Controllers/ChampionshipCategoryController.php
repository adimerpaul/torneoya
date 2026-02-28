<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChampionshipCategoryRequest;
use App\Http\Requests\UpdateChampionshipCategoryRequest;
use App\Models\ChampionshipCategory;
use App\Models\ChampionshipMulti;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChampionshipCategoryController extends Controller
{
    public function index(ChampionshipMulti $championshipMulti, Request $request): JsonResponse
    {
        abort_unless($championshipMulti->user_id === $request->user()->id, 403, 'No autorizado');

        return response()->json(
            ChampionshipCategory::query()
                ->where('championship_multi_id', $championshipMulti->id)
                ->latest('id')
                ->get()
        );
    }

    public function store(StoreChampionshipCategoryRequest $request, ChampionshipMulti $championshipMulti): JsonResponse
    {
        abort_unless($championshipMulti->user_id === $request->user()->id, 403, 'No autorizado');

        $data = $request->validated();
        $data['championship_multi_id'] = $championshipMulti->id;
        $data['status'] = $data['status'] ?? 'draft';

        $category = ChampionshipCategory::query()->create($data);

        return response()->json($category, 201);
    }

    public function show(Request $request, ChampionshipCategory $category): JsonResponse
    {
        $multi = ChampionshipMulti::query()->findOrFail($category->championship_multi_id);
        abort_unless($multi->user_id === $request->user()->id, 403, 'No autorizado');

        return response()->json($category);
    }

    public function update(UpdateChampionshipCategoryRequest $request, ChampionshipCategory $category): JsonResponse
    {
        $multi = ChampionshipMulti::query()->findOrFail($category->championship_multi_id);
        abort_unless($multi->user_id === $request->user()->id, 403, 'No autorizado');

        $category->update($request->validated());

        return response()->json($category->fresh());
    }

    public function destroy(Request $request, ChampionshipCategory $category): JsonResponse
    {
        $multi = ChampionshipMulti::query()->findOrFail($category->championship_multi_id);
        abort_unless($multi->user_id === $request->user()->id, 403, 'No autorizado');

        $category->delete();

        return response()->json(['message' => 'Categoria eliminada correctamente.']);
    }
}
