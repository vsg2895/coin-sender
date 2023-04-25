<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AutomationConnectTelegramRequest extends FormRequest
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
            'chat_id' => [
                'required',
                'string',
                'regex:/^(-100[0-9]+)|(@[a-zA-Z0-9_-]*)/i',
            ],
            'project_id' => 'required|integer|exists:projects,id',
        ];
    }
}
