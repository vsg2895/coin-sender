<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AccessStoreRequest extends FormRequest
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
            'email' => 'email',
            'role_name' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where(function ($query) {
                    $user = auth()->user();
                    if ($user->hasRole('Catapult Manager')) {
                        $query->whereNotIn('name', [
                            'Super Admin',
                            'Project Owner',
                            'Project Administrator',
                            'Catapult Manager',
                        ]);
                    } else {
                        $query->where('name', '!=', 'Super Admin');
                    }
                }),
            ],
            'manager_id' => 'integer|exists:managers,id',
            'project_id' => 'nullable|integer|exists:projects,id',
        ];
    }
}
