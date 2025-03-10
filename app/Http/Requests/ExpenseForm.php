<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseForm extends FormRequest
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
      "items.*.date" => "sometimes",
      "items.*.name" => "sometimes",
      "items.*.category" => "sometimes",
      "items.*.amount" => "sometimes",
      "items.*.total" => "sometimes",
      "items.*.tax" => "sometimes",
      "items.*.tax_id" => "sometimes",
    ];
  }
}
