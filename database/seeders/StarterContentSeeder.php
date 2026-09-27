<?php

namespace Database\Seeders;

use App\Cms\BlockRegistry;
use App\Cms\Languages\LanguageManager;
use App\Cms\SiteSettings;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Starter content of a new site: a few generic pages (home, about, services,
 * contact, legal notice, privacy policy) in the current language.
 *
 * Texts use the tags {site_name}, {email}, {address}…: they follow the
 * "Site information" automatically. Everything can be changed in the
 * administration.
 */
class StarterContentSeeder extends Seeder
{
    public function run(): void
    {
        $locale = app(LanguageManager::class)->defaultLocale();
        app()->setLocale($locale);

        DB::transaction(function () use ($locale) {
            Page::query()->delete();

            app(SiteSettings::class)->set([
                'tagline' => __('Welcome to my website'),
                'cta_label' => __('Contact us'),
                'cta_url' => '/'.$this->slug(__('contact')),
                'cta_icon' => 'mail',
                'footer_about' => __('A short presentation of your activity, shown at the bottom of every page. Change it in "Site information → Footer".'),
                'seo_description' => __('Welcome to the website of {site_name}.'),
                'privacy_url' => '/'.$this->slug(__('privacy-policy')),
            ]);

            $pages = [
                [__('Home'), 'home', ['home' => true, 'nav' => true], $this->home()],
                [__('About'), __('about'), ['nav' => true], $this->about()],
                [__('Services'), __('services'), ['nav' => true], $this->services()],
                [__('Contact'), __('contact'), ['nav' => true], $this->contact()],
                [__('Legal notice'), __('legal-notice'), ['footer' => true], $this->legal()],
                [__('Privacy policy'), __('privacy-policy'), ['footer' => true], $this->privacy()],
            ];

            foreach ($pages as $position => [$title, $slug, $options, $blocks]) {
                $page = new Page([
                    'title' => $title,
                    'slug' => $this->slug($slug),
                    'locale' => $locale,
                    'is_published' => true,
                    'show_in_nav' => $options['nav'] ?? false,
                    'show_in_footer' => $options['footer'] ?? false,
                    'position' => $position,
                ]);
                $page->is_home = $options['home'] ?? false;
                $page->save();

                foreach ($blocks as $i => [$type, $data]) {
                    $page->blocks()->create(['type' => $type, 'position' => $i, 'data' => BlockRegistry::normalize($type, $data)]);
                }
            }
        });
    }

    private function slug(string $value): string
    {
        return Str::slug($value);
    }

    private function home(): array
    {
        return [
            ['hero', [
                'layout' => 'card',
                'badge' => __('Welcome'),
                'title' => __('Welcome to {site_name}'),
                'subtitle' => __('A short sentence that sums up what you do'),
                'text' => __('Present your activity in two or three sentences: who you are, what you offer and to whom. Everything on this page can be changed in the administration.'),
                'buttons' => [
                    ['label' => __('Contact us'), 'url' => '{button}', 'icon' => 'mail', 'style' => 'primary'],
                    ['label' => __('Our services'), 'url' => '/'.$this->slug(__('services')), 'icon' => 'arrow_forward', 'style' => 'secondary'],
                ],
                'badges' => [
                    ['icon' => 'verified', 'title' => __('Quality'), 'text' => __('A first strong point')],
                    ['icon' => 'schedule', 'title' => __('Responsiveness'), 'text' => __('A second strong point')],
                    ['icon' => 'favorite', 'title' => __('Care'), 'text' => __('A third strong point')],
                ],
                'image_icon' => 'star',
                'image_title' => __('Add your picture'),
                'quote' => __('A short testimonial or a sentence that represents you.'),
                'quote_author' => __('First name L.'),
                'quote_role' => __('Happy customer'),
            ]],
            ['cards', [
                'eyebrow' => __('What we offer'),
                'title' => __('Our services'),
                'intro' => __('Describe your main services, products or activities in a few cards.'),
                'align' => 'center',
                'style' => 'vertical',
                'columns' => '3',
                'background' => 'soft',
                'items' => [
                    ['icon' => 'lightbulb', 'tone' => 'primary', 'title' => __('Advice'), 'text' => __('Explain in a few words what this service brings to your customers.'), 'link_label' => __('Learn more'), 'link_url' => '/'.$this->slug(__('services'))],
                    ['icon' => 'handyman', 'tone' => 'tertiary', 'title' => __('Realisation'), 'text' => __('Explain in a few words what this service brings to your customers.'), 'link_label' => __('Learn more'), 'link_url' => '/'.$this->slug(__('services'))],
                    ['icon' => 'support_agent', 'tone' => 'secondary', 'title' => __('Follow-up'), 'text' => __('Explain in a few words what this service brings to your customers.'), 'link_label' => __('Learn more'), 'link_url' => '/'.$this->slug(__('services'))],
                ],
            ]],
            ['text_image', [
                'eyebrow' => __('About'),
                'title' => __('A few words about us'),
                'text' => '<p>'.e(__('Tell your story: how you started, what drives you, what makes you different. Visitors like to know who they are dealing with.')).'</p>',
                'checks' => __('Experience')."\n".__('Listening')."\n".__('Reliability'),
                'buttons' => [['label' => __('Learn more about us'), 'url' => '/'.$this->slug(__('about')), 'icon' => 'arrow_forward', 'style' => 'secondary']],
                'image_side' => 'left',
                'style' => 'split',
            ]],
            ['steps', [
                'eyebrow' => __('How it works'),
                'title' => __('Simple, in three steps'),
                'align' => 'center',
                'style' => 'numbers',
                'columns' => '3',
                'items' => [
                    ['tone' => 'primary', 'title' => __('You contact us'), 'text' => __('By phone, by e-mail or with the contact form.')],
                    ['tone' => 'tertiary', 'title' => __('We talk about your needs'), 'text' => __('We take the time to understand what you expect.')],
                    ['tone' => 'secondary', 'title' => __('We get to work'), 'text' => __('You receive a clear proposal and we follow it together.')],
                ],
            ]],
            ['cta', [
                'style' => 'banner',
                'badge' => __('Let\'s talk'),
                'title' => __('A project, a question?'),
                'text' => __('We will be happy to answer you. Write to us or call us.'),
                'buttons' => [['label' => __('Contact us'), 'url' => '{button}', 'icon' => 'mail', 'style' => 'primary']],
                'show_contact' => true,
            ]],
        ];
    }

    private function about(): array
    {
        return [
            ['hero', [
                'layout' => 'portrait',
                'show_breadcrumb' => true,
                'title' => __('About'),
                'subtitle' => __('Who we are'),
                'text' => __('Present yourself here: your background, your values, your team.'),
                'image_title' => __('Your name'),
                'image_text' => __('Your role'),
            ]],
            ['timeline', [
                'eyebrow' => __('Our story'),
                'title' => __('The key moments'),
                'text' => __('A few dates that tell your story.'),
                'stats' => [
                    ['tone' => 'primary', 'value' => '10+', 'label' => __('Years of experience'), 'text' => __('A key figure')],
                    ['tone' => 'tertiary', 'value' => '100+', 'label' => __('Happy customers'), 'text' => __('Another key figure')],
                ],
                'items' => [
                    ['period' => '2024', 'title' => __('A recent milestone'), 'text' => __('Describe this step in one or two sentences.')],
                    ['period' => '2020', 'title' => __('An important step'), 'text' => __('Describe this step in one or two sentences.')],
                    ['period' => '2015', 'title' => __('The beginning'), 'text' => __('Describe this step in one or two sentences.'), 'highlight' => true],
                ],
            ]],
            ['quote', [
                'quote' => __('A sentence that sums up your philosophy.'),
                'author' => '{site_name}',
            ]],
        ];
    }

    private function services(): array
    {
        return [
            ['page_header', [
                'title' => __('Services'),
                'text' => __('Everything we offer, and our prices.'),
            ]],
            ['cards', [
                'style' => 'horizontal',
                'columns' => '2',
                'items' => [
                    ['icon' => 'lightbulb', 'title' => __('First service'), 'text' => __('Describe the service, who it is for and what it brings.')],
                    ['icon' => 'handyman', 'title' => __('Second service'), 'text' => __('Describe the service, who it is for and what it brings.')],
                    ['icon' => 'support_agent', 'title' => __('Third service'), 'text' => __('Describe the service, who it is for and what it brings.')],
                    ['icon' => 'rocket_launch', 'title' => __('Fourth service'), 'text' => __('Describe the service, who it is for and what it brings.')],
                ],
            ]],
            ['pricing', [
                'eyebrow' => __('Prices'),
                'title' => __('Clear and transparent prices'),
                'align' => 'center',
                'background' => 'soft',
                'items' => [
                    ['category' => __('Basic'), 'tone' => 'primary', 'title' => __('Essential'), 'price' => '49 €', 'unit' => __('/ month'), 'bullets' => __('A first included element')."\n".__('A second included element'), 'button_label' => __('Contact us'), 'button_url' => '{button}', 'highlight' => false],
                    ['category' => __('Popular'), 'tone' => 'tertiary', 'title' => __('Complete'), 'price' => '99 €', 'unit' => __('/ month'), 'bullets' => __('A first included element')."\n".__('A second included element')."\n".__('A third included element'), 'button_label' => __('Contact us'), 'button_url' => '{button}', 'highlight' => true],
                ],
            ]],
            ['faq', [
                'title' => __('Frequently asked questions'),
                'style' => 'accordion',
                'items' => [
                    ['question' => __('How do I contact you?'), 'answer' => '<p>'.e(__('By e-mail at {email}, or with the contact form.')).'</p>'],
                    ['question' => __('How long does it take?'), 'answer' => '<p>'.e(__('Answer the questions your customers often ask you.')).'</p>'],
                ],
            ]],
        ];
    }

    private function contact(): array
    {
        return [
            ['page_header', [
                'title' => __('Contact'),
                'text' => __('A question, a request? Write to us, we will answer quickly.'),
            ]],
            ['contact_cards', []],
            ['contact_form', [
                'eyebrow' => __('Write to us'),
                'title' => __('Send us a message'),
                'text' => __('Fill in the form: your message arrives directly in our mailbox.'),
                'form_subjects' => __('General question')."\n".__('Quote request')."\n".__('Other'),
                'ask_phone' => true,
            ]],
        ];
    }

    private function legal(): array
    {
        $content = implode('', [
            '<h2>'.e(__('Site publisher')).'</h2>',
            '<p>{company_name}<br>{legal_status}<br>{address}<br>'.e(__('Registration number:')).' {registration_number}<br>'.e(__('E-mail:')).' {email}</p>',
            '<p>'.e(__('Publication director:')).' {publication_director}</p>',
            '<h2>'.e(__('Hosting')).'</h2>',
            '<p>{host_name}<br>{host_address}</p>',
            '<h2>'.e(__('Intellectual property')).'</h2>',
            '<p>'.e(__('All the content of this site (texts, pictures, logo) is protected. Any reproduction without prior authorisation is forbidden.')).'</p>',
        ]);

        return [
            ['page_header', ['title' => __('Legal notice'), 'note' => __('Fill in the "Legal information" in "Site information": this page updates automatically.')]],
            ['rich_text', ['content' => $content, 'width' => 'narrow']],
        ];
    }

    private function privacy(): array
    {
        $content = implode('', [
            '<h2>'.e(__('Data collected')).'</h2>',
            '<p>'.e(__('The contact form collects your name, your e-mail address, possibly your phone number, and your message. These data are only used to answer your request.')).'</p>',
            '<h2>'.e(__('Retention period')).'</h2>',
            '<p>'.e(__('Messages are stored encrypted and deleted automatically after 12 months.')).'</p>',
            '<h2>'.e(__('Cookies')).'</h2>',
            '<p>'.e(__('This site does not use any advertising or tracking cookie. Maps and videos from third-party services are only loaded if you choose to.')).'</p>',
            '<h2>'.e(__('Your rights')).'</h2>',
            '<p>'.e(__('You can access, correct or delete your data by writing to {email}.')).'</p>',
        ]);

        return [
            ['page_header', ['title' => __('Privacy policy')]],
            ['rich_text', ['content' => $content, 'width' => 'narrow']],
        ];
    }
}
