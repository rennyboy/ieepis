<x-filament-panels::page>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Welcome! Please set up your school profile.</h2>
        <form action="{{ route('filament.admin.auth.logout') }}" method="post">
            @csrf
            <x-filament::button type="submit" color="gray" size="sm" icon="heroicon-o-arrow-left-on-rectangle">
                Sign Out
            </x-filament::button>
        </form>
    </div>

    <form wire:submit="create">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            <x-filament::button type="submit" size="lg">
                Finalize and Complete Setup
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
