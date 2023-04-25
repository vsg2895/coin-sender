<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password as PasswordRule;

class RegisterRequest extends FormRequest
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
            'name' => 'required|regex:/^[a-zA-Z0-9\s]+$/|min:3|max:29|unique:managers,name',
            'email' => 'required|email',
            'password' => ['required', PasswordRule::default(), 'confirmed'],
            'country_id' => 'required|integer|exists:countries,id',
            'languages' => 'required|array',
            'languages.*' => 'required|integer|exists:languages,id',
            'social_links' => 'required|array',
            'social_links.*.id' => 'required|integer|exists:social_links,id',
            'social_links.*.content' => 'required|string|min:1',
        ];
    }
}
