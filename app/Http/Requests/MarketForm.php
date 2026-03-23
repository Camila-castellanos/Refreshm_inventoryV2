<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
            'is_active' => 'boolean',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'logo' => 'nullable|file|image|max:2048', // Max 2MB for logo
            'favicon' => 'nullable|file|image|max:1024', // Max 1MB for favicon
            'banners' => 'nullable|array',
            'banners.*' => 'file|image|max:5120', // Max 5MB per image
            'deleted_banners' => 'nullable|array',
            'deleted_banners.*' => 'integer|exists:media,id',
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
            'about_us' => 'nullable|array',
            'about_us.title' => 'nullable|string|max:255',
            'about_us.content' => 'nullable|string|max:5000',
            'about_us.image_url' => 'nullable|string|max:255',
        ];
    }
}
