<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemsWithBillForm extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // bill data
            'bill.vendor_id' => 'required|exists:vendors,id',
            'bill.date'      => 'required|date',
            'bill.tax_id'    => 'nullable|exists:taxes,id',
            'bill.subtotal'  => 'sometimes|numeric',
            'bill.total'     => 'sometimes|numeric',
            'bill.title'     => 'required|string|max:255',

            // items data
            'items'                => 'required|array|min:1',
            'items.*.storage_id'   => 'required|exists:storages,id',
            'items.*.manufacturer' => 'sometimes|string',
            'items.*.model'        => 'sometimes|string',
            'items.*.colour'       => 'sometimes|string',
            'items.*.battery'      => 'sometimes|string',
            'items.*.grade'        => 'sometimes|string',
            'items.*.issues'       => 'sometimes|string',
            'items.*.imei'         => 'sometimes|string',
            'items.*.cost'         => 'sometimes|numeric',
            'items.*.selling_price'=> 'sometimes|numeric',
            'items.*.tax'          => 'sometimes|numeric',
            'items.*.subtotal'     => 'sometimes|numeric',
            "items.*.supplier" => "sometimes",
            "items.*.date" => "sometimes",
            "items.*.vendor_id" => "sometimes",
            ];
        }
    
    }