<?php

use App\Http\Controllers\Admin;
use App\Http\Middleware\AdminIdleTimeout;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Http\Middleware\SetAdminLocale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
| Dedicated sub-domain (ADMIN_DOMAIN) or /admin prefix (ADMIN_PATH).
| The public site never links to these routes.
*/

$admin = Route::name('admin.')->middleware(SetAdminLocale::class);
$domain = config('mycms.domain');
$domain ? $admin->domain($domain) : $admin->prefix(config('mycms.path'));

$admin->group(function () {

    Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain']))->name('robots');

    // ---------- Guests ----------
    Route::middleware('guest')->group(function () {
        Route::get('/login', [Admin\Auth\LoginController::class, 'show'])->name('login');
        Route::post('/login', [Admin\Auth\LoginController::class, 'store'])->middleware('throttle:admin-login')->name('login.store');

        Route::get('/verify', [Admin\Auth\TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
        Route::post('/verify', [Admin\Auth\TwoFactorChallengeController::class, 'store'])->middleware('throttle:admin-login')->name('two-factor.verify');

        Route::get('/forgot-password', [Admin\Auth\PasswordResetController::class, 'request'])->name('password.request');
        Route::post('/forgot-password', [Admin\Auth\PasswordResetController::class, 'email'])->middleware('throttle:admin-login')->name('password.email');
        Route::get('/reset-password/{token}', [Admin\Auth\PasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('/reset-password', [Admin\Auth\PasswordResetController::class, 'update'])->middleware('throttle:admin-login')->name('password.update');
    });

    // ---------- Signed in ----------
    Route::middleware(['auth', EnsureAdmin::class, AdminIdleTimeout::class])->group(function () {
        Route::post('/logout', [Admin\Auth\LoginController::class, 'destroy'])->name('logout');

        // Two-factor authentication set-up (mandatory unless ADMIN_REQUIRE_2FA=false)
        Route::get('/security/two-factor', [Admin\Auth\TwoFactorSetupController::class, 'show'])->name('two-factor.setup');
        Route::post('/security/two-factor', [Admin\Auth\TwoFactorSetupController::class, 'confirm'])->name('two-factor.confirm');

        Route::middleware(EnsureTwoFactorEnabled::class)->group(function () {
            Route::get('/', Admin\DashboardController::class)->name('dashboard');

            // Pages
            Route::get('/pages', [Admin\PageController::class, 'index'])->name('pages.index');
            Route::get('/pages/new', [Admin\PageController::class, 'create'])->name('pages.create');
            Route::post('/pages', [Admin\PageController::class, 'store'])->name('pages.store');
            Route::post('/pages/order', [Admin\PageController::class, 'reorder'])->name('pages.reorder');
            Route::get('/pages/{page}', [Admin\PageController::class, 'edit'])->name('pages.edit');
            Route::get('/pages/{page}/settings', [Admin\PageController::class, 'settings'])->name('pages.settings');
            Route::put('/pages/{page}', [Admin\PageController::class, 'update'])->name('pages.update');
            Route::post('/pages/{page}/publish', [Admin\PageController::class, 'togglePublish'])->name('pages.publish');
            Route::post('/pages/{page}/duplicate', [Admin\PageController::class, 'duplicate'])->name('pages.duplicate');
            Route::post('/pages/{page}/translate', [Admin\PageController::class, 'translate'])->name('pages.translate');
            Route::delete('/pages/{page}', [Admin\PageController::class, 'destroy'])->name('pages.destroy');
            Route::get('/pages/{page}/preview', [Admin\PageController::class, 'preview'])->name('pages.preview');

            // Sections (blocks) of a page
            Route::get('/pages/{page}/sections/add', [Admin\BlockController::class, 'create'])->name('blocks.create');
            Route::post('/pages/{page}/sections', [Admin\BlockController::class, 'store'])->name('blocks.store');
            Route::post('/pages/{page}/sections/order', [Admin\BlockController::class, 'reorder'])->name('blocks.reorder');
            Route::get('/sections/{block}', [Admin\BlockController::class, 'edit'])->name('blocks.edit');
            Route::put('/sections/{block}', [Admin\BlockController::class, 'update'])->name('blocks.update');
            Route::post('/sections/{block}/visibility', [Admin\BlockController::class, 'toggle'])->name('blocks.toggle');
            Route::post('/sections/{block}/duplicate', [Admin\BlockController::class, 'duplicate'])->name('blocks.duplicate');
            Route::delete('/sections/{block}', [Admin\BlockController::class, 'destroy'])->name('blocks.destroy');

            // Site information
            Route::get('/settings/{group?}', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/settings/{group}', [Admin\SettingsController::class, 'update'])->name('settings.update');

            // Appearance: colours, theme options, themes
            Route::get('/appearance', [Admin\AppearanceController::class, 'edit'])->name('appearance.edit');
            Route::put('/appearance', [Admin\AppearanceController::class, 'update'])->name('appearance.update');
            Route::put('/appearance/options', [Admin\AppearanceController::class, 'updateOptions'])->name('appearance.options');
            Route::get('/themes', [Admin\ThemeController::class, 'index'])->name('themes.index');
            Route::post('/themes', [Admin\ThemeController::class, 'install'])->middleware('throttle:10,1')->name('themes.install');
            Route::post('/themes/{theme}/activate', [Admin\ThemeController::class, 'activate'])->name('themes.activate');
            Route::post('/themes/{theme}/update', [Admin\ThemeController::class, 'update'])->middleware('throttle:10,1')->name('themes.update');
            Route::delete('/themes/{theme}', [Admin\ThemeController::class, 'destroy'])->name('themes.destroy');

            // Languages
            Route::get('/languages', [Admin\LanguageController::class, 'index'])->name('languages.index');
            Route::put('/languages', [Admin\LanguageController::class, 'save'])->name('languages.save');
            Route::post('/languages', [Admin\LanguageController::class, 'install'])->middleware('throttle:10,1')->name('languages.install');
            Route::post('/languages/{code}/update', [Admin\LanguageController::class, 'update'])->middleware('throttle:10,1')->name('languages.update');
            Route::delete('/languages/{code}', [Admin\LanguageController::class, 'destroy'])->name('languages.destroy');

            // Media library
            Route::get('/media', [Admin\MediaController::class, 'index'])->name('media.index');
            Route::get('/media/list', [Admin\MediaController::class, 'list'])->name('media.list');
            Route::post('/media', [Admin\MediaController::class, 'store'])->middleware('throttle:60,1')->name('media.store');
            Route::put('/media/{media}', [Admin\MediaController::class, 'update'])->name('media.update');
            Route::delete('/media/{media}', [Admin\MediaController::class, 'destroy'])->name('media.destroy');

            // Contact form messages
            Route::get('/messages', [Admin\MessageController::class, 'index'])->name('messages.index');
            Route::get('/messages/{message}', [Admin\MessageController::class, 'show'])->name('messages.show');
            Route::post('/messages/{message}/unread', [Admin\MessageController::class, 'unread'])->name('messages.unread');
            Route::delete('/messages/{message}', [Admin\MessageController::class, 'destroy'])->name('messages.destroy');

            // My account & security
            Route::get('/account', [Admin\AccountController::class, 'edit'])->name('account.edit');
            Route::put('/account/profile', [Admin\AccountController::class, 'updateProfile'])->name('account.profile');
            Route::put('/account/password', [Admin\AccountController::class, 'updatePassword'])->name('account.password');
            Route::post('/account/recovery-codes', [Admin\AccountController::class, 'regenerateCodes'])->name('account.codes');
            Route::post('/account/two-factor/disable', [Admin\AccountController::class, 'disableTwoFactor'])->name('account.two-factor.disable');
            Route::post('/account/logout-others', [Admin\AccountController::class, 'logoutOthers'])->name('account.sessions');

            Route::get('/icons', Admin\IconController::class)->name('icons');
            Route::get('/activity', Admin\ActivityController::class)->name('activity');
            Route::get('/help', Admin\HelpController::class)->name('help');
        });
    });
});
