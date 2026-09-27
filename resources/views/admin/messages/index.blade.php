@extends('admin.layout', ['title' => __('Messages')])

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold">{{ __('Messages') }}</h1>
        <p class="mt-1 text-stone-500">{{ __('Messages sent from the contact form. They are encrypted and deleted automatically after :months months (GDPR).', ['months' => $retention]) }}</p>
    </div>

    <div class="ck-card divide-y divide-stone-100">
        @forelse ($messages as $message)
            <a href="{{ route('admin.messages.show', $message) }}" class="flex items-center gap-4 px-4 sm:px-5 py-4 hover:bg-stone-50 first:rounded-t-2xl last:rounded-b-2xl">
                <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $message->read_at ? 'bg-stone-200' : 'bg-emerald-600' }}" title="{{ $message->read_at ? __('Read') : __('Unread') }}"></span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate {{ $message->read_at ? 'text-stone-700' : 'font-bold' }}">{{ $message->name }} <span class="font-normal text-stone-500">· {{ $message->email }}</span></span>
                    <span class="block text-sm text-stone-500 truncate">{{ $message->subject ? $message->subject.' — ' : '' }}{{ \Illuminate\Support\Str::limit($message->message, 90) }}</span>
                </span>
                <span class="text-xs text-stone-400 shrink-0 text-right">{{ $message->created_at->translatedFormat('j M') }}<span class="block">{{ $message->created_at->format('H:i') }}</span></span>
            </a>
        @empty
            <div class="p-10 text-center">
                <x-icon name="inbox" class="text-[48px] text-stone-300" />
                <p class="mt-3 font-semibold">{{ __('No message.') }}</p>
                <p class="text-stone-500">{{ __('The messages of the contact form will appear here.') }}</p>
            </div>
        @endforelse
    </div>
    <div class="mt-6">{{ $messages->links() }}</div>
@endsection
