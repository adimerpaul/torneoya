<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlayerRequest;
use App\Http\Requests\UpdatePlayerRequest;
use App\Models\Player;
use Illuminate\Http\JsonResponse;

class PlayerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Player::query()
                ->latest('id')
                ->paginate(20)
        );
    }

    public function store(StorePlayerRequest $request): JsonResponse
    {
        $player = Player::query()->create($request->validated());

        return response()->json($player, 201);
    }

    public function show(Player $player): JsonResponse
    {
        return response()->json($player);
    }

    public function update(UpdatePlayerRequest $request, Player $player): JsonResponse
    {
        $player->update($request->validated());

        return response()->json($player->fresh());
    }

    public function destroy(Player $player): JsonResponse
    {
        $player->delete();

        return response()->json(['message' => 'Jugador eliminado correctamente.']);
    }
}
