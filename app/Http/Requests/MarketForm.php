<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class MarketForm extends FormRequest
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
        $marketId = $this->route('market')?->id;

        return [
            'name' => 'required|string|max:255',
            'shop_id' => [
                'required',
                'exists:shops,id',
                Rule::exists('shops', 'id')->where(function ($query) {
                    $query->where('company_id', Auth::user()->company_id);
                }),
            ],
            'description' => 'nullable|string|max:1000',
            'tagline' => 'nullable|string|max:255',
            'currency' => 'required|string|in:USD,EUR,GBP,CAD,AUD',
            'show_inventory_count' => 'boolean',
            'is_active' => 'boolean',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'custom_domain' => [
                'nullable',
                'string',
                'max:255',
                $marketId 
                    ? Rule::unique('markets', 'custom_domain')->ignore($marketId)
                    : Rule::unique('markets', 'custom_domain'),
            ],
            'faq' => 'nullable|array',
            'faq.title' => 'nullable|string|max:255',
            'faq.description' => 'nullable|string|max:1000',
            'faq.questions' => 'nullable|array',
            'faq.questions.*.id' => 'nullable|string',
            'faq.questions.*.question' => 'nullable|string|max:500',
            'faq.questions.*.answer' => 'nullable|string|max:2000',
            'faq.questions.*.order' => 'nullable|integer',
        ];
    }
}
