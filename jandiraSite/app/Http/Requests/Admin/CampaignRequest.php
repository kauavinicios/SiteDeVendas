<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:160'],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:5000'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'cta_url' => ['nullable', 'url', 'max:500', 'required_with:cta_label'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'Informe o assunto do e-mail.',
            'subject.max' => 'O assunto pode ter no máximo 160 caracteres.',
            'title.required' => 'Informe o título exibido no e-mail.',
            'title.max' => 'O título pode ter no máximo 160 caracteres.',
            'body.required' => 'Escreva a mensagem da campanha.',
            'body.max' => 'A mensagem pode ter no máximo 5000 caracteres.',
            'cta_label.max' => 'O texto do botão pode ter no máximo 80 caracteres.',
            'cta_url.url' => 'Informe uma URL válida para o botão (incluindo https://).',
            'cta_url.required_with' => 'Informe o link do botão quando preencher o texto do botão.',
        ];
    }
}
