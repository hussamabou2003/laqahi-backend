<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Guard المدير الوطني
        'admin' => [
            'driver' => 'sanctum',
            'provider' => 'admins',
        ],

        // Guard الطبيب
        'doctor' => [
            'driver' => 'sanctum',
            'provider' => 'doctors',
        ],

        // Guard ولي الأمر
        'parent' => [
            'driver' => 'sanctum',
            'provider' => 'parents',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],

        'doctors' => [
            'driver' => 'eloquent',
            'model' => App\Models\Doctor::class,
        ],

        'parents' => [
            'driver' => 'eloquent',
            'model' => App\Models\ParentUser::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('auth.passwords.users.table', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];