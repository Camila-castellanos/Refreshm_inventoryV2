<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerForm extends FormRequest
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
            'customer_name' => 'required|max:255',
            'first_name' => 'max:255|nullable',
            'last_name' => 'max:255|nullable',
            'email' => 'max:255|nullable',
            'personal_phone' => 'max:255|nullable',
            'credit' => 'numeric',
        ];
    }
}
