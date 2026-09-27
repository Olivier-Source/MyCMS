<?php

namespace App\Console\Commands;

use App\Cms\Languages\LanguageManager;
use App\Cms\Languages\LanguagePack;
use App\Cms\Packages\PackageException;
use App\Cms\SiteSettings;
use Illuminate\Console\Command;

/**
 * Language packs from the command line:
 *   php artisan mycms:language list
 *   php artisan mycms:language install https://github.com/someone/mycms-lang-de
 *   php artisan mycms:language install ./de.zip
 *   php artisan mycms:language enable de      (publish the site in this language too)
 *   php artisan mycms:language disable de
 *   php artisan mycms:language update de
 *   php artisan mycms:language delete de
 */
class LanguageCommand extends Command
{
    protected $signature = 'mycms:language
        {action=list : list, install, enable, disable, update or delete}
        {target? : Git repository / ZIP address or file (install), language code (other actions)}';

    protected $description = 'List, install, enable, update or delete language packs';

    public function handle(LanguageManager $languages, SiteSettings $settings): int
    {
        $target = (string) $this->argument('target');

        try {
            switch ($this->argument('action')) {
                case 'list':
                    $site = $languages->siteLocales();
                    $this->table(['Code', 'Language', 'Version', 'Strings', 'Source', 'Site'], array_map(fn (LanguagePack $p) => [
                        $p->code, $p->nativeName(), $p->version(), $p->code === 'en' ? 'source' : $p->stringsCount(),
                        $p->bundled ? 'built-in' : ($languages->sourceUrl($p) ?? 'zip'),
                        $p->code === $languages->defaultLocale() ? 'default' : (in_array($p->code, $site, true) ? 'yes' : ''),
                    ], array_values($languages->all())));

                    return self::SUCCESS;

                case 'install':
                    $source = is_file($target) ? ['zip' => realpath($target)] : ['url' => $target];
                    $pack = $languages->install($source);
                    $this->info("Language \"{$pack->name()}\" ({$pack->code}) installed.");

                    return self::SUCCESS;

                case 'enable':
                case 'disable':
                    $languages->find($target) ?? throw new PackageException('This language is not installed.');
                    if ($target === $languages->defaultLocale()) {
                        throw new PackageException('This is the default language of the site.');
                    }
                    $site = array_values(array_diff($languages->siteLocales(), [$target]));
                    if ($this->argument('action') === 'enable') {
                        $site[] = $target;
                    }
                    $settings->set(['site_locales' => $site]);
                    $this->info('Site languages: '.implode(', ', $languages->siteLocales()));

                    return self::SUCCESS;

                case 'update':
                    $pack = $languages->find($target) ?? throw new PackageException('This language is not installed.');
                    $url = $languages->sourceUrl($pack) ?? throw new PackageException('This language was not installed from an address: install the new ZIP file instead.');
                    $languages->install(['url' => $url]);
                    $this->info("Language \"{$target}\" updated.");

                    return self::SUCCESS;

                case 'delete':
                    $languages->delete($target);
                    $this->info("Language \"{$target}\" deleted.");

                    return self::SUCCESS;
            }
        } catch (PackageException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->error('Unknown action. Use: list, install, enable, disable, update, delete.');

        return self::FAILURE;
    }
}
