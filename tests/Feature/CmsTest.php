<?php

namespace Tests\Feature;

use App\Cms\BlockRegistry;
use App\Cms\SiteSettings;
use App\Models\Block;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\StarterContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StarterContentSeeder::class);
    }

    private function actingAsAdmin(): static
    {
        $admin = User::factory()->admin()->withTwoFactor('JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP')->create();

        return $this->actingAs($admin)->withSession(['admin.last_activity' => time()]);
    }

    public function test_all_starter_pages_are_online(): void
    {
        $this->assertSame(6, Page::count());
        foreach (Page::all() as $page) {
            $this->get($page->path())->assertOk()->assertSee($page->title);
        }
    }

    public function test_every_block_type_can_be_added_edited_and_rendered(): void
    {
        $page = Page::where('is_home', true)->first();
        $this->actingAsAdmin();

        foreach (array_keys(BlockRegistry::all()) as $type) {
            $this->post(route('admin.blocks.store', $page), ['type' => $type])->assertRedirect();
            $block = $page->blocks()->where('type', $type)->latest('id')->first();
            $this->get(route('admin.blocks.edit', $block))->assertOk();
        }

        $this->get('/')->assertOk();
        $this->get(route('admin.pages.preview', $page))->assertOk();
    }

    public function test_public_pages_send_security_headers(): void
    {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_placeholders_follow_the_site_information(): void
    {
        app(SiteSettings::class)->set(['site_name' => 'Bakery Martin', 'phone' => '04 99 99 99 99']);

        $this->get('/')->assertSee('Welcome to Bakery Martin')->assertDontSee('{site_name}');
        $this->get('/contact')->assertSee('04 99 99 99 99');
    }

    public function test_a_disabled_page_disappears_from_the_site_but_stays_in_preview(): void
    {
        $page = Page::where('slug', 'about')->first();

        $this->actingAsAdmin()->post(route('admin.pages.publish', $page))->assertRedirect();
        $this->assertFalse($page->fresh()->is_published);

        $this->get('/about')->assertNotFound();
        $this->get('/')->assertDontSee('>About</a>', false);
        $this->get(route('admin.pages.preview', $page))->assertOk()->assertSee('not published');
    }

    public function test_the_home_page_cannot_be_disabled_or_deleted(): void
    {
        $home = Page::where('is_home', true)->first();

        $this->actingAsAdmin()->post(route('admin.pages.publish', $home))->assertForbidden();
        $this->delete(route('admin.pages.destroy', $home))->assertForbidden();
        $this->assertTrue($home->fresh()->is_published);
    }

    public function test_an_admin_can_create_a_page_from_a_template(): void
    {
        $this->actingAsAdmin()->post(route('admin.pages.store'), ['title' => 'Our workshops', 'template' => 'text', 'locale' => 'en'])
            ->assertRedirect();

        $page = Page::where('slug', 'our-workshops')->first();
        $this->assertNotNull($page);
        $this->assertFalse($page->is_published);
        $this->assertSame(['page_header', 'rich_text'], $page->blocks->pluck('type')->all());
    }

    public function test_reserved_addresses_cannot_be_used(): void
    {
        $page = Page::where('slug', 'about')->first();

        $this->actingAsAdmin()->put(route('admin.pages.update', $page), ['title' => 'Test', 'slug' => 'admin'])
            ->assertSessionHasErrors('slug');
        $this->put(route('admin.pages.update', $page), ['title' => 'Test', 'slug' => 'fr'])
            ->assertSessionHasErrors('slug');
    }

    public function test_block_content_is_sanitized(): void
    {
        $block = Block::where('type', 'rich_text')->first();
        $data = $block->data;
        $data['content'] = '<p onclick="alert(1)">Hello <script>alert(2)</script><a href="javascript:alert(3)">link</a> <strong>bold</strong></p>';
        $data['title'] = '<img src=x onerror=alert(4)>Title';

        $this->actingAsAdmin()->put(route('admin.blocks.update', $block), ['data' => json_encode($data)])->assertRedirect();

        $saved = $block->fresh()->data;
        $this->assertStringNotContainsString('script', $saved['content']);
        $this->assertStringNotContainsString('onclick', $saved['content']);
        $this->assertStringNotContainsString('javascript:', $saved['content']);
        $this->assertStringContainsString('<strong>bold</strong>', $saved['content']);
        $this->assertSame('Title', $saved['title']);
    }

    public function test_dangerous_links_are_refused(): void
    {
        $block = Block::where('type', 'hero')->first();
        $data = $block->data;
        $data['buttons'][0]['url'] = 'javascript:alert(1)';

        $this->actingAsAdmin()->put(route('admin.blocks.update', $block), ['data' => json_encode($data)])
            ->assertSessionHasErrors('buttons.0.url');
        $this->assertNotSame('javascript:alert(1)', $block->fresh()->data['buttons'][0]['url']);
    }

    public function test_sections_can_be_hidden(): void
    {
        $block = Block::whereHas('page', fn ($q) => $q->where('is_home', true))->where('type', 'steps')->first();
        $this->get('/')->assertSee('Simple, in three steps');

        $this->actingAsAdmin()->post(route('admin.blocks.toggle', $block));
        $this->get('/')->assertDontSee('Simple, in three steps');
    }

    public function test_settings_are_saved_and_links_are_checked(): void
    {
        $this->actingAsAdmin()->put(route('admin.settings.update', 'button'), [
            'cta_label' => 'Book now', 'cta_url' => 'javascript:alert(1)', 'cta_icon' => 'event',
        ])->assertSessionHasErrors('cta_url');

        $this->put(route('admin.settings.update', 'button'), [
            'cta_label' => 'Book now', 'cta_url' => 'https://booking.example.com', 'cta_icon' => 'event',
        ])->assertRedirect();

        $this->get('/')->assertSee('Book now')->assertSee('https://booking.example.com', false);
    }

    public function test_the_video_block_only_accepts_youtube_and_vimeo(): void
    {
        $page = Page::where('is_home', true)->first();
        $page->blocks()->create(['type' => 'video', 'position' => 99, 'data' => ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']]);
        $page->blocks()->create(['type' => 'video', 'position' => 100, 'data' => ['url' => 'https://evil.example.com/video']]);

        $this->get('/')->assertSee('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertDontSee('evil.example.com', false);
    }

    public function test_contact_messages_are_stored_encrypted(): void
    {
        $response = $this->post(route('contact.store'), [
            '_ts' => Crypt::encryptString((string) (time() - 30)),
            'name' => 'Camille Test',
            'email' => 'camille@example.com',
            'message' => 'Hello, I would like a quote.',
            'consent' => '1',
        ]);
        $response->assertRedirect()->assertSessionHas('contact_sent');

        $this->assertSame('Camille Test', ContactMessage::first()->name);
        $raw = DB::table('contact_messages')->first();
        $this->assertStringNotContainsString('Camille', $raw->name);
        $this->assertStringNotContainsString('quote', $raw->message);
    }

    public function test_contact_form_traps_bots(): void
    {
        $this->post(route('contact.store'), [
            '_ts' => Crypt::encryptString((string) (time() - 30)), 'website' => 'http://spam.example',
            'name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'Buy now!', 'consent' => '1',
        ])->assertSessionHas('contact_sent');

        $this->post(route('contact.store'), [
            '_ts' => Crypt::encryptString((string) time()), 'name' => 'Fast', 'email' => 'f@example.com', 'message' => 'Too fast', 'consent' => '1',
        ]);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_only_real_images_can_be_uploaded(): void
    {
        $fake = UploadedFile::fake()->createWithContent('photo.jpg', '<?php echo "pirate"; ?>');

        $this->actingAsAdmin()->post(route('admin.media.store'), ['file' => $fake])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('media', 0);
    }

    public function test_every_admin_screen_opens(): void
    {
        $this->actingAsAdmin();
        $page = Page::first();

        foreach ([
            route('admin.dashboard'), route('admin.pages.index'), route('admin.pages.create'), route('admin.pages.edit', $page),
            route('admin.pages.settings', $page), route('admin.blocks.create', $page), route('admin.appearance.edit'),
            route('admin.themes.index'), route('admin.languages.index'), route('admin.media.index'), route('admin.messages.index'),
            route('admin.account.edit'), route('admin.activity'), route('admin.help'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (array_keys(SiteSettings::groups()) as $group) {
            $this->get(route('admin.settings.edit', $group))->assertOk();
        }
    }

    public function test_admin_routes_are_not_served_on_the_public_domain_when_a_subdomain_is_configured(): void
    {
        config(['mycms.domain' => 'admin.example.test']);
        $request = Request::create('http://admin.example.test/about');
        $response = $this->app->handle($request);

        $this->assertSame(404, $response->getStatusCode());
    }
}
