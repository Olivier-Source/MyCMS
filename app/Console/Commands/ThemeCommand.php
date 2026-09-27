<?php

namespace App\Console\Commands;

use App\Cms\Packages\PackageException;
use App\Cms\Themes\Theme;
use App\Cms\Themes\ThemeManager;
use Illuminate\Console\Command;

/**
 * Themes from the command line:
 *   php artisan mycms:theme list
 *   php artisan mycms:theme install https://github.com/someone/mycms-theme-x
 *   php artisan mycms:theme install ./my-theme.zip
 *   php artisan mycms:theme activate my-theme
 *   php artisan mycms:theme update my-theme
 *   php artisan mycms:theme delete my-theme
 */
class ThemeCommand extends Command
{
    protected $signature = 'mycms:theme
        {action=list : list, install, activate, update or delete}
        {target? : Git repository / ZIP address or file (install), theme name (other actions)}
        {--activate : Activate the theme right after installing it}';

    protected $description = 'List, install, activate, update or delete themes';

    public function handle(ThemeManager $themes): int
    {
        $target = (string) $this->argument('target');

        try {
            switch ($this->argument('action')) {
                case 'list':
                    $active = $themes->active()->slug;
                    $this->table(['Theme', 'Name', 'Version', 'Source', ''], array_map(fn (Theme $t) => [
                        $t->slug, $t->name(), $t->version(), $t->bundled ? 'built-in' : ($themes->sourceUrl($t) ?? 'zip'), $t->slug === $active ? 'active' : '',
                    ], array_values($themes->all())));

                    return self::SUCCESS;

                case 'install':
                    $source = is_file($target) ? ['zip' => realpath($target)] : ['url' => $target];
                    $theme = $themes->install($source);
                    $this->info("Theme \"{$theme->name()}\" ({$theme->slug}) {$theme->version()} installed.");
                    if ($this->option('activate')) {
                        $themes->activate($theme->slug);
                        $this->info('Theme activated.');
                    }

                    return self::SUCCESS;

                case 'activate':
                    $themes->activate($target);
                    $this->info("Theme \"{$target}\" activated.");

                    return self::SUCCESS;

                case 'update':
                    $theme = $themes->find($target) ?? throw new PackageException('This theme does not exist.');
                    $url = $themes->sourceUrl($theme) ?? throw new PackageException('This theme was not installed from an address: install the new ZIP file instead.');
                    $theme = $themes->install(['url' => $url]);
                    $this->info("Theme \"{$theme->name()}\" updated to {$theme->version()}.");

                    return self::SUCCESS;

                case 'delete':
                    $themes->delete($target);
                    $this->info("Theme \"{$target}\" deleted.");

                    return self::SUCCESS;
            }
        } catch (PackageException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->error('Unknown action. Use: list, install, activate, update, delete.');

        return self::FAILURE;
    }
}
