{{-- Preview of an image field (inside an imageField Alpine component) --}}
<div class="flex items-center gap-4">
    <div class="w-28 h-20 shrink-0 rounded-xl overflow-hidden border border-stone-200 bg-stone-100 flex items-center justify-center text-stone-400">
        <template x-if="media"><img :src="media.url" alt="" class="w-full h-full object-cover"></template>
        <template x-if="!media && value"><span class="text-xs px-2 text-center">{{ __('Image chosen') }}</span></template>
        <template x-if="!value"><x-icon name="image" class="text-[28px]" /></template>
    </div>
    <div class="flex flex-wrap gap-2">
        <button type="button" class="ck-btn-secondary py-2" @click="choose()"><x-icon name="add_photo_alternate" class="text-[18px]" /><span x-text="value ? @js(__('Change')) : @js(__('Choose an image'))"></span></button>
        <button type="button" class="ck-btn-ghost py-2" x-show="value" @click="clear()">{{ __('Remove') }}</button>
    </div>
</div>
