@extends('admin.layout', ['title' => __('Message from :name', ['name' => $message->name])])

@section('content')
    <a href="{{ route('admin.messages.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-stone-500 hover:text-stone-900 mb-4"><x-icon name="arrow_back" class="text-[18px]" />{{ __('Messages') }}</a>

    <article class="ck-card p-5 sm:p-8">
        <header class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-5 border-b border-stone-200">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold">{{ $message->subject ?: __('Message without subject') }}</h1>
                <p class="mt-1 text-stone-500 text-sm">{{ __('Received on :date', ['date' => $message->created_at->translatedFormat('l j F Y, H:i')]) }}{{ $message->locale ? ' · '.strtoupper($message->locale) : '' }}</p>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="mailto:{{ $message->email }}?subject={{ rawurlencode(__('Re:').' '.($message->subject ?: __('your message'))) }}" class="ck-btn-primary"><x-icon name="mail" class="text-[18px]" />{{ __('Reply') }}</a>
                @if ($message->phone)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $message->phone) }}" class="ck-btn-secondary"><x-icon name="call" class="text-[18px]" />{{ __('Call') }}</a>
                @endif
            </div>
        </header>

        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 py-5 border-b border-stone-200 text-sm">
            <div><dt class="text-stone-500">{{ __('Name') }}</dt><dd class="font-semibold">{{ $message->name }}</dd></div>
            <div><dt class="text-stone-500">{{ __('E-mail') }}</dt><dd class="font-semibold break-all">{{ $message->email }}</dd></div>
            <div><dt class="text-stone-500">{{ __('Phone') }}</dt><dd class="font-semibold">{{ $message->phone ?: '—' }}</dd></div>
        </dl>

        <div class="pt-5 text-[15px] leading-relaxed whitespace-pre-line">{{ $message->message }}</div>

        <footer class="mt-8 pt-5 border-t border-stone-200 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('admin.messages.unread', $message) }}">
                @csrf
                <button type="submit" class="ck-btn-secondary"><x-icon name="mark_email_unread" class="text-[18px]" />{{ __('Mark as unread') }}</button>
            </form>
            <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" @submit="if (!confirm(@js(__('Permanently delete this message?')))) $event.preventDefault()">
                @csrf @method('DELETE')
                <button type="submit" class="ck-btn-danger"><x-icon name="delete" class="text-[18px]" />{{ __('Delete') }}</button>
            </form>
        </footer>
    </article>
@endsection
