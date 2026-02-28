<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMatchEventRequest;
use App\Http\Requests\StoreMatchMediaRequest;
use App\Models\ChampionshipCategory;
use App\Models\ChampionshipMulti;
use App\Models\ChampionshipSingle;
use App\Models\MatchEvent;
use App\Models\MatchMedia;
use App\Models\MatchModel;
use App\Services\CompetitionStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchEventController extends Controller
{
    public function __construct(private readonly CompetitionStatsService $statsService) {}

    public function index(Request $request, MatchModel $match): JsonResponse
    {
        $this->assertMatchOwnership($match, $request->user()->id);

        return response()->json(
            MatchEvent::query()
                ->where('match_id', $match->id)
                ->orderBy('minute')
                ->orderBy('id')
                ->get()
        );
    }

    public function store(StoreMatchEventRequest $request, MatchModel $match): JsonResponse
    {
        $this->assertMatchOwnership($match, $request->user()->id);
        $data = $request->validated();
        $data['match_id'] = $match->id;
        $data['created_by'] = $request->user()->id;

        $event = MatchEvent::query()->create($data);

        $this->applyEventImpactToMatch($match, $event);
        $this->statsService->recalculate($match->scope_type, (int) $match->scope_id);

        return response()->json($event, 201);
    }

    public function media(Request $request, MatchModel $match): JsonResponse
    {
        $this->assertMatchOwnership($match, $request->user()->id);

        return response()->json(
            MatchMedia::query()
                ->where('match_id', $match->id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        );
    }

    public function storeMedia(StoreMatchMediaRequest $request, MatchModel $match): JsonResponse
    {
        $this->assertMatchOwnership($match, $request->user()->id);
        $data = $request->validated();
        $data['match_id'] = $match->id;
        $data['media_type'] = $data['media_type'] ?? 'photo';
        $data['uploaded_by'] = $request->user()->id;

        $media = MatchMedia::query()->create($data);

        return response()->json($media, 201);
    }

    private function applyEventImpactToMatch(MatchModel $match, MatchEvent $event): void
    {
        $homeScore = (int) $match->home_score;
        $awayScore = (int) $match->away_score;

        if (in_array($event->event_type, ['goal', 'penalty_scored'], true) && $event->team_id) {
            if ((int) $event->team_id === (int) $match->home_team_id) {
                $homeScore++;
            } elseif ((int) $event->team_id === (int) $match->away_team_id) {
                $awayScore++;
            }
        }

        if ($event->event_type === 'own_goal' && $event->team_id) {
            if ((int) $event->team_id === (int) $match->home_team_id) {
                $awayScore++;
            } elseif ((int) $event->team_id === (int) $match->away_team_id) {
                $homeScore++;
            }
        }

        $updates = [];
        if ($homeScore !== (int) $match->home_score || $awayScore !== (int) $match->away_score) {
            $updates['home_score'] = $homeScore;
            $updates['away_score'] = $awayScore;
        }

        if ($event->event_type === 'status_change') {
            $status = $event->payload_json['status'] ?? null;
            if (is_string($status) && in_array($status, ['scheduled', 'live', 'paused', 'finished', 'postponed', 'cancelled'], true)) {
                $updates['status'] = $status;
            }
        }

        if ($updates !== []) {
            $match->update($updates);
        }
    }

    private function assertMatchOwnership(MatchModel $match, int $userId): void
    {
        if ($match->scope_type === 'single') {
            $single = ChampionshipSingle::query()->findOrFail($match->scope_id);
            abort_unless($single->user_id === $userId, 403, 'No autorizado');

            return;
        }

        if ($match->scope_type === 'category') {
            $category = ChampionshipCategory::query()->findOrFail($match->scope_id);
            $multi = ChampionshipMulti::query()->findOrFail($category->championship_multi_id);
            abort_unless($multi->user_id === $userId, 403, 'No autorizado');

            return;
        }

        abort(422, 'scope_type no valido');
    }
}
