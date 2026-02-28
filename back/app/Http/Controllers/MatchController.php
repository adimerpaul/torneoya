<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMatchRequest;
use App\Http\Requests\UpdateMatchRequest;
use App\Http\Requests\UpdateMatchResultRequest;
use App\Models\ChampionshipCategory;
use App\Models\ChampionshipMulti;
use App\Models\ChampionshipSingle;
use App\Models\ClubTeam;
use App\Models\MatchModel;
use App\Models\MatchResultRevision;
use App\Models\Player;
use App\Models\ScorerSnapshot;
use App\Models\StandingSnapshot;
use App\Services\CompetitionStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function __construct(private readonly CompetitionStatsService $statsService) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope_type' => ['required', 'in:single,category'],
            'scope_id' => ['required', 'integer', 'min:1'],
        ]);

        $this->assertOwnership((string) $validated['scope_type'], (int) $validated['scope_id'], $request->user()->id);

        return response()->json(
            MatchModel::query()
                ->where('scope_type', $validated['scope_type'])
                ->where('scope_id', $validated['scope_id'])
                ->latest('id')
                ->paginate(30)
        );
    }

    public function store(StoreMatchRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->assertOwnership((string) $data['scope_type'], (int) $data['scope_id'], $request->user()->id);

        $data['status'] = $data['status'] ?? 'scheduled';
        $data['home_score'] = $data['home_score'] ?? 0;
        $data['away_score'] = $data['away_score'] ?? 0;
        $data['home_points_extra'] = $data['home_points_extra'] ?? 0;
        $data['away_points_extra'] = $data['away_points_extra'] ?? 0;

        $match = MatchModel::query()->create($data);

        if ($match->status === 'finished') {
            $this->statsService->recalculate($match->scope_type, (int) $match->scope_id);
        }

        return response()->json($match, 201);
    }

    public function show(Request $request, MatchModel $match): JsonResponse
    {
        $this->assertMatchOwnership($match, $request->user()->id);

        return response()->json($match);
    }

    public function update(UpdateMatchRequest $request, MatchModel $match): JsonResponse
    {
        $this->assertMatchOwnership($match, $request->user()->id);

        $data = $request->validated();
        if (isset($data['scope_type']) || isset($data['scope_id'])) {
            $scopeType = (string) ($data['scope_type'] ?? $match->scope_type);
            $scopeId = (int) ($data['scope_id'] ?? $match->scope_id);
            $this->assertOwnership($scopeType, $scopeId, $request->user()->id);
        }

        $beforeStatus = $match->status;
        $match->update($data);

        if ($beforeStatus === 'finished' || $match->status === 'finished') {
            $this->statsService->recalculate($match->scope_type, (int) $match->scope_id);
        }

        return response()->json($match->fresh());
    }

    public function destroy(Request $request, MatchModel $match): JsonResponse
    {
        $this->assertMatchOwnership($match, $request->user()->id);

        $scopeType = $match->scope_type;
        $scopeId = (int) $match->scope_id;
        $wasFinished = $match->status === 'finished';

        $match->delete();

        if ($wasFinished) {
            $this->statsService->recalculate($scopeType, $scopeId);
        }

        return response()->json(['message' => 'Partido eliminado correctamente.']);
    }

    public function updateResult(UpdateMatchResultRequest $request, MatchModel $match): JsonResponse
    {
        $this->assertMatchOwnership($match, $request->user()->id);
        $data = $request->validated();

        $oldHome = (int) $match->home_score;
        $oldAway = (int) $match->away_score;
        $newHome = (int) $data['home_score'];
        $newAway = (int) $data['away_score'];

        $match->update([
            'home_score' => $newHome,
            'away_score' => $newAway,
            'home_points_extra' => $data['home_points_extra'] ?? $match->home_points_extra,
            'away_points_extra' => $data['away_points_extra'] ?? $match->away_points_extra,
            'status' => $data['status'] ?? $match->status,
            'comments_public' => $data['comments_public'] ?? $match->comments_public,
        ]);

        if ($oldHome !== $newHome || $oldAway !== $newAway) {
            MatchResultRevision::query()->create([
                'match_id' => $match->id,
                'old_home_score' => $oldHome,
                'old_away_score' => $oldAway,
                'new_home_score' => $newHome,
                'new_away_score' => $newAway,
                'reason' => $data['reason'] ?? null,
                'changed_by' => $request->user()->id,
            ]);
        }

        $this->statsService->recalculate($match->scope_type, (int) $match->scope_id);

        return response()->json($match->fresh());
    }

    public function standings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope_type' => ['required', 'in:single,category'],
            'scope_id' => ['required', 'integer', 'min:1'],
        ]);

        $scopeType = (string) $validated['scope_type'];
        $scopeId = (int) $validated['scope_id'];
        $this->assertOwnership($scopeType, $scopeId, $request->user()->id);

        $rows = StandingSnapshot::query()
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->orderByDesc('points')
            ->orderByDesc('goal_diff')
            ->orderByDesc('goals_for')
            ->get();
        $teamIds = $rows->pluck('team_id')->unique()->values()->all();
        $teams = ClubTeam::query()->whereIn('id', $teamIds)->get()->keyBy('id');

        return response()->json($rows->map(fn ($row) => array_merge($row->toArray(), [
            'team' => $teams->get($row->team_id),
        ])));
    }

    public function scorers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scope_type' => ['required', 'in:single,category'],
            'scope_id' => ['required', 'integer', 'min:1'],
        ]);

        $scopeType = (string) $validated['scope_type'];
        $scopeId = (int) $validated['scope_id'];
        $this->assertOwnership($scopeType, $scopeId, $request->user()->id);

        $rows = ScorerSnapshot::query()
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->orderByDesc('goals')
            ->orderBy('id')
            ->get();
        $teamIds = $rows->pluck('team_id')->unique()->values()->all();
        $playerIds = $rows->pluck('player_id')->unique()->values()->all();
        $teams = ClubTeam::query()->whereIn('id', $teamIds)->get()->keyBy('id');
        $players = Player::query()->whereIn('id', $playerIds)->get()->keyBy('id');

        return response()->json($rows->map(fn ($row) => array_merge($row->toArray(), [
            'team' => $teams->get($row->team_id),
            'player' => $players->get($row->player_id),
        ])));
    }

    private function assertMatchOwnership(MatchModel $match, int $userId): void
    {
        $this->assertOwnership($match->scope_type, (int) $match->scope_id, $userId);
    }

    private function assertOwnership(string $scopeType, int $scopeId, int $userId): void
    {
        if ($scopeType === 'single') {
            $single = ChampionshipSingle::query()->findOrFail($scopeId);
            abort_unless($single->user_id === $userId, 403, 'No autorizado');

            return;
        }

        if ($scopeType === 'category') {
            $category = ChampionshipCategory::query()->findOrFail($scopeId);
            $multi = ChampionshipMulti::query()->findOrFail($category->championship_multi_id);
            abort_unless($multi->user_id === $userId, 403, 'No autorizado');

            return;
        }

        abort(422, 'scope_type no valido');
    }
}
