<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // المحاسب يسجل يدوياً السندات فقط: receipt سند قبض (من عميل)، payment سند صرف (لمورد)
            // أما sale و purchase فتسجل تلقائياً عند تأكيد الفواتير
            'party_type'       => 'required|in:customer,supplier',
            'party_id'         => 'required|integer',
            'type'             => 'required|in:receipt,payment',
            'amount'           => 'required|numeric|min:0.01',
            'reference_number' => 'nullable|string|max:255',
            'notes'            => 'nullable|string',
        ];
    }
}
