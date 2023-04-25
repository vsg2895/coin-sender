<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectDiscordUpdateRequest extends FormRequest
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
            'newTask.active' => 'required|boolean',
            'newTask.channelId' => 'present|nullable|string',
            'newProject.active' => 'required|boolean',
            'newProject.channelId' => 'present|nullable|string',
            'completedTask.active' => 'required|boolean',
            'completedTask.channelId' => 'present|nullable|string',
        ];
    }
}
