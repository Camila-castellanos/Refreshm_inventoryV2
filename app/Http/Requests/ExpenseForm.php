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
      "items.*.date" => "required",
      "items.*.name" => "sometimes",
      "items.*.category" => "required",
      "items.*.subtotal" => "sometimes",
      "items.*.total" => "sometimes",
      "items.*.tax" => "sometimes",
      "items.*.tax_id" => "required",
    ];
  }

  public function messages(): array
  {
      return [
          'items.required' => 'You must provide at least one expense item.',
          'items.array' => 'The expense items must be sent in the correct format.',
          'items.min' => 'Please add at least one expense item.',

      

          'items.*.category.required' => 'A category is required for every expense item.',
          'items.*.category.string' => 'The category must be text.',
          'items.*.category.max' => 'The category name is too long for item #:position.', // Example using :position

          'items.*.tax_id.required' => 'Please select the tax type for each expense item.',
          'items.*.tax_id.integer' => 'The tax type selection is invalid for item #:position.',
          'items.*.tax_id.exists' => 'The selected tax type does not exist for item #:position.',

          // Example messages for 'sometimes' fields if they fail rules *when present*
          'items.*.name.string' => 'The name for item #:position must be text.',
          'items.*.name.max' => 'The name for item #:position is too long.',

          'items.*.subtotal.numeric' => 'The subtotal for item #:position must be a valid number.',
          'items.*.subtotal.min' => 'The subtotal for item #:position cannot be negative.',

          'items.*.total.numeric' => 'The total for item #:position must be a valid number.',
          'items.*.total.min' => 'The total for item #:position cannot be negative.',

          'items.*.tax.numeric' => 'The tax amount for item #:position must be a valid number.',
          'items.*.tax.min' => 'The tax amount for item #:position cannot be negative.',
      ];
  }
}
