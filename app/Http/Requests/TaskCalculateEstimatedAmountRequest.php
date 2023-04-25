<?php

namespace App\Http\Requests;

use App\Rules\ValidUserForTaskRule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskCalculateEstimatedAmountRequest extends FormRequest
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
            'rewards' => 'required|array|min:1',
            'rewards.*.type' => [
                Rule::in(config('reward.types')),
                'distinct:ignore_case',
            ],
            'rewards.*.value' => 'required|string|min:1',
            'min_level' => 'integer|min:0|nullable',
            'max_level' => 'integer|min:0|nullable|gte:min_level',
            'activity_id' => 'integer|exists:activities,id|nullable',
            'assign_user_ids' => 'array',
            'assign_user_ids.*' => [
                'integer',
                new ValidUserForTaskRule($this->min_level, $this->max_level, $this->activity_id),
            ],
            'level_coefficient' => 'boolean',
            'number_of_participants' => 'integer|min:1',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if (!empty($this->assign_user_ids)) {
            $this->offsetUnset('number_of_participants');
        }

        $this->merge([
            'max_level' => !$this->has('max_level')
                ? $this->get('min_level')
                : $this->get('max_level'),
            'level_coefficient' => filter_var($this->level_coefficient, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }
}
