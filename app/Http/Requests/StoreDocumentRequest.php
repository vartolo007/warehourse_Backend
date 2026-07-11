<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
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
            // ملف بحجم أقصى 5 ميغا (صور أو PDF)
            'file'              => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'documentable_type' => 'required|in:sales_order,purchase_order',
            'documentable_id'   => 'required|integer',
        ];
    }
}
