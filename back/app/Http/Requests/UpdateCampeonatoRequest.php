<?php

namespace App\Http\Requests;

class UpdateCampeonatoRequest extends StoreCampeonatoRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        foreach ($rules as $key => $ruleSet) {
            $rules[$key] = array_merge(['sometimes'], $ruleSet);
        }

        return $rules;
    }
}
