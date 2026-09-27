{{-- Contact form. Messages are stored (encrypted) and can be read in the administration. --}}
@php
    $subjects = $r->lines($d['form_subjects'] ?? '');
    $askPhone = $d['ask_phone'] ?? true;
    $privacyUrl = $settings->get('privacy_url') ? $r->href($settings->get('privacy_url')) : null;
@endphp
<div id="contact-form" class="scroll-mt-28">
    @if (session('contact_sent'))
        <div class="p-6 sm:p-8 rounded-2xl bg-surface-container-lowest shadow-sm text-center space-y-3" role="status">
            <div class="w-14 h-14 mx-auto rounded-full bg-primary-fixed/60 text-on-primary-fixed flex items-center justify-center"><x-icon name="mark_email_read" class="text-[28px]" /></div>
            <h3 class="font-headline text-xl font-bold text-on-surface">{{ __('Thank you, your message has been sent') }}</h3>
            <p class="text-sm text-on-surface-variant">{{ __('You will receive an answer as soon as possible.') }}</p>
        </div>
    @else
        @if (! empty($d['form_warning']))
            <div class="mb-6 p-4 rounded-xl bg-secondary-container text-on-secondary-container text-xs leading-relaxed space-y-1.5">
                <div class="flex items-center gap-2 font-semibold text-tertiary"><x-icon name="lock" class="text-[18px]" /><span>{{ __('Important note') }}</span></div>
                <p>{!! $r->t($d['form_warning']) !!}</p>
            </div>
        @endif

        <form method="POST" action="{{ site_url('/contact') }}" class="space-y-4" novalidate>
            @csrf
            <input type="hidden" name="_ts" value="{{ \Illuminate\Support\Facades\Crypt::encryptString((string) time()) }}">
            <input type="hidden" name="_locale" value="{{ $contentLocale }}">
            {{-- Trap for bots: invisible to visitors --}}
            <div class="absolute -left-[9999px] w-px h-px overflow-hidden" aria-hidden="true">
                <label for="cf-website">{{ __('Do not fill in') }}</label>
                <input id="cf-website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            @if ($errors->any())
                <div class="p-3 rounded-lg bg-error-container text-on-error-container text-sm" role="alert">
                    {{ __('Please correct the fields shown below.') }}
                </div>
            @endif

            @php
                $input = 'w-full px-3.5 py-2.5 rounded-lg bg-surface-container-lowest text-on-surface text-sm border border-outline-variant/60 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary shadow-sm';
            @endphp

            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1.5" for="cf-name">{{ __('Your name') }} <span class="text-error">*</span></label>
                <input id="cf-name" name="name" type="text" required maxlength="120" autocomplete="name" value="{{ old('name') }}" class="{{ $input }}">
                @error('name')<p class="mt-1 text-xs text-error font-semibold">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1.5" for="cf-email">{{ __('E-mail address') }} <span class="text-error">*</span></label>
                <input id="cf-email" name="email" type="email" required maxlength="190" autocomplete="email" value="{{ old('email') }}" class="{{ $input }}">
                @error('email')<p class="mt-1 text-xs text-error font-semibold">{{ $message }}</p>@enderror
            </div>
            @if ($askPhone)
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1.5" for="cf-phone">{{ __('Phone') }} <span class="text-on-surface-variant font-normal">({{ __('optional') }})</span></label>
                    <input id="cf-phone" name="phone" type="tel" maxlength="30" autocomplete="tel" value="{{ old('phone') }}" class="{{ $input }}">
                    @error('phone')<p class="mt-1 text-xs text-error font-semibold">{{ $message }}</p>@enderror
                </div>
            @endif
            @if ($subjects)
                <div>
                    <label class="block text-xs font-semibold text-on-surface mb-1.5" for="cf-subject">{{ __('Subject') }}</label>
                    <select id="cf-subject" name="subject" class="{{ $input }}">
                        @foreach ($subjects as $subject)
                            <option @selected(old('subject') === $subject)>{{ $subject }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="block text-xs font-semibold text-on-surface mb-1.5" for="cf-message">{{ __('Your message') }} <span class="text-error">*</span></label>
                <textarea id="cf-message" name="message" rows="5" required maxlength="5000" class="{{ $input }} leading-relaxed">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-xs text-error font-semibold">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-start gap-2.5 cursor-pointer pt-1">
                <input type="checkbox" name="consent" value="1" required @checked(old('consent')) class="mt-0.5 w-4 h-4 shrink-0 accent-primary">
                <span class="text-xs text-on-surface-variant leading-snug">
                    @if ($privacyUrl)
                        {!! $r->sentence('I agree that this information is used only to answer my request, in accordance with the :link.', ['link' => '<a href="'.e($privacyUrl).'" class="font-semibold text-primary underline underline-offset-2">'.e(__('privacy policy')).'</a>']) !!}
                    @else
                        {{ __('I agree that this information is used only to answer my request.') }}
                    @endif
                    <span class="text-error">*</span>
                </span>
            </label>
            @error('consent')<p class="text-xs text-error font-semibold">{{ $message }}</p>@enderror

            <button type="submit" class="w-full mt-2 py-3 px-5 rounded-xl bg-btn text-on-btn font-semibold text-sm shadow-md hover:bg-btn-hover hover:shadow-lg transition-all flex items-center justify-center gap-2">
                <x-icon name="send" class="text-[18px]" /><span>{{ __('Send my message') }}</span>
            </button>
            <p class="pt-1 text-[11px] text-on-surface-variant flex items-center justify-center gap-1 text-center">
                <x-icon name="security" class="text-[14px] text-primary" /><span>{{ __('Your data is never sold or shared.') }}</span>
            </p>
        </form>
    @endif
</div>
