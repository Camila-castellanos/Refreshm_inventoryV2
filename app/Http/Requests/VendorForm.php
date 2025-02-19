<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorForm extends FormRequest
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
            'vendor' => 'required|max:255',
            'first_name' => 'max:255',
            'last_name' => 'max:255',
            'email' => 'max:255',
            'phone' => 'max:255',
            'phone_optional' => 'max:255',
            'website' => 'nullable',
            'currency' => 'nullable',
            'address' => 'nullable',
            'address_optional' => 'nullable',
            'address_state' => 'nullable',
            'address_city' => 'nullable',
            'address_country' => 'nullable',
            'address_postal' => 'nullable',

        ];
    }
}
