<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemForm extends FormRequest
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
            "items" => "required|array|min:1",
            "items.*.supplier" => "sometimes",
            "items.*.manufacturer" => "sometimes",
            "items.*.model" => "sometimes",
            "items.*.issues" => "sometimes",
            "items.*.colour" => "sometimes",
            "items.*.vendor_id" => "sometimes",
            "items.*.battery" => "sometimes",
            "items.*.grade" => "sometimes",
            "items.*.cost" => "sometimes",
            "items.*.imei" => "sometimes",
            "items.*.date" => "sometimes",
            "items.*.selling_price" => "sometimes",
            // "items.*.storage_id" => "required",
        ];
    }
}
