<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MyTeamStoreRequest extends FormRequest
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
            'email' => [
                'nullable',
                'email',
                Rule::notIn(config('app.admin_email')),
            ],
            'role_name' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where(function ($query) {
                    $query->whereNotIn('name', ['Project Owner', 'Super Admin']);
                }),
            ],
            'manager_id' => 'integer|exists:managers,id',
        ];
    }
}
