<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChampionshipMultiRequest extends FormRequest
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
