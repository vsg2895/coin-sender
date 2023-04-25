<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AmbassadorListRequest extends FormRequest
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
            'search' => 'string|min:1',
            'per_page' => 'integer|min:1',
            'register_date' => 'integer',
        ];
    }
}
