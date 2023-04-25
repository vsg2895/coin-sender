<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
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
            'name' => 'required|string|min:1',
            'country_id' => 'required|integer|exists:countries,id',
            'languages' => 'required|array',
            'languages.*.id' => 'integer|exists:manager_languages,id',
            'languages.*.language_id' => 'required|integer|exists:languages,id',
        ];
    }
}
