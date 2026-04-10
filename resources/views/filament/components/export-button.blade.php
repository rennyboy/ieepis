@php
    $user = auth()->user();
    $canExport = $user && $user->hasAnyRole(['super-admin', 'admin', 'school-admin']);
@endphp

@if($canExport)
    <div class="flex items-center">
        <x-filament::button
            href="{{ route($route) }}"
            tag="a"
            color="success"
            icon="heroicon-o-document-arrow-down"
            size="sm"
            target="_blank"
        >
            {{ $label }}
        </x-filament::button>
    </div>
@endif
