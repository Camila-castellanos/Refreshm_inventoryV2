<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BillForm extends FormRequest
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
      "items.*.status" => "sometimes",
      "items.*.date" => "sometimes",
      "items.*.vendor" => "sometimes",
      "items.*.vendor_id" => "sometimes",
      "items.*.subtotal" => "sometimes",
      "items.*.tax" => "sometimes",
      "items.*.total" => "sometimes",
    ];
  }
}
