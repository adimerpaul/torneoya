<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'home_score' => ['required', 'integer', 'min:0'],
            'away_score' => ['required', 'integer', 'min:0'],
            'home_points_extra' => ['nullable', 'integer'],
            'away_points_extra' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:scheduled,live,paused,finished,postponed,cancelled'],
            'comments_public' => ['nullable', 'string'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
