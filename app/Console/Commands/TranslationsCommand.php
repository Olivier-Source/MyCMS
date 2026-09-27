<?php

namespace App\Console\Commands;

use App\Cms\Languages\LanguageManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Helps translators: lists every interface string of MyCMS (the English texts
 * passed to __() in the code and templates) and compares them with a
 * language pack.
 *
 *   php artisan mycms:translations fr             missing and obsolete strings
 *   php artisan mycms:translations de --template  writes a messages.json to fill in
 */
class TranslationsCommand extends Command
{
    protected $signature = 'mycms:translations
        {locale : Language code (fr, de, pt_BR…)}
        {--template : Write a messages.json file with the missing strings (empty values)}
        {--output= : Folder of the generated file (default: storage/app/translations/{locale})}';

    protected $description = 'Compare the interface strings with a language pack, or generate a template to translate';

    public const FRAMEWORK_STRINGS = [
        'Hello!',
        'Whoops!',
        'Regards,',
        'Reset Password',
        'Reset Password Notification',
        'You are receiving this email because we received a password reset request for your account.',
        'This password reset link will expire in :count minutes.',
        'If you did not request a password reset, no further action is required.',
        "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:",
        'All rights reserved.',
    ];

    public function handle(LanguageManager $languages): int
    {
        $locale = (string) $this->argument('locale');
        $strings = $this->extract();
        $existing = $languages->find($locale)?->file('messages') ?? [];

        $missing = array_values(array_diff($strings, array_keys($existing)));
        $obsolete = array_values(array_diff(array_keys($existing), $strings));

        $this->info(count($strings).' strings in the code, '.count($existing).' in the pack "'.$locale.'".');

        if ($this->option('template')) {
            $dir = $this->option('output') ?: storage_path('app/translations/'.$locale);
            File::ensureDirectoryExists($dir);
            $messages = $existing + array_fill_keys($missing, '');
            ksort($messages, SORT_NATURAL | SORT_FLAG_CASE);
            File::put($dir.'/messages.json', json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
            if (! is_file($dir.'/language.json')) {
                File::put($dir.'/language.json', json_encode([
                    'code' => $locale, 'name' => 'Language name in English', 'native' => 'Language name', 'version' => '1.0.0', 'author' => '', 'direction' => 'ltr',
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
            }
            $this->info('Template written in '.$dir.' ('.count($missing).' strings to translate).');

            return self::SUCCESS;
        }

        if ($missing) {
            $this->warn(count($missing).' missing string(s):');
            foreach ($missing as $s) {
                $this->line('  - '.$s);
            }
        }
        if ($obsolete) {
            $this->comment(count($obsolete).' obsolete string(s) (no longer used):');
            foreach ($obsolete as $s) {
                $this->line('  - '.$s);
            }
        }
        if (! $missing && ! $obsolete) {
            $this->info('The language pack is complete.');
        }

        return $missing ? self::FAILURE : self::SUCCESS;
    }

    /** @return string[] Strings passed to __(), trans_choice() and @lang() */
    public function extract(): array
    {
        $paths = [app_path(), resource_path('views'), resource_path('themes/default/views'), database_path('seeders')];
        $found = [];

        foreach ($paths as $path) {
            foreach (File::allFiles($path) as $file) {
                if (! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }
                $code = $file->getContents();
                preg_match_all('/(?:__|trans_choice|@lang|->sentence)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/s', $code, $matches, PREG_SET_ORDER);
                foreach ($matches as $m) {
                    $string = $m[1] === "'" ? str_replace(["\\'", '\\\\'], ["'", '\\'], $m[2]) : stripcslashes($m[2]);
                    // Keys of PHP translation groups ("validation.required") are not interface strings
                    if (! preg_match('/^[a-z_]+\.[a-z_.]+$/', $string)) {
                        $found[$string] = true;
                    }
                }
            }
        }

        // Strings of the Laravel e-mails (password reset, e-mail layout)
        foreach (self::FRAMEWORK_STRINGS as $string) {
            $found[$string] = true;
        }

        $strings = array_keys($found);
        sort($strings, SORT_NATURAL | SORT_FLAG_CASE);

        return $strings;
    }
}
