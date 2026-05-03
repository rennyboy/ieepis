<x-filament::page>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div id="offline-equipment-app"></div>

    @push('scripts')
        @vite(['resources/js/equipment.js'])
    @endpush
</x-filament::page>
