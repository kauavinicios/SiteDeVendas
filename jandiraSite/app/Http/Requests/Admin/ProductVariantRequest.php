<?php

namespace App\Http\Requests\Admin;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras usadas no cadastro e na edição de variações.
     * Na edição, o SKU da própria variação é ignorado na regra "unique".
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $variantId = $this->route('variant')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('product_variants', 'sku')->ignore($variantId),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'active' => ['required', 'boolean'],
        ];
    }

    /**
     * Dados validados com o SKU normalizado (sem espaços; vazio vira null).
     *
     * @return array<string, mixed>
     */
    public function variantData(): array
    {
        $data = $this->validated();

        $data['sku'] = $this->filled('sku')
            ? trim($data['sku'])
            : null;

        if (! $this->user()?->hasPermission(Permission::ViewFinancialReports)) {
            unset($data['cost_price']);
        } else {
            $data['cost_price'] = $this->filled('cost_price')
                ? $data['cost_price']
                : null;
        }

        return $data;
    }
}
