<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scope_type' => ['required', 'string', 'in:single,category'],
            'scope_id' => ['required', 'integer', 'min:1'],
            'stage_id' => ['nullable', 'integer', 'exists:competition_stages,id'],
            'group_id' => ['nullable', 'integer'],
            'home_team_id' => ['required', 'integer', 'exists:club_teams,id', 'different:away_team_id'],
            'away_team_id' => ['required', 'integer', 'exists:club_teams,id'],
            'scheduled_at' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'referee_main' => ['nullable', 'string', 'max:255'],
            'referee_assistants_json' => ['nullable', 'array'],
            'status' => ['nullable', 'in:scheduled,live,paused,finished,postponed,cancelled'],
            'home_score' => ['nullable', 'integer', 'min:0'],
            'away_score' => ['nullable', 'integer', 'min:0'],
            'home_points_extra' => ['nullable', 'integer'],
            'away_points_extra' => ['nullable', 'integer'],
            'comments_public' => ['nullable', 'string'],
        ];
    }
}
