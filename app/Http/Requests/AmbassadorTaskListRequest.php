<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AmbassadorTaskListRequest extends FormRequest
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
            'status' => 'in:done,rejected,on_revision,waiting_for_review',
            'per_page' => 'integer|min:1',
            'from_date' => 'integer',
            'to_date' => 'integer',
        ];
    }
}
