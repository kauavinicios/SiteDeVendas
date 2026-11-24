<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras usadas no cadastro e na edição de produtos.
     * O slug é gerado automaticamente a partir do nome (HasUniqueSlug).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'active' => ['required', 'boolean'],
            'featured' => ['required', 'boolean'],
        ];
    }
}
