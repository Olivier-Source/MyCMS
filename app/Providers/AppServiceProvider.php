<?php

namespace App\Providers;

use App\Cms\Languages\LanguageManager;
use App\Cms\Languages\PackTranslationLoader;
use App\Cms\Placeholders;
use App\Cms\Renderer;
use App\Cms\SiteSettings;
use App\Cms\Themes\ThemeManager;
use App\Models\Page;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SiteSettings::class);
        $this->app->singleton(Placeholders::class);
        $this->app->singleton(ThemeManager::class);
        $this->app->singleton(LanguageManager::class);

        // Translations: Laravel files + language packs (JSON) + theme strings.
        // extend() (and not singleton()) because Laravel's translation provider
        // is deferred and registers its own loader later.
        $this->app->extend('translation.loader', fn ($loader, $app) => new PackTranslationLoader(
            $app['files'],
            [base_path('vendor/laravel/framework/src/Illuminate/Translation/lang'), $app['path.lang']]
        ));
    }

    public function boot(): void
    {
        // Behind a proxy that terminates TLS, links must stay in https
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Administration passwords: 12 characters minimum, letters, digits,
        // and absent from known data leaks (haveibeenpwned).
        Password::defaults(function () {
            $rule = Password::min(12)->letters()->mixedCase()->numbers();

            // Online check disabled during automated tests
            return $this->app->runningUnitTests() ? $rule : $rule->uncompromised();
        });

        RateLimiter::for('admin-login', fn (Request $request) => Limit::perMinute(config('mycms.lockout.ip_per_minute'))->by($request->ip()));
        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinute(2)->by($request->ip()),
            Limit::perHour(6)->by($request->ip()),
        ]);

        // Password reset link: to the administration
        ResetPassword::createUrlUsing(fn ($user, string $token) => route('admin.password.reset', ['token' => $token, 'email' => $user->email]));

        // Public templates: "theme::" namespace (active theme → parent → default)
        $this->app->make(ThemeManager::class)->registerViews();

        // Data shared by the public templates (computed once per request)
        View::composer(['theme::*', 'errors::*'], function ($view) {
            // Stored on the request (not in a static variable, which would
            // survive from one request to another in the tests)
            $attributes = request()->attributes;
            if (! $attributes->has('mycms.shared')) {
                $attributes->set('mycms.shared', $this->sharedSiteData());
            }

            foreach ($attributes->get('mycms.shared') as $key => $value) {
                if (! $view->offsetExists($key)) {
                    $view->with($key, $value);
                }
            }
            // Error pages: no renderer provided by a controller
            if (! $view->offsetExists('r')) {
                $view->with('r', app(Renderer::class)->preload([]));
            }
            if (! $view->offsetExists('page')) {
                $view->with('page', null);
            }
        });
    }

    private function sharedSiteData(): array
    {
        $settings = app(SiteSettings::class);
        $languages = app(LanguageManager::class);
        $themes = app(ThemeManager::class);
        $locale = $settings->contentLocale() ?? $languages->defaultLocale();
        $pages = Schema::hasTable('pages') ? Page::published()->inLocale($locale)->orderBy('position')->get() : collect();
        $renderer = app(Renderer::class);
        $cta = (string) $settings->get('cta_url');

        return [
            'settings' => $settings,
            'ph' => app(Placeholders::class),
            'theme' => $themes->active(),
            'themes' => $themes,
            'languages' => $languages,
            'contentLocale' => $locale,
            'language' => $languages->find($locale),
            'navPages' => $pages->where('show_in_nav', true),
            'footerPages' => $pages->where('show_in_footer', true),
            'homeUrl' => site_url($languages->pathPrefix($locale) ?: '/'),
            'ctaLabel' => (string) $settings->get('cta_label'),
            'ctaUrl' => $cta !== '' ? $renderer->href($cta) : '',
        ];
    }
}
