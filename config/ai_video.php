<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Video — OpenAI sozlamalari
    |--------------------------------------------------------------------------
    */
    'openai_model'   => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'openai_timeout' => env('OPENAI_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Video provider (Grok API ulanguncha placeholder)
    |--------------------------------------------------------------------------
    | VIDEO_PROVIDER=grok  →  VideoGenerationService Grok API ishlatadi
    | VIDEO_PROVIDER=mock  →  Haqiqiy API chaqirmasdan test URL qaytaradi
    */
    'video_provider' => env('VIDEO_PROVIDER', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Grok API (xAI) — keyinchalik to'ldiradi
    |--------------------------------------------------------------------------
    */
    'grok' => [
        'api_key'      => env('GROK_API_KEY', ''),
        'api_url'      => env('GROK_API_URL', 'https://api.x.ai/v1'),
        'solver_model' => env('GROK_SOLVER_MODEL', 'grok-3'),   // masala yechish uchun
        'video_model'  => env('GROK_VIDEO_MODEL', 'grok-video-1'), // video uchun (kelajak)
        'timeout'      => env('GROK_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Foydalanuvchi limitleri
    |--------------------------------------------------------------------------
    */
    'daily_limit'     => env('AI_VIDEO_DAILY_LIMIT', 5),
    'default_language'=> env('AI_VIDEO_LANGUAGE', 'uz'),

    /*
    |--------------------------------------------------------------------------
    | Video uslublari (frontend uchun)
    |--------------------------------------------------------------------------
    */
    'video_styles' => [
        'blackboard' => 'Doska uslubi',
        'animated'   => 'Animatsiyali',
        'minimal'    => 'Minimal',
    ],

    'explanation_lengths' => [
        'short'  => 'Qisqa (1-2 daqiqa)',
        'medium' => "O'rtacha (3-5 daqiqa)",
        'long'   => "Batafsil (6-10 daqiqa)",
    ],

    'voice_styles' => [
        'calm'      => 'Sokin va aniq',
        'energetic' => "Qiziqarli va jonli",
    ],

    /*
    |--------------------------------------------------------------------------
    | Fanlar ro'yxati
    |--------------------------------------------------------------------------
    */
    'subjects' => [
        'mathematics' => 'Matematika',
        'geometry'    => 'Geometriya',
        'algebra'     => 'Algebra',
        'physics'     => 'Fizika',
        'chemistry'   => 'Kimyo',
        'biology'     => 'Biologiya',
        'history'     => 'Tarix',
        'geography'   => 'Geografiya',
        'language'    => "O'zbek tili",
        'english'     => 'Ingliz tili',
        'informatics' => 'Informatika',
        'other'       => 'Boshqa',
    ],

];
