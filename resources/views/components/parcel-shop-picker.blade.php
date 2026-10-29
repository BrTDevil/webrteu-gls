@props([
    'name' => 'gls_psd_id',
    'value' => null,
    'country' => 'RO',
    'language' => null,
])

@php($widgetId = 'gls-dpm-'.\Illuminate\Support\Str::random(8))

@once
    <script type="module" src="{{ config('gls.map_widget') }}"></script>
@endonce

<div class="gls-parcel-shop-picker">
    <input type="hidden" id="{{ $widgetId }}-input" name="{{ $name }}" value="{{ $value }}" {{ $attributes->whereStartsWith('wire:model') }}>
    <gls-dpm id="{{ $widgetId }}" country="{{ strtolower($country) }}" language="{{ strtolower($language ?? $country) }}" {{ $attributes->whereDoesntStartWith('wire:model') }}></gls-dpm>
    <span id="{{ $widgetId }}-label"></span>
</div>

<script>
    document.getElementById(@json($widgetId)).addEventListener('change', function (event) {
        var point = event.detail || {};
        var input = document.getElementById(@json($widgetId.'-input'));
        input.value = point.id || '';
        input.dispatchEvent(new Event('input', { bubbles: true }));
        document.getElementById(@json($widgetId.'-label')).textContent = [point.name, point.contact && point.contact.address].filter(Boolean).join(', ');
    });
</script>
