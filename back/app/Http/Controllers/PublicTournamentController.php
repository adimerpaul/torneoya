<?php

namespace App\Http\Controllers;

use App\Models\ChampionshipCategory;
use App\Models\ChampionshipMulti;
use App\Models\ChampionshipSingle;
use App\Models\ClubTeam;
use App\Models\MatchModel;
use App\Models\Player;
use App\Models\PublicShareSetting;
use App\Models\ScorerSnapshot;
use App\Models\StandingSnapshot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicTournamentController extends Controller
{
    public function overview(string $slug): JsonResponse
    {
        $share = PublicShareSetting::query()->where('public_slug', $slug)->firstOrFail();
        abort_unless($share->is_public, 404);

        if ($share->scope_type === 'single') {
            $scope = ChampionshipSingle::query()->findOrFail($share->scope_id);

            return response()->json(['type' => 'single', 'championship' => $scope]);
        }

        if ($share->scope_type === 'multi') {
            $scope = ChampionshipMulti::query()->findOrFail($share->scope_id);
            $categories = ChampionshipCategory::query()->where('championship_multi_id', $scope->id)->get();

            return response()->json(['type' => 'multi', 'championship' => $scope, 'categories' => $categories]);
        }

        abort(404);
    }

    public function matches(Request $request, string $slug): JsonResponse
    {
        [$share, $scopeType, $scopeId] = $this->resolvePublicScope($request, $slug);

        $query = MatchModel::query()
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->orderBy('scheduled_at')
            ->orderBy('id');

        if (! $share->show_comments) {
            $query->select([
                'id',
                'scope_type',
                'scope_id',
                'group_id',
                'home_team_id',
                'away_team_id',
                'scheduled_at',
                'venue',
                'status',
                'home_score',
                'away_score',
            ]);
        }

        return response()->json($query->get());
    }

    public function standings(Request $request, string $slug): JsonResponse
    {
        [, $scopeType, $scopeId] = $this->resolvePublicScope($request, $slug);

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

    public function scorers(Request $request, string $slug): JsonResponse
    {
        [$share, $scopeType, $scopeId] = $this->resolvePublicScope($request, $slug);
        abort_unless($share->show_player_stats, 404);

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

    private function resolvePublicScope(Request $request, string $slug): array
    {
        $share = PublicShareSetting::query()->where('public_slug', $slug)->firstOrFail();
        abort_unless($share->is_public, 404);

        if ($share->scope_type === 'single') {
            ChampionshipSingle::query()->findOrFail($share->scope_id);

            return [$share, 'single', (int) $share->scope_id];
        }

        if ($share->scope_type === 'multi') {
            $multi = ChampionshipMulti::query()->findOrFail($share->scope_id);
            $categoryId = (int) $request->query('category_id', 0);
            abort_unless($categoryId > 0, 422, 'category_id es requerido para campeonatos multi');

            $category = ChampionshipCategory::query()->findOrFail($categoryId);
            abort_unless((int) $category->championship_multi_id === (int) $multi->id, 404);

            return [$share, 'category', $categoryId];
        }

        abort(404);
    }
}
