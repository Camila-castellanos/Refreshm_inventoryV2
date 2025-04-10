<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleForm extends FormRequest
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
            "subtotal" => "required|numeric",
            "discount" => "required|numeric",
            "flatTax" => "required|numeric",
            "paid" => "required|numeric",
            "payment_method" => "string|nullable",
            "payment_account" => "string|nullable",
            "notes" => "string|nullable",
            "tax" => "required|numeric",
            "newItems" => "array|nullable",
            "total" => "required|numeric",
            "balance_remaining" => "required|numeric",
            "amount_paid" => "required|numeric",
            "items" => "required_without:newItems|array",
            "items.*.id" => "required|exists:items,id",
            "items.*.sold" => "required|date",
            "items.*.customer" => "required",
            "items.*.profit" => "required|numeric",
            "items.*.position" => "numeric|nullable",
            "items.*.storage_id" => "exists:storages,id|nullable",
            "tax_id" => "required",
        ];
    }
}
