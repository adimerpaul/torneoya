<?php

namespace App\Services;

use App\Models\ChampionshipCategory;
use App\Models\ChampionshipSingle;
use App\Models\MatchEvent;
use App\Models\MatchModel;
use App\Models\PointsScheme;
use App\Models\ScorerSnapshot;
use App\Models\StandingSnapshot;
use Carbon\Carbon;

class CompetitionStatsService
{
    public function recalculate(string $scopeType, int $scopeId): void
    {
        $points = $this->resolvePointsScheme($scopeType, $scopeId);
        $this->recalculateStandings($scopeType, $scopeId, $points);
        $this->recalculateScorers($scopeType, $scopeId);
    }

    private function recalculateStandings(string $scopeType, int $scopeId, array $points): void
    {
        $matches = MatchModel::query()
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('status', 'finished')
            ->get();

        $stats = [];
        foreach ($matches as $match) {
            $groupId = $match->group_id;
            $homeKey = $this->teamGroupKey((int) $match->home_team_id, $groupId);
            $awayKey = $this->teamGroupKey((int) $match->away_team_id, $groupId);

            if (! isset($stats[$homeKey])) {
                $stats[$homeKey] = $this->emptyStandingRow((int) $match->home_team_id, $groupId);
            }
            if (! isset($stats[$awayKey])) {
                $stats[$awayKey] = $this->emptyStandingRow((int) $match->away_team_id, $groupId);
            }

            $homeScore = (int) $match->home_score;
            $awayScore = (int) $match->away_score;
            $homeExtra = (int) $match->home_points_extra;
            $awayExtra = (int) $match->away_points_extra;

            $stats[$homeKey]['played']++;
            $stats[$awayKey]['played']++;
            $stats[$homeKey]['goals_for'] += $homeScore;
            $stats[$homeKey]['goals_against'] += $awayScore;
            $stats[$awayKey]['goals_for'] += $awayScore;
            $stats[$awayKey]['goals_against'] += $homeScore;

            if ($homeScore > $awayScore) {
                $stats[$homeKey]['won']++;
                $stats[$awayKey]['lost']++;
                $stats[$homeKey]['points'] += $points['win'] + $homeExtra;
                $stats[$awayKey]['points'] += $points['loss'] + $awayExtra;
            } elseif ($homeScore < $awayScore) {
                $stats[$homeKey]['lost']++;
                $stats[$awayKey]['won']++;
                $stats[$homeKey]['points'] += $points['loss'] + $homeExtra;
                $stats[$awayKey]['points'] += $points['win'] + $awayExtra;
            } else {
                $stats[$homeKey]['draw']++;
                $stats[$awayKey]['draw']++;
                $stats[$homeKey]['tied_matches']++;
                $stats[$awayKey]['tied_matches']++;
                $stats[$homeKey]['points'] += $points['draw'] + $homeExtra;
                $stats[$awayKey]['points'] += $points['draw'] + $awayExtra;
            }
        }

        StandingSnapshot::query()
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->delete();

        $now = Carbon::now();
        foreach ($stats as $row) {
            $maxScore = max(1, $row['played'] * max(1, $points['win']));
            $row['goal_diff'] = $row['goals_for'] - $row['goals_against'];
            $row['percentage'] = round(($row['points'] / $maxScore) * 100, 2);
            $row['scope_type'] = $scopeType;
            $row['scope_id'] = $scopeId;
            $row['updated_at'] = $now;
            StandingSnapshot::query()->create($row);
        }
    }

    private function recalculateScorers(string $scopeType, int $scopeId): void
    {
        $events = MatchEvent::query()
            ->join('matches', 'matches.id', '=', 'match_events.match_id')
            ->where('matches.scope_type', $scopeType)
            ->where('matches.scope_id', $scopeId)
            ->whereNotIn('matches.status', ['cancelled'])
            ->select('match_events.*')
            ->get();

        $rows = [];
        $playerMatchSet = [];

        foreach ($events as $event) {
            if (! $event->player_id || ! $event->team_id) {
                continue;
            }

            $playerId = (int) $event->player_id;
            $teamId = (int) $event->team_id;
            $key = $playerId.'-'.$teamId;

            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'player_id' => $playerId,
                    'team_id' => $teamId,
                    'goals' => 0,
                    'own_goals' => 0,
                    'yellow_cards' => 0,
                    'red_cards' => 0,
                    'matches_played' => 0,
                ];
            }

            if (in_array($event->event_type, ['goal', 'penalty_scored'], true)) {
                $rows[$key]['goals']++;
            }
            if ($event->event_type === 'own_goal') {
                $rows[$key]['own_goals']++;
            }
            if ($event->event_type === 'yellow_card') {
                $rows[$key]['yellow_cards']++;
            }
            if ($event->event_type === 'red_card') {
                $rows[$key]['red_cards']++;
            }

            $playerMatchSet[$key][$event->match_id] = true;
        }

        ScorerSnapshot::query()
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->delete();

        $now = Carbon::now();
        foreach ($rows as $key => $row) {
            $row['scope_type'] = $scopeType;
            $row['scope_id'] = $scopeId;
            $row['matches_played'] = count($playerMatchSet[$key] ?? []);
            $row['updated_at'] = $now;
            ScorerSnapshot::query()->create($row);
        }
    }

    private function resolvePointsScheme(string $scopeType, int $scopeId): array
    {
        $pointsSchemeId = null;
        if ($scopeType === 'single') {
            $pointsSchemeId = ChampionshipSingle::query()->find($scopeId)?->points_scheme_id;
        } elseif ($scopeType === 'category') {
            $pointsSchemeId = ChampionshipCategory::query()->find($scopeId)?->points_scheme_id;
        }

        $scheme = $pointsSchemeId ? PointsScheme::query()->find($pointsSchemeId) : null;

        return [
            'win' => (int) ($scheme->points_win ?? 3),
            'draw' => (int) ($scheme->points_draw ?? 1),
            'loss' => (int) ($scheme->points_loss ?? 0),
        ];
    }

    private function emptyStandingRow(int $teamId, ?int $groupId): array
    {
        return [
            'group_id' => $groupId,
            'team_id' => $teamId,
            'played' => 0,
            'won' => 0,
            'draw' => 0,
            'lost' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_diff' => 0,
            'points' => 0,
            'percentage' => 0,
            'tied_matches' => 0,
            'fair_play_points' => 0,
        ];
    }

    private function teamGroupKey(int $teamId, ?int $groupId): string
    {
        return ($groupId ?? 0).'-'.$teamId;
    }
}
