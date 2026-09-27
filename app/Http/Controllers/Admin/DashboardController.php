<?php

namespace App\Http\Controllers\Admin;

use App\Cms\SiteSettings;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ContactMessage;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, SiteSettings $settings): View
    {
        $user = $request->user();

        // A short checklist before / after going live
        $checklist = [
            ['ok' => $settings->get('site_name') !== 'My Website' && $settings->get('site_name') !== __('My Website'), 'label' => __('Site name chosen'), 'route' => route('admin.settings.edit', 'identity')],
            ['ok' => $settings->get('email') !== 'contact@example.com', 'label' => __('Contact e-mail filled in'), 'route' => route('admin.settings.edit', 'contact')],
            ['ok' => (bool) ($settings->get('logo_id') || $settings->get('initials') !== 'MW'), 'label' => __('Logo or initials chosen'), 'route' => route('admin.settings.edit', 'identity')],
            ['ok' => (bool) $settings->get('host_name'), 'label' => __('Hosting provider filled in for the legal notice'), 'route' => route('admin.settings.edit', 'company')],
            ['ok' => config('mail.default') !== 'log', 'label' => __('E-mail sending configured (alerts, forgotten password)'), 'route' => null],
            ['ok' => (bool) $settings->get('allow_indexing'), 'label' => __('Site visible on search engines'), 'route' => route('admin.settings.edit', 'seo')],
        ];

        return view('admin.dashboard', [
            'user' => $user,
            'unread' => ContactMessage::whereNull('read_at')->count(),
            'latestMessages' => ContactMessage::latest()->limit(4)->get(),
            'pagesCount' => Page::count(),
            'publishedCount' => Page::published()->count(),
            'recentPages' => Page::latest('updated_at')->limit(4)->get(),
            'failedLogins' => ActivityLog::whereIn('action', ['login.failed', 'login.locked'])->where('created_at', '>=', now()->subDays(7))->count(),
            'previousLogin' => ActivityLog::where('user_id', $user->id)->where('action', 'login')->latest('id')->skip(1)->first(),
            'checklist' => $checklist,
            'recoveryLeft' => count($user->two_factor_recovery_codes ?? []),
        ]);
    }
}
