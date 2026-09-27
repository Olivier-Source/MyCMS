<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administration address
    |--------------------------------------------------------------------------
    |
    | In production you may serve the administration from a dedicated
    | sub-domain (e.g. admin.example.com): session cookies then stay isolated
    | from the public site. When ADMIN_DOMAIN is empty, the administration is
    | served under the ADMIN_PATH prefix of the main domain (/admin).
    |
    */

    'domain' => env('ADMIN_DOMAIN') ?: null,

    'path' => trim(env('ADMIN_PATH', 'admin'), '/') ?: 'admin',

    /*
    |--------------------------------------------------------------------------
    | Two-factor authentication
    |--------------------------------------------------------------------------
    |
    | When true, every administrator must set up an authenticator app before
    | reaching the administration. When false, it stays optional (it can be
    | enabled from "My account").
    |
    */

    'require_2fa' => (bool) env('ADMIN_REQUIRE_2FA', true),

    /*
    |--------------------------------------------------------------------------
    | Progressive login lockout
    |--------------------------------------------------------------------------
    |
    | After `max_attempts` failures the account is locked for `base_minutes`.
    | Each new failure after a lock doubles the duration, up to `max_minutes`.
    | A successful login (or `reset_after_hours` without failure) resets it.
    |
    */

    'lockout' => [
        'max_attempts' => 3,
        'base_minutes' => 5,
        'max_minutes' => 24 * 60,
        'reset_after_hours' => 24,
        // Global limit per IP address (all e-mail addresses combined)
        'ip_per_minute' => 10,
    ],

    // Automatic logout after inactivity in the administration
    'idle_minutes' => (int) env('ADMIN_IDLE_MINUTES', 60),

    // Retention of contact form messages (GDPR)
    'messages_retention_months' => (int) env('MYCMS_MESSAGES_RETENTION', 12),

    // Media library
    'media' => [
        'max_kb' => 8 * 1024,
        'max_dimension' => 2400,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Themes & language packs
    |--------------------------------------------------------------------------
    |
    | Bundled packages live in resources/themes and resources/languages.
    | Packages installed from a Git repository or a ZIP archive are stored in
    | storage/app/themes and storage/app/languages (keep that folder on a
    | persistent volume).
    |
    | A theme contains Blade templates, i.e. PHP code: only install themes
    | from sources you trust. Set MYCMS_ALLOW_PACKAGE_INSTALL=false to
    | forbid installing themes and language packs from the administration
    | (the `php artisan mycms:theme` / `mycms:language` commands still work).
    |
    */

    'packages' => [
        'allow_admin_install' => (bool) env('MYCMS_ALLOW_PACKAGE_INSTALL', true),
        'max_download_mb' => 50,
        'max_files' => 2000,
        'timeout' => 60,
    ],

    'default_theme' => 'default',

    // Link shown in the administration ("MyCMS" credit, help page)
    'project_url' => env('MYCMS_PROJECT_URL', 'https://github.com/mycms-project/mycms'),

    'version' => '1.0.0',

];
