<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Support\EmailValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', EmailValidation::rule(), 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', Rule::in(UserRole::staffValues())],
            'password' => ['required', 'confirmed', Password::defaults()],
            'active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do usuário.',
            'email.required' => 'Informe o e-mail de acesso.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado no sistema.',
            'role.in' => 'Selecione um cargo interno válido.',
            'password.required' => 'Defina uma senha inicial para o usuário.',
            'password.confirmed' => 'A confirmação de senha não confere.',
        ];
    }
}
