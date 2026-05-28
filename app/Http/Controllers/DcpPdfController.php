<?php

namespace App\Http\Controllers;

use App\Filament\Pages\DcpDistributionData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DcpPdfController extends Controller
{
    public function export()
    {
        $rows = collect(DcpDistributionData::getData());
        $totals = DcpDistributionData::getTotals();

        $labels = $rows->pluck('level')->values()->all();

        // Mirror the two dashboard ChartWidgets exactly (same datasets/colors)
        // so the PDF charts match the on-screen ones.
        $distributionChart = $this->chartImage('bar', $labels, [
            ['label' => 'Laptop (Non-Teaching) - L4NT', 'data' => $rows->pluck('l4nt')->all(), 'backgroundColor' => '#3b82f6'],
            ['label' => 'Laptop (Teaching) - L4T', 'data' => $rows->pluck('l4t')->all(), 'backgroundColor' => '#10b981'],
            ['label' => 'Smart TV - STV', 'data' => $rows->pluck('stv')->all(), 'backgroundColor' => '#f59e0b'],
        ], 'DCP Distribution by School Level');

        $populationChart = $this->chartImage('bar', $labels, [
            ['label' => 'Total ICT Packages', 'data' => $rows->pluck('total')->all(), 'backgroundColor' => '#6366f1'],
            ['label' => 'PSI Population (Teachers/Employees)', 'data' => $rows->pluck('psi_pop')->all(), 'backgroundColor' => '#ec4899'],
        ], 'Total ICT Packages and PSI Population');

        $pdf = Pdf::loadView('pdf.dcp-distribution', [
            'rows' => $rows->all(),
            'totals' => $totals,
            'distributionChart' => $distributionChart,
            'populationChart' => $populationChart,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('dcp-distribution-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Render a Chart.js config to a PNG via QuickChart and return it as a
     * base64 data URI (DomPDF embeds these directly, no remote fetch).
     * Returns null on any failure so the PDF still renders without the chart.
     */
    private function chartImage(string $type, array $labels, array $datasets, string $title): ?string
    {
        try {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->retry(2, 500)
                ->post('https://quickchart.io/chart', [
                    'width' => 900,
                    'height' => 360,
                    'format' => 'png',
                    'backgroundColor' => 'white',
                    'chart' => [
                        'type' => $type,
                        'data' => ['labels' => $labels, 'datasets' => $datasets],
                        'options' => [
                            'plugins' => [
                                'title' => ['display' => true, 'text' => $title],
                                'legend' => ['position' => 'bottom'],
                            ],
                            'scales' => ['y' => ['beginAtZero' => true]],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('QuickChart render failed for DCP PDF', ['status' => $response->status()]);

                return null;
            }

            return 'data:image/png;base64,'.base64_encode($response->body());
        } catch (\Throwable $e) {
            Log::warning('QuickChart unreachable for DCP PDF', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
