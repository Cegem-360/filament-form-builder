@if ($items !== [])
    <div class="ffb-help-links">
        @foreach ($items as $item)
            <x-filament::button tag="a" :href="$item['url']" size="sm" color="gray" icon="heroicon-m-arrow-right" icon-position="after">
                {{ $item['label'] }}
            </x-filament::button>
        @endforeach
    </div>
@endif
