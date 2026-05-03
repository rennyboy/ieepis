<?php
namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class QrScanner extends Component
{
    public $scanning = false;
    public $errorMessage = null;
    public $manualCode = '';

    // Mobile detection via Alpine (client side). The component itself does not enforce, but the view hides UI when not mobile.

    public function startScanning()
    {
        $this->scanning = true;
        $this->errorMessage = null;
        $this->dispatchBrowserEvent('qr-start');
    }

    public function stopScanning()
    {
        $this->scanning = false;
    }

    // Called from JavaScript when a QR code is read
    public function handleScannedCode(string $code)
    {
        $this->scanning = false;
        $response = Http::get('/api/qr/' . $code);
        if ($response->successful()) {
            $redirect = $response->json('redirect');
            return redirect($redirect);
        }
        $this->errorMessage = $response->json('message') ?? 'Unexpected error';
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
