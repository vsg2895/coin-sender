<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CoinTypeUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|min:1',
            'rpc_url' => 'required|string|min:1',
            'chain_id' => 'required|integer|min:1',
            'type_of_chain' => 'required|string|min:1',
            'block_explorer_url' => 'required|string|min:1',
        ];
    }
}
