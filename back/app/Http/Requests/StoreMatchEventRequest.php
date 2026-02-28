<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMatchEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_id' => ['nullable', 'integer', 'exists:club_teams,id'],
            'player_id' => ['nullable', 'integer', 'exists:players,id'],
            'related_player_id' => ['nullable', 'integer', 'exists:players,id'],
            'event_type' => ['required', 'string', 'in:goal,own_goal,yellow_card,red_card,foul,substitution,comment,status_change,penalty_scored,penalty_missed'],
            'minute' => ['nullable', 'integer', 'min:0', 'max:300'],
            'period_type' => ['nullable', 'string', 'max:20'],
            'payload_json' => ['nullable', 'array'],
        ];
    }
}
