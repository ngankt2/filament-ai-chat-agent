<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Providers Configuration
    |--------------------------------------------------------------------------
    | Cấu hình cho nhiều nhà cung cấp AI như OpenAI, Gemini, Claude...
    */

    'default' => env('AI_DEFAULT_PROVIDER', 'openai'),

    'providers' => [

        'openai' => [
            'api_key'     => env('OPENAI_API_KEY', ''),
            'base_url'    => env('OPENAI_API_BASE_URL', 'https://api.openai.com/v1'),
            'model'       => env('OPENAI_MODEL', 'gpt-4o'),
            'timeout'     => 30,
            'options'     => [
                'temperature' => 0.7,
                'max_tokens' => 1024,
                'top_p' => 1.0,
                'frequency_penalty' => 0.0,
                'presence_penalty' => 0.0,
            ],
        ],

        'gemini' => [
            'api_key'     => env('GEMINI_API_KEY', ''),
            'base_url'    => env('GEMINI_API_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/models'),
            'model'       => env('GEMINI_MODEL', 'gemini-pro'),
            'timeout'     => 30,
        ],

        // Thêm Claude, Mistral, v.v. nếu cần
    ],
];
