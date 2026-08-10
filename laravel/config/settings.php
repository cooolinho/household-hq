<?php

return [
    'settings' => [
        \App\Settings\FixedCostSettings::class,
        \App\Settings\ImapImportSettings::class,
    ],

    'setting_class_path' => app_path('Settings'),

    'migrations_paths' => [
        database_path('settings'),
    ],

    'auto_discover_settings' => [
        app_path('Settings'),
    ],

    'discovered_settings_cache_path' => base_path('bootstrap/cache'),

    'default_repository' => 'database',

    'repositories' => [
        'database' => [
            'type' => Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository::class,
            'model' => null,
            'table' => null,
            'connection' => null,
        ],
        'redis' => [
            'type' => Spatie\LaravelSettings\SettingsRepositories\RedisSettingsRepository::class,
            'connection' => null,
            'prefix' => null,
        ],
    ],

    'encoder' => null,

    'decoder' => null,

    'cache' => [
        'enabled' => (bool)env('SETTINGS_CACHE_ENABLED', false),
        'store' => null,
        'prefix' => null,
        'ttl' => null,
        'memo' => env('SETTINGS_CACHE_MEMO', false),
    ],

    'global_casts' => [],

    'casts' => [],
];

