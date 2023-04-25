<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MyTeamUpdateRequest extends FormRequest
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
            'role_name' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where(function ($query) {
                    $query->whereNotIn('name', ['Project Owner', 'Super Admin']);
                }),
            ],
            'manager_id' => [
                'integer',
                Rule::exists('managers', 'id')->where(function ($query) {
                    $query->where('email', '!=', config('app.admin_email'));
                }),
            ],
        ];
    }
}
