<?php

namespace App\Http\Requests;

class UpdateMatchRequest extends StoreMatchRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        foreach ($rules as $field => $ruleSet) {
            $rules[$field] = array_merge(['sometimes'], $ruleSet);
        }

        return $rules;
    }
}
