<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChampionshipSingleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'string', 'max:255'],
            'cover' => ['nullable', 'string', 'max:255'],
            'sport_id' => ['required', 'integer', 'exists:sports,id'],
            'format_default_id' => ['nullable', 'integer', 'exists:competition_formats,id'],
            'points_scheme_id' => ['nullable', 'integer', 'exists:points_schemes,id'],
            'tiebreak_rule_id' => ['nullable', 'integer', 'exists:tiebreak_rules,id'],
            'start_date' => ['nullable', 'date'],
            'inscription_deadline' => ['nullable', 'date'],
            'color_primary' => ['nullable', 'string', 'max:20'],
            'color_secondary' => ['nullable', 'string', 'max:20'],
            'rules_text' => ['nullable', 'string'],
            'prizes_text' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,published,in_progress,finished,cancelled'],
        ];
    }
}
