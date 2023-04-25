<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectUpdateRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'min:3',
                'max:43',
                Rule::unique('projects')->ignore($this->project->id),
            ],
            'logo' => 'mimes:jpg,jpeg,png|min:10,max:10000',
            'banner' => 'mimes:jpg,jpeg,png|min:10,max:10000',
            'public' => 'required|boolean',
            'description' => 'required|string|min:60|max:500',
            'pool_amount' => 'required|numeric',
            'coin_type_id' => 'required|integer|exists:coin_types,id',
            'blockchain_id' => 'required|integer|exists:blockchains,id',
            'medium_username' => 'nullable|string|min:1',
            'tags' => 'required|array|min:1|max:10',
            'tags.*.id' => 'integer|exists:project_tags,id',
            'tags.*.tag_id' => 'required|integer|exists:tags,id',
            'social_links' => 'required|array|min:1',
            'social_links.*.id' => 'integer|exists:project_social_links,id',
            'social_links.*.content' => 'nullable|url',
            'social_links.*.social_link_id' => 'required|integer|exists:social_links,id',
        ];
    }

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'public' => filter_var($this->public, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }
}
