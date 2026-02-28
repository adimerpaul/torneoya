<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChampionshipCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sport_id' => ['required', 'integer', 'exists:sports,id'],
            'name' => ['required', 'string', 'max:255'],
            'format_default_id' => ['nullable', 'integer', 'exists:competition_formats,id'],
            'points_scheme_id' => ['nullable', 'integer', 'exists:points_schemes,id'],
            'tiebreak_rule_id' => ['nullable', 'integer', 'exists:tiebreak_rules,id'],
            'status' => ['nullable', 'in:draft,published,in_progress,finished,cancelled'],
        ];
    }
}
