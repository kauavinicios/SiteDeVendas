<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id ?? $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($categoryId) {
                    if ($categoryId) {
                        $query->where('id', '!=', $categoryId);
                    }
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da categoria é obrigatório.',
            'slug.unique' => 'Este slug já está em uso por outra categoria.',
            'parent_id.exists' => 'A categoria pai selecionada é inválida.',
        ];
    }
}
