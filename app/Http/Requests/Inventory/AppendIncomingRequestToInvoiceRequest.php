<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class AppendIncomingRequestToInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sale_id' => [
                'required',
                'integer',
                'exists:sales,id',
            ],
            'idempotency_key' => ['required', 'string', 'max:191'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('incoming_request.append_to_invoice.failure', [
            'correlation_id' => $this->header('X-Request-Id')
                ?? $this->header('X-Correlation-Id')
                ?? $this->input('idempotency_key'),
            'request_id' => (int) ($this->route('incomingRequest') ?? 0),
            'sale_id' => $this->input('sale_id'),
            'status_code' => 422,
            'error_class' => 'ValidationException',
            'outcome' => 'failure',
            'validation_error_fields' => array_keys($validator->errors()->toArray()),
        ]);

        parent::failedValidation($validator);
    }
}
