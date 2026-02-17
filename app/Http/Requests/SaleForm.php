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
            'subtotal' => 'required|numeric',
            'discount' => 'required|numeric',
            'flatTax' => 'nullable|numeric',
            'paid' => 'required|numeric',
            'payment_method' => 'string|nullable',
            'payment_account' => 'string|nullable',
            'notes' => 'string|nullable',
            'tax' => 'required|numeric',
            'newItems' => 'array|nullable',
            'total' => 'nullable|numeric',
            'balance_remaining' => 'required|numeric',
            'amount_paid' => 'required|numeric',
            'items' => 'array|nullable',
            'items.*.id' => 'required_with:items|exists:items,id',
            'items.*.sold' => 'required_with:items|date',
            'items.*.customer' => 'required_with:items',
            'items.*.profit' => 'required_with:items|numeric',
            'items.*.position' => 'numeric|nullable',
            'items.*.storage_id' => 'exists:storages,id|nullable',
            'items.*.selling_price' => 'required_with:items|numeric',
            'items.*.type' => 'required_with:items|string',
            'tax_id' => 'nullable',
            // Campos de crédito
            'credit' => 'numeric|nullable',
            'credit_added' => 'numeric|nullable',
        ];
    }
}
