<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectCreateRequest extends FormRequest
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
            'name' => 'required_if:fulfill,1|string|min:3|max:43|unique:projects,name',
            'logo' => 'mimes:jpg,jpeg,png|min:10,max:10000',
            'banner' => 'mimes:jpg,jpeg,png|min:10,max:10000',
            'public' => 'required|boolean',
            'fulfill' => 'required|boolean',
            'owner_email' => 'required|email|unique:managers,email',
            'description' => 'required_if:fulfill,1|string|min:60|max:500',
            'pool_amount' => 'required_if:fulfill,1|numeric',
            'coin_type_id' => 'required_if:fulfill,1|integer|exists:coin_types,id',
            'blockchain_id' => 'required_if:fulfill,1|integer|exists:blockchains,id',
            'medium_username' => 'required_if:fulfill,1|string|min:1',
            'tags' => 'required_if:fulfill,1|array|min:1|max:10',
            'tags.*' => 'required|integer|exists:tags,id',
            'social_links' => 'required_if:fulfill,1|array|min:1',
            'social_links.*.id' => 'required|integer|exists:social_links,id',
            'social_links.*.content' => 'required|url',
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
            'fulfill' => filter_var($this->fulfill, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }
}
