<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\ClubTeam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            ClubTeam::query()
                ->where('owner_user_id', $request->user()->id)
                ->latest('id')
                ->paginate(20)
        );
    }

    public function store(StoreTeamRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['owner_user_id'] = $request->user()->id;

        $team = ClubTeam::query()->create($data);

        return response()->json($team, 201);
    }

    public function show(Request $request, ClubTeam $team): JsonResponse
    {
        abort_unless($team->owner_user_id === $request->user()->id, 403, 'No autorizado');

        return response()->json($team);
    }

    public function update(UpdateTeamRequest $request, ClubTeam $team): JsonResponse
    {
        abort_unless($team->owner_user_id === $request->user()->id, 403, 'No autorizado');

        $team->update($request->validated());

        return response()->json($team->fresh());
    }

    public function destroy(Request $request, ClubTeam $team): JsonResponse
    {
        abort_unless($team->owner_user_id === $request->user()->id, 403, 'No autorizado');

        $team->delete();

        return response()->json(['message' => 'Equipo eliminado correctamente.']);
    }
}
