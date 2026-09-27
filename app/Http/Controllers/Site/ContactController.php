<?php

namespace App\Http\Controllers\Site;

use App\Cms\Languages\LanguageManager;
use App\Cms\SiteSettings;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Notifications\NewContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ContactController extends Controller
{
    public function store(Request $request, SiteSettings $settings, LanguageManager $languages): RedirectResponse
    {
        // Error messages in the language of the page the form was sent from
        $locale = in_array($request->input('_locale'), $languages->siteLocales(), true) ? $request->input('_locale') : $languages->defaultLocale();
        app()->setLocale($locale);
        $settings->setContentLocale($locale);

        $back = redirect()->to(url()->previous().'#contact-form');

        // Bot traps: hidden field filled in, or form sent too quickly
        try {
            $startedAt = (int) Crypt::decryptString((string) $request->input('_ts'));
        } catch (\Throwable) {
            $startedAt = time();
        }
        if ($request->filled('website') || time() - $startedAt < 3) {
            return $back->with('contact_sent', true);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9 +().\-]*$/'],
            'subject' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => __('Please accept the privacy policy.'),
            'phone.regex' => __('The phone number contains characters that are not allowed.'),
        ], [
            'name' => __('name'), 'email' => __('e-mail'), 'phone' => __('phone'), 'message' => __('message'),
        ]);

        $message = ContactMessage::create(
            collect($data)->except('consent')->map(fn ($v) => $v === null ? null : strip_tags($v))->all() + ['locale' => $locale]
        );

        if ($settings->get('contact_notify')) {
            $to = $settings->get('contact_recipient') ?: $settings->get('email');
            try {
                Notification::route('mail', $to)->notify(
                    (new NewContactMessage($message, (bool) $settings->get('contact_include_content')))
                        ->locale($settings->get('admin_locale') ?: $languages->defaultLocale())
                );
            } catch (\Throwable $e) {
                // The message stays available in the administration even if the e-mail fails
                Log::warning('Contact notification not sent: '.$e->getMessage());
            }
        }

        return $back->with('contact_sent', true);
    }
}
