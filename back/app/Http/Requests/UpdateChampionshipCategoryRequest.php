<?php

namespace App\Http\Requests;

class UpdateChampionshipCategoryRequest extends StoreChampionshipCategoryRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        foreach ($rules as $key => $value) {
            array_unshift($rules[$key], 'sometimes');
        }

        return $rules;
    }
}
