<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AmbassadorLeaderboardRequest extends FormRequest
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
            'levels' => 'array',
            'levels.*' => 'integer|min:1',
            'per_page' => 'integer|min:1',
            'activity_id' => 'integer|exists:activities,id',
            'order_by_total_points' => 'in:asc,desc',
        ];
    }
}
