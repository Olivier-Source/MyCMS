<?php

namespace Tests\Feature;

use App\Cms\BlockRegistry;
use App\Cms\Languages\LanguageManager;
use App\Cms\SiteSettings;
use App\Cms\Themes\ThemeManager;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\StarterContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class ThemesAndLanguagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StarterContentSeeder::class);
        BlockRegistry::flush();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(ThemeManager::installedPath().'/test-theme');
        File::deleteDirectory(LanguageManager::installedPath().'/de');
        BlockRegistry::flush();
        parent::tearDown();
    }

    private function actingAsAdmin(): static
    {
        $admin = User::factory()->admin()->withTwoFactor('JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP')->create();

        return $this->actingAs($admin)->withSession(['admin.last_activity' => time()]);
    }

    /** @param  array<string, string>  $files */
    private function zip(array $files): UploadedFile
    {
        $path = sys_get_temp_dir().'/mycms-'.uniqid().'.zip';
        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive;
            $zip->open($path, ZipArchive::CREATE);
            foreach ($files as $name => $content) {
                $zip->addFromString($name, $content);
            }
            $zip->close();
        } else {
            // Servers without the zip extension: same archive built with PharData
            $zip = new \PharData($path, 0, null, \Phar::ZIP);
            foreach ($files as $name => $content) {
                $zip->addFromString($name, $content);
            }
            unset($zip);
        }

        return new UploadedFile($path, 'package.zip', 'application/zip', null, true);
    }

    public function test_the_minimal_theme_can_be_activated_and_inherits_the_default_sections(): void
    {
        $this->actingAsAdmin()->post(route('admin.themes.activate', 'minimal'))->assertRedirect();

        $this->get('/')->assertOk()
            ->assertSee('/themes/minimal/assets/theme.css', false)
            ->assertSee('Welcome to'); // hero of the minimal theme
        $this->get('/contact')->assertOk()->assertSee('Send my message'); // contact form of the default theme
        $this->assertArrayHasKey('stats', BlockRegistry::all()); // block declared in theme.json
    }

    public function test_a_theme_can_be_installed_from_a_zip_archive(): void
    {
        $zip = $this->zip([
            'test-theme-main/theme.json' => json_encode(['name' => 'Test theme', 'slug' => 'test-theme', 'version' => '2.0.0', 'parent' => 'default']),
            'test-theme-main/views/blocks/page_header.blade.php' => '<h1 class="test-header">{{ $d[\'title\'] }} — from the test theme</h1>',
            'test-theme-main/assets/style.css' => 'body{}',
            'test-theme-main/evil.php' => '<?php echo "not copied";',
            'test-theme-main/.git/config' => 'ignored',
        ]);

        $this->actingAsAdmin()->post(route('admin.themes.install'), ['source' => 'zip', 'zip' => $zip])
            ->assertRedirect(route('admin.themes.index'));

        $path = ThemeManager::installedPath().'/test-theme';
        $this->assertFileExists($path.'/views/blocks/page_header.blade.php');
        $this->assertFileDoesNotExist($path.'/evil.php');
        $this->assertDirectoryDoesNotExist($path.'/.git');

        $this->post(route('admin.themes.activate', 'test-theme'))->assertRedirect();
        $this->get('/contact')->assertSee('from the test theme');
        $this->get('/themes/test-theme/assets/style.css')->assertOk();
        $this->get('/themes/test-theme/theme.json')->assertNotFound();
    }

    public function test_archives_with_path_traversal_are_refused(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('The zip extension is needed to build a malicious archive.');
        }

        $zip = $this->zip([
            'theme.json' => json_encode(['name' => 'Bad', 'slug' => 'test-theme']),
            '../../evil.blade.php' => 'x',
        ]);

        $this->actingAsAdmin()->post(route('admin.themes.install'), ['source' => 'zip', 'zip' => $zip])
            ->assertSessionHasErrors('install');
        $this->assertDirectoryDoesNotExist(ThemeManager::installedPath().'/test-theme');
    }

    public function test_package_addresses_on_the_local_network_are_refused(): void
    {
        $this->actingAsAdmin()->post(route('admin.themes.install'), ['source' => 'url', 'url' => 'https://127.0.0.1/theme.zip'])
            ->assertSessionHasErrors('install');
        $this->post(route('admin.themes.install'), ['source' => 'url', 'url' => 'http://example.com/theme.zip'])
            ->assertSessionHasErrors('install');
    }

    public function test_a_language_pack_can_be_installed_and_used(): void
    {
        $zip = $this->zip([
            'language.json' => json_encode(['code' => 'de', 'name' => 'German', 'native' => 'Deutsch']),
            'messages.json' => json_encode(['Send my message' => 'Nachricht senden', 'Home' => 'Startseite']),
            'hack.php' => '<?php echo 1;',
        ]);

        $this->actingAsAdmin()->post(route('admin.languages.install'), ['source' => 'zip', 'zip' => $zip])->assertRedirect();
        $this->assertFileDoesNotExist(LanguageManager::installedPath().'/de/hack.php');

        app()->setLocale('de');
        $this->assertSame('Nachricht senden', __('Send my message'));
    }

    public function test_the_site_can_be_published_in_several_languages(): void
    {
        $this->actingAsAdmin()->put(route('admin.languages.save'), [
            'site_locales' => ['en', 'fr'], 'default_locale' => 'en', 'admin_locale' => 'en',
        ])->assertRedirect();

        $home = Page::where('is_home', true)->first();
        $about = Page::where('slug', 'about')->first();
        $this->post(route('admin.pages.translate', $home), ['locale' => 'fr'])->assertRedirect();
        $this->post(route('admin.pages.translate', $about), ['locale' => 'fr'])->assertRedirect();

        $frenchAbout = Page::inLocale('fr')->where('slug', 'about')->first();
        $this->assertSame($about->id, $frenchAbout->translation_of);
        $frenchAbout->update(['slug' => 'a-propos', 'title' => 'À propos', 'is_published' => true]);

        // Translated site information
        $this->put(route('admin.settings.update', ['group' => 'identity', 'lang' => 'fr']), ['site_name' => 'Mon site', 'tagline' => 'Bienvenue'])->assertRedirect();

        $this->get('/fr')->assertOk()->assertSee('lang="fr"', false)->assertSee('Mon site')->assertSee('hreflang="en"', false);
        $this->get('/fr/a-propos')->assertOk()->assertSee('À propos');
        $this->get('/about')->assertOk()->assertSee('hreflang="fr"', false)->assertSee('/fr/a-propos', false);
        $this->get('/fr/about')->assertNotFound();
        $this->get('/de')->assertNotFound();

        // The default language keeps its own values
        $this->assertNotSame('Mon site', app(SiteSettings::class)->get('site_name'));
        $this->get('/sitemap.xml')->assertOk()->assertSee('/fr/a-propos', false);
    }

    public function test_the_french_pack_translates_the_whole_interface(): void
    {
        $this->artisan('mycms:translations', ['locale' => 'fr'])->assertSuccessful();
    }

    public function test_the_install_command_creates_the_site_in_the_chosen_language(): void
    {
        Page::query()->delete();

        $this->artisan('mycms:install', ['--locale' => 'fr', '--site-name' => 'Boulangerie Martin', '--no-interaction' => true])->assertSuccessful();

        $this->assertTrue(Page::inLocale('fr')->where('slug', 'a-propos')->exists());
        $this->get('/')->assertOk()->assertSee('Bienvenue chez Boulangerie Martin')->assertSee('lang="fr"', false);
    }
}
