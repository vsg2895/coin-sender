<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AmbassadorAutocompleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'search' => 'string|min:1|nullable',
            'min_level' => 'integer|min:0|nullable',
            'max_level' => 'integer|min:0|nullable',
            'activity_id' => 'integer|exists:activities,id',
        ];
    }
}
