<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras para upload múltiplo de imagens do produto.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.required' => 'Selecione ao menos uma imagem para enviar.',
            'images.max' => 'Você pode enviar no máximo 10 imagens por vez.',
            'images.*.image' => 'Cada arquivo enviado deve ser uma imagem válida.',
            'images.*.mimes' => 'Formatos permitidos: JPG, JPEG, PNG ou WEBP.',
            'images.*.max' => 'Cada imagem deve ter no máximo 5 MB.',
        ];
    }
}
