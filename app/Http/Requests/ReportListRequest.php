<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'per_page' => 'integer|min:1',
            'order_by' => 'in:asc,desc',
            'project_id' => 'integer|exists:projects,id',
        ];
    }
}
