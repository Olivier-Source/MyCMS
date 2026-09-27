<?php

namespace App\Console\Commands;

use App\Cms\Languages\LanguageManager;
use App\Cms\SiteSettings;
use App\Models\Page;
use Database\Seeders\StarterContentSeeder;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Installs MyCMS: database tables, starter pages in the chosen language, site
 * name and e-mail, and (optionally) the first administrator.
 *
 * Idempotent: running it again on an installed site only applies the
 * migrations (the content is never replaced without --fresh).
 */
class InstallCommand extends Command
{
    protected $signature = 'mycms:install
        {--locale= : Language of the site and of the administration (en, fr…)}
        {--site-name= : Name of the site}
        {--email= : Contact e-mail of the site}
        {--admin-email= : E-mail of the first administrator}
        {--admin-name= : Name of the first administrator}
        {--fresh : Replace the existing pages with the starter content (deletes the pages!)}';

    protected $description = 'Install MyCMS (database, starter content, first administrator)';

    public function handle(LanguageManager $languages, SiteSettings $settings): int
    {
        $this->components->info('MyCMS '.config('mycms.version'));

        $this->call('migrate', ['--force' => true]);

        $installed = Page::exists();
        if ($installed && ! $this->option('fresh')) {
            $this->components->info(__('MyCMS is already installed: the database is up to date.'));

            return $this->createAdmin();
        }
        if ($installed && ! $this->confirm('All the existing pages will be replaced. Continue?')) {
            return self::FAILURE;
        }

        $locale = $this->option('locale') ?: ($this->input->isInteractive()
            ? select('Language of the site', $languages->options(), default: config('app.locale'))
            : config('app.locale'));
        if (! $languages->exists($locale)) {
            $this->error("Unknown language \"{$locale}\". Available: ".implode(', ', array_keys($languages->all())));

            return self::FAILURE;
        }
        app()->setLocale($locale);

        $siteName = $this->option('site-name') ?: ($this->input->isInteractive() ? text(__('Name of the site'), default: __('My Website'), required: true) : __('My Website'));
        $email = $this->option('email') ?: $this->option('admin-email') ?: 'contact@example.com';

        $settings->set([
            'default_locale' => $locale,
            'site_locales' => [$locale],
            'admin_locale' => $locale,
        ]);

        $this->call('db:seed', ['--class' => StarterContentSeeder::class, '--force' => true]);

        $settings->set([
            'site_name' => strip_tags($siteName),
            'initials' => $this->initials($siteName),
            'email' => $email,
        ]);

        $this->components->info(__('Starter content installed.'));

        return $this->createAdmin();
    }

    private function createAdmin(): int
    {
        if (! $this->option('admin-email')) {
            return self::SUCCESS;
        }

        return $this->call('mycms:admin', array_filter([
            'email' => $this->option('admin-email'),
            '--name' => $this->option('admin-name'),
            '--no-interaction' => ! $this->input->isInteractive(),
        ]));
    }

    private function initials(string $name): string
    {
        $words = preg_split('/[\s\-_]+/u', trim($name)) ?: [];
        $letters = array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), array_filter($words));

        return mb_substr(implode('', $letters), 0, 2) ?: 'MY';
    }
}
