{{-- resources/views/filament/equipment/qrcode.blade.php --}}
<div class="flex flex-col items-center p-6 gap-4">
    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $record->brand }} {{ $record->model }}</div>
    <div class="text-sm text-gray-500">{{ $record->property_no }}</div>
    
    <div class="bg-white p-4 rounded-lg shadow-inner border border-gray-200">
        {!! QrCode::size(200)->generate($record->qr_code ?? "{$record->property_no}|{$record->serial_number}") !!}
    </div>

    <div class="w-full text-sm text-gray-600 dark:text-gray-400 space-y-1">
        <div class="flex justify-between">
            <span class="font-medium">Property No.:</span>
            <span class="font-mono">{{ $record->property_no }}</span>
        </div>
        <div class="flex justify-between">
            <span class="font-medium">Serial No.:</span>
            <span class="font-mono">{{ $record->serial_number }}</span>
        </div>
        <div class="flex justify-between">
            <span class="font-medium">School:</span>
            <span>{{ $record->school?->name }}</span>
        </div>
        <div class="flex justify-between">
            <span class="font-medium">Condition:</span>
            <span>{{ $record->condition }}</span>
        </div>
    </div>

    <p class="text-xs text-gray-400 text-center">
        Scan this QR code for quick equipment identification and inventory updates.
    </p>
</div>
