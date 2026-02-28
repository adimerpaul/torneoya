<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdatePlayerRequest extends StorePlayerRequest
{
    public function rules(): array
    {
        $playerId = $this->route('player')?->id ?? $this->route('player');

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:150'],
            'last_name' => ['sometimes', 'required', 'string', 'max:150'],
            'document_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'document_number' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('players', 'document_number')->ignore($playerId)],
            'birthdate' => ['sometimes', 'nullable', 'date'],
            'photo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dominant_foot' => ['sometimes', 'nullable', 'string', 'max:50'],
            'position_default' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
