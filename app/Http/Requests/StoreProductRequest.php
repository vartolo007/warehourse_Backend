<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'category_id'      => 'required|exists:categories,id',
            'name'             => 'required|string|max:255',
            'sku'              => 'required|string|max:100|unique:products,sku',
            'barcode'          => 'nullable|string|max:100',
            'description'      => 'nullable|string',
            'quantity'         => 'nullable|integer|min:0',   // الكمية الافتتاحية عند إضافة المنتج
            'minimum_quantity' => 'nullable|integer|min:0',
            'image'            => 'nullable|string|max:500',
            'status'           => 'nullable|in:active,inactive',
        ];
    }
}
