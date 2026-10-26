<?php

return [
    /*
    |--------------------------------------------------------------------------
    | General API Configuration
    |--------------------------------------------------------------------------
    */
    'version' => env('DHUNZO_API_VERSION', 'v1'),
    'app_name' => env('APP_NAME', 'Dhunzo Music API'),

    /*
    |--------------------------------------------------------------------------
    | Music Configuration
    |--------------------------------------------------------------------------
    */
    'music' => [
        'default_provider' => env('DHUNZO_DEFAULT_MUSIC_PROVIDER', 'saavn'),
        'cache' => [
            'search_ttl' => env('DHUNZO_SEARCH_CACHE_TTL', 900),       // 15 minutes
            'details_ttl' => env('DHUNZO_DETAILS_CACHE_TTL', 86400),   // 24 hours
        ],
        'providers' => [
            'saavn' => [
                'base_url' => env('SAAVN_BASE_URL', 'https://www.jiosaavn.com/api.php'),
                'timeout' => env('SAAVN_TIMEOUT', 5.0),
                'default_params' => [
                    '_format' => 'json',
                    '_marker' => '0',
                    'api_version' => '4',
                    'ctx' => 'web6dot0',
                ],
            ],
            'youtube' => [
                'api_key' => env('YOUTUBE_API_KEY', ''),
                'base_url' => env('YOUTUBE_BASE_URL', 'https://www.googleapis.com/youtube/v3'),
                'timeout' => env('YOUTUBE_TIMEOUT', 5.0),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Lyrics Configuration
    |--------------------------------------------------------------------------
    */
    'lyrics' => [
        'timeout_ms' => env('DHUNZO_LYRICS_TIMEOUT_MS', 2500),
        'matcher' => [
            'min_score' => 0.70,
            'weights' => [
                'title' => 0.40,
                'artist' => 0.30,
                'duration' => 0.20,
                'album' => 0.10,
            ],
            'duration_tolerance_seconds' => 3,
        ],
        'circuit_breaker' => [
            'failure_threshold' => 5,
            'open_duration_seconds' => 300,
            'negative_cache_ttl' => 1800,
        ],
        'providers' => [
            'lrclib' => [
                'enabled' => env('LRCLIB_ENABLED', true),
                'base_url' => env('LRCLIB_BASE_URL', 'https://lrclib.net/api'),
            ],
            'betterlyrics' => [
                'enabled' => env('BETTERLYRICS_ENABLED', true),
                'base_url' => env('BETTERLYRICS_BASE_URL', 'https://lyrics.cx/api'),
            ],
            'kugou' => [
                'enabled' => env('KUGOU_ENABLED', true),
                'search_url' => env('KUGOU_SEARCH_URL', 'http://lyrics.kugou.com/search'),
                'download_url' => env('KUGOU_DOWNLOAD_URL', 'http://lyrics.kugou.com/download'),
            ],
            'netease' => [
                'enabled' => env('NETEASE_ENABLED', true),
                'search_url' => env('NETEASE_SEARCH_URL', 'https://music.163.com/api/search/get/web'),
                'lyrics_url' => env('NETEASE_LYRICS_URL', 'https://music.163.com/api/song/lyric'),
            ],
            'musixmatch' => [
                'enabled' => env('MUSIXMATCH_ENABLED', true),
                'base_url' => env('MUSIXMATCH_BASE_URL', 'https://apic-desktop.musixmatch.com/ws/1.1'),
                'token' => env('MUSIXMATCH_TOKEN', ''),
            ],
            'genius' => [
                'enabled' => env('GENIUS_ENABLED', true),
                'access_token' => env('GENIUS_ACCESS_TOKEN', ''),
                'base_url' => env('GENIUS_BASE_URL', 'https://api.genius.com'),
            ],
            'youtube_caption' => [
                'enabled' => env('YOUTUBE_CAPTION_ENABLED', true),
            ],
        ],
        'ai_fallback' => [
            'enabled' => env('LYRICS_AI_FALLBACK_ENABLED', true),
            'driver' => env('LYRICS_AI_DRIVER', 'whisper'), // whisper, groq, openai, gemini, acoustic
            'api_key' => env('LYRICS_AI_API_KEY', env('GROQ_API_KEY', env('OPENAI_API_KEY', ''))),
            'model' => env('LYRICS_AI_MODEL', 'whisper-large-v3'),
            'endpoint' => env('LYRICS_AI_ENDPOINT', 'https://api.groq.com/openai/v1/audio/transcriptions'),
            'timeout_seconds' => env('LYRICS_AI_TIMEOUT', 15),
            'supported_languages' => [
                'bho' => 'Bhojpuri',
                'hi'  => 'Hindi',
                'pa'  => 'Punjabi',
                'bn'  => 'Bengali',
                'ta'  => 'Tamil',
                'te'  => 'Telugu',
                'en'  => 'English',
                'ur'  => 'Urdu',
                'mr'  => 'Marathi',
                'gu'  => 'Gujarati',
            ],
            'save_to_database' => true,
        ],
    ],
];
