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
            'billing_address' => 'max:255|nullable',
            'billing_address_optional' => 'nullable',
            'billing_country' => 'max:255|nullable',
            'billing_state' => 'max:255|nullable',
            'billing_city' => 'max:255|nullable',
            'billing_postal_code' => 'max:255|nullable',
            'billing_currency' => 'max:255|nullable',
            'shipto' => 'max:255|nullable',
            'shipping_address' => 'max:255|nullable',
            'shipping_address_optional' => 'nullable',
            'shipping_country' => 'max:255|nullable',
            'shipping_state' => 'max:255|nullable',
            'shipping_city' => 'max:255|nullable',
            'shipping_postal_code' => 'max:255|nullable',
            'shipping_phone' => 'max:255|nullable',
            'shipping_delivery_instructions' => 'max:1000|nullable',
            'accnumber' => 'max:255|nullable',
            'website' => 'max:255|nullable',
            'note' => 'max:1000|nullable',
            'personal_phone_optional' => 'nullable',
        ];
    }
}
