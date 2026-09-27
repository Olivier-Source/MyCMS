@extends('admin.layout', ['title' => __('Help')])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Help') }}</h1>
        <p class="mt-1 text-stone-500">{{ __('Answers to the most common questions.') }}</p>
    </div>

    <div class="space-y-3 max-w-3xl">
        @foreach ([
            [__('How do I change a text of my site?'), __('Go to "Pages", click on "Edit" next to the page, then on the section concerned. Change the text and click on "Save". The preview on the right shows the result.')],
            [__('How do I change a picture?'), __('In the section concerned, click on "Change" under the image, then choose an existing image or upload a new one from your computer or your phone. Remember to add a short description in "Pictures".')],
            [__('My phone number (or my address) has changed'), __('Change it only once in "Site information": it is updated automatically everywhere (header, footer, "Call" buttons, texts…).')],
            [__('How do I add a page?'), __('In "Pages", click on "New page", give it a title and choose a starting point. It stays invisible until you click on "Publish the page". To show it in the menu, turn on "Show in the main menu" in its "Settings".')],
            [__('How do I temporarily hide a page or a section?'), __('For a page: "Disable" button. For a section: click on the eye next to it. Nothing is deleted, you can show them again at any time.')],
            [__('How do I change the look of the site?'), __('"Colours & style" changes the colours and the options of the theme. "Themes" lets you choose another theme, or install one from a Git link or a ZIP file.')],
            [__('How do I publish my site in another language?'), __('In "Languages", tick the language and save. Then open each page and click on "Translate into…", and translate the "Site information" by choosing the language at the top of the screen.')],
            [__('I am going on holiday: how do I warn my visitors?'), __('In "Site information → Announcement", write your message, turn on "Show the banner" and possibly set the dates: the banner disappears automatically after the end date.')],
            [__('I lost my phone (two-factor authentication)'), __('Use one of your recovery codes on the verification screen ("Phone unavailable?" link). Without a recovery code, the person managing the server can reset the protection.')],
            [__('My account is blocked'), __('After 3 wrong passwords, logins are blocked for 5 minutes, then longer and longer in case of new errors. Wait, or use "Forgot your password?". The person managing the server can also unblock the account immediately.')],
        ] as [$question, $answer])
            <details class="ck-card group">
                <summary class="flex items-center justify-between gap-4 p-5 cursor-pointer list-none font-semibold">
                    {{ $question }}<x-icon name="expand_more" class="text-[22px] text-stone-400" />
                </summary>
                <div class="px-5 pb-5 -mt-1 text-[15px] text-stone-700 leading-relaxed">{{ $answer }}</div>
            </details>
        @endforeach

        <section class="ck-card p-5 sm:p-6">
            <h2 class="text-lg font-bold mb-1">{{ __('Information you can insert in your texts') }}</h2>
            <p class="text-sm text-stone-500 mb-4">{{ __('Write them with the braces: they are replaced automatically by the value of "Site information".') }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                @foreach ($tags as $tag => $label)
                    <div class="flex items-center justify-between gap-2 border-b border-stone-100 py-1.5">
                        <code class="rounded bg-stone-100 px-1.5 py-0.5 text-emerald-800">{{ '{'.$tag.'}' }}</code>
                        <span class="text-stone-500 text-right">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl bg-emerald-50 border border-emerald-200 p-5 sm:p-6">
            <h2 class="text-lg font-bold text-emerald-900">{{ __('MyCMS is free and open source') }}</h2>
            <p class="mt-1 text-emerald-900">{{ __('Documentation, themes, languages and help from the community:') }} <a href="{{ config('mycms.project_url') }}" target="_blank" rel="noopener" class="font-semibold underline break-all">{{ config('mycms.project_url') }}</a></p>
        </section>
    </div>
@endsection
