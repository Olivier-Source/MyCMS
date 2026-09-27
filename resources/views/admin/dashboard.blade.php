@extends('admin.layout', ['title' => __('Dashboard')])

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Hello :name', ['name' => explode(' ', $user->name)[0]]) }} 👋</h1>
        <p class="mt-1 text-stone-500">
            {{ __('What would you like to do today?') }}
            @if ($previousLogin)
                <span class="block sm:inline text-sm">{{ __('Last login: :date.', ['date' => $previousLogin->created_at->translatedFormat('l j F, H:i')]) }}</span>
            @endif
        </p>
    </div>

    {{-- Shortcuts --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach ([
            ['route' => route('admin.pages.index'), 'icon' => 'edit_note', 'title' => __('Edit my pages'), 'text' => __(':published of :total page(s) online', ['published' => $publishedCount, 'total' => $pagesCount])],
            ['route' => route('admin.settings.edit'), 'icon' => 'badge', 'title' => __('My information'), 'text' => __('Name, address, phone, hours…')],
            ['route' => route('admin.messages.index'), 'icon' => 'mail', 'title' => __('My messages'), 'text' => $unread ? trans_choice(':count unread|:count unread', $unread) : __('No new message')],
            ['route' => route('admin.appearance.edit'), 'icon' => 'palette', 'title' => __('My colours'), 'text' => __('Change the look of the site')],
        ] as $card)
            <a href="{{ $card['route'] }}" class="ck-card p-5 hover:border-emerald-300 hover:shadow-md transition group">
                <span class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center group-hover:bg-emerald-700 group-hover:text-white transition"><x-icon :name="$card['icon']" class="text-[24px]" /></span>
                <p class="mt-4 font-bold">{{ $card['title'] }}</p>
                <p class="text-sm text-stone-500">{{ $card['text'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <section class="ck-card p-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold">{{ __('Latest messages') }}</h2>
                    <a href="{{ route('admin.messages.index') }}" class="text-sm font-semibold text-emerald-800 hover:underline">{{ __('See all') }}</a>
                </div>
                @forelse ($latestMessages as $message)
                    <a href="{{ route('admin.messages.show', $message) }}" class="flex items-center gap-3 rounded-xl px-3 py-3 -mx-3 hover:bg-stone-50">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $message->read_at ? 'bg-stone-200' : 'bg-emerald-600' }}"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate {{ $message->read_at ? '' : 'font-bold' }}">{{ $message->name }}</span>
                            <span class="block text-sm text-stone-500 truncate">{{ $message->subject ?: \Illuminate\Support\Str::limit($message->message, 60) }}</span>
                        </span>
                        <span class="text-xs text-stone-400 shrink-0">{{ $message->created_at->diffForHumans() }}</span>
                    </a>
                @empty
                    <p class="text-stone-500 text-sm">{{ __('No message yet.') }}</p>
                @endforelse
            </section>

            <section class="ck-card p-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold">{{ __('Recently edited pages') }}</h2>
                    <a href="{{ route('admin.pages.index') }}" class="text-sm font-semibold text-emerald-800 hover:underline">{{ __('All pages') }}</a>
                </div>
                <div class="divide-y divide-stone-100">
                    @foreach ($recentPages as $p)
                        <a href="{{ route('admin.pages.edit', $p) }}" class="flex items-center justify-between gap-3 py-3 hover:text-emerald-800">
                            <span class="font-medium">{{ $p->title }} <span class="ck-badge bg-stone-100 text-stone-600 uppercase ml-1">{{ $p->locale }}</span></span>
                            <span class="text-xs text-stone-400">{{ $p->updated_at->diffForHumans() }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="ck-card p-5 sm:p-6">
                <h2 class="text-lg font-bold mb-4">{{ __('Before going live') }}</h2>
                <ul class="space-y-3">
                    @foreach ($checklist as $item)
                        <li class="flex items-start gap-3 text-sm">
                            <x-icon :name="$item['ok'] ? 'check_circle' : 'radio_button_unchecked'" class="text-[20px] mt-0.5 {{ $item['ok'] ? 'text-emerald-600' : 'text-stone-300' }}" />
                            @if ($item['route'] && ! $item['ok'])
                                <a href="{{ $item['route'] }}" class="text-stone-700 hover:text-emerald-800 hover:underline">{{ $item['label'] }}</a>
                            @else
                                <span class="{{ $item['ok'] ? 'text-stone-500' : 'text-stone-700' }}">{{ $item['label'] }}{{ ! $item['ok'] && ! $item['route'] ? ' ('.__('in the .env file').')' : '' }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="ck-card p-5 sm:p-6">
                <h2 class="text-lg font-bold mb-4 flex items-center gap-2"><x-icon name="shield_lock" class="text-[22px] text-emerald-700" />{{ __('Security') }}</h2>
                <ul class="space-y-3 text-sm">
                    @if ($user->hasTwoFactorEnabled())
                        <li class="flex items-center gap-2"><x-icon name="check_circle" class="text-[18px] text-emerald-600" />{{ __('Two-factor authentication enabled') }}</li>
                        <li class="flex items-center gap-2">
                            <x-icon :name="$recoveryLeft > 2 ? 'check_circle' : 'warning'" class="text-[18px] {{ $recoveryLeft > 2 ? 'text-emerald-600' : 'text-amber-500' }}" />
                            {{ trans_choice(':count recovery code left|:count recovery codes left', $recoveryLeft) }}
                        </li>
                    @else
                        <li class="flex items-center gap-2"><x-icon name="warning" class="text-[18px] text-amber-500" /><a href="{{ route('admin.two-factor.setup') }}" class="hover:underline">{{ __('Two-factor authentication is not enabled: enable it') }}</a></li>
                    @endif
                    <li class="flex items-center gap-2">
                        <x-icon :name="$failedLogins ? 'warning' : 'check_circle'" class="text-[18px] {{ $failedLogins ? 'text-amber-500' : 'text-emerald-600' }}" />
                        {{ $failedLogins ? trans_choice(':count failed login attempt this week|:count failed login attempts this week', $failedLogins) : __('No suspicious attempt this week') }}
                    </li>
                </ul>
                <a href="{{ route('admin.activity') }}" class="mt-4 inline-block text-sm font-semibold text-emerald-800 hover:underline">{{ __('See the log') }}</a>
            </section>
        </div>
    </div>
@endsection
