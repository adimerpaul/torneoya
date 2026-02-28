<?php

namespace App\Http\Requests;

class UpdateChampionshipSingleRequest extends StoreChampionshipSingleRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        foreach ($rules as $key => $value) {
            if ($key !== 'sport_id') {
                array_unshift($rules[$key], 'sometimes');
            }
        }
        $rules['sport_id'] = ['sometimes', 'required', 'integer', 'exists:sports,id'];

        return $rules;
    }
}
