<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'template_id'    => ['required', 'integer', Rule::exists('game_templates', 'id')->where('status', 'enabled')],
            'topic'          => ['required', 'string', 'min:3', 'max:200'],
            'language'       => ['required', 'string', 'in:uz,en,ru'],
            'students_count' => ['required', 'integer', 'min:1', 'max:30'],
            'category_id'    => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'difficulty'     => ['nullable', 'string', 'in:easy,medium,hard'],
        ];
    }
}
