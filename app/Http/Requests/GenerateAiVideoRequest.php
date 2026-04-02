<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAiVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth middleware allaqachon tekshiradi
    }

    public function rules(): array
    {
        $subjects = implode(',', array_keys(config('ai_video.subjects')));
        $styles   = implode(',', array_keys(config('ai_video.video_styles')));
        $lengths  = implode(',', array_keys(config('ai_video.explanation_lengths')));
        $voices   = implode(',', array_keys(config('ai_video.voice_styles')));

        return [
            'subject'            => "required|string|in:{$subjects}",
            'problem_text'       => 'required|string|min:10|max:3000',
            'video_style'        => "nullable|string|in:{$styles}",
            'explanation_length' => "nullable|string|in:{$lengths}",
            'voice_style'        => "nullable|string|in:{$voices}",
            'language'           => 'nullable|string|in:uz,en,ru',
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required'      => 'Fan turini tanlang.',
            'subject.in'            => "Noto'g'ri fan tanlandi.",
            'problem_text.required' => 'Masala matnini kiriting.',
            'problem_text.min'      => "Masala matni kamida 10 ta belgidan iborat bo'lishi kerak.",
            'problem_text.max'      => "Masala matni 3000 belgidan oshmasligi kerak.",
        ];
    }
}
