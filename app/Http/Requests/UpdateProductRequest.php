<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
            'category_id'      => 'sometimes|required|exists:categories,id',
            'name'             => 'sometimes|required|string|max:255',
            'sku'              => ['sometimes', 'required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($this->route('id'))],
            'barcode'          => 'nullable|string|max:100',
            'description'      => 'nullable|string',
            'minimum_quantity' => 'nullable|integer|min:0',
            'image'            => 'nullable|string|max:500',
            'status'           => 'nullable|in:active,inactive',
            // ملاحظة: الكمية (quantity) لا تعدل من هنا أبداً
            // أي تغيير بالكمية يجب أن يمر عبر حركات المخزون (stock movements) ليبقى السجل موثقاً
        ];
    }
}
