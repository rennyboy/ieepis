<?php
namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class QrScanner extends Component
{
    public $scanning = false;
    public $errorMessage = null;
    public $manualCode = '';

    // Mobile detection via Alpine (client side). The component itself does not enforce, but the view hides UI when not mobile.

    protected $listeners = ['qr-scanned' => 'handleScannedCode'];

    public function startScanning()
    {
        $this->scanning = true;
        $this->errorMessage = null;
        $this->dispatch('qr-start');
    }

    public function stopScanning()
    {
        $this->scanning = false;
    }

    // Called from JavaScript when a QR code is read
    public function handleScannedCode($data)
    {
        $code = is_array($data) && isset($data['value']) ? $data['value'] : (is_string($data) ? $data : '');
        
        $this->scanning = false;

        if (!$code) {
            $this->errorMessage = 'Invalid QR Code scanned.';
            return;
        }

        $parts = explode('-', $code);
        if (count($parts) !== 2) {
            $this->errorMessage = 'Unrecognized QR Code format.';
            return;
        }

        [$prefix, $id] = $parts;

        try {
            if ($prefix === 'EQ') {
                $equipment = \App\Models\Equipment::withTrashed()->findOrFail($id);
                return redirect(\App\Filament\Resources\EquipmentResource::getUrl('edit', ['record' => $equipment]));
            } elseif ($prefix === 'EM') {
                $employee = \App\Models\Employee::withTrashed()->findOrFail($id);
                return redirect(\App\Filament\Resources\EmployeeResource::getUrl('edit', ['record' => $employee]));
            } else {
                $this->errorMessage = 'Unknown QR prefix.';
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->errorMessage = 'Record not found.';
        }
    }

    // Manual entry submission
    public function submitManual()
    {
        $code = trim($this->manualCode);
        if ($code === '') {
            $this->errorMessage = 'Please enter a QR code.';
            return;
        }
        $this->handleScannedCode($code);
    }

    public function render()
    {
        return view('livewire.qr-scanner');
    }
}
