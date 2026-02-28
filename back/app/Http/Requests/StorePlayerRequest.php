<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:150'],
            'last_name' => ['required', 'string', 'max:150'],
            'document_type' => ['nullable', 'string', 'max:50'],
            'document_number' => ['nullable', 'string', 'max:100', 'unique:players,document_number'],
            'birthdate' => ['nullable', 'date'],
            'photo' => ['nullable', 'string', 'max:255'],
            'dominant_foot' => ['nullable', 'string', 'max:50'],
            'position_default' => ['nullable', 'string', 'max:100'],
        ];
    }
}
