<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Physical Count of PPE - {{ $count->count_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 8px;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 14px;
            color: #1a56db;
            text-transform: uppercase;
        }
        .header h2 {
            margin: 3px 0;
            font-size: 11px;
            color: #333;
            font-weight: normal;
        }
        .header p {
            margin: 2px 0;
            font-size: 9px;
        }
        .meta-info {
            margin-bottom: 10px;
            font-size: 9px;
        }
        .meta-info table {
            width: 100%;
            border: none;
        }
        .meta-info td {
            border: none;
            padding: 2px 5px;
            vertical-align: top;
        }
        .meta-label {
            font-weight: bold;
            width: 120px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table.items th,
        table.items td {
            border: 1px solid #999;
            padding: 3px 4px;
            text-align: left;
        }
        table.items th {
            background-color: #e8edf2;
            color: #1a56db;
            font-weight: bold;
            font-size: 7px;
            text-align: center;
        }
        table.items td {
            font-size: 7px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .grand-total {
            font-weight: bold;
            background-color: #f5f5f5;
        }
        .shortage {
            color: #dc2626;
        }
        .overage {
            color: #16a34a;
        }
        .signatures {
            margin-top: 30px;
            width: 100%;
            font-size: 9px;
        }
        .signatures td {
            border: none;
            padding: 5px 15px;
            text-align: center;
            vertical-align: bottom;
        }
        .sig-line {
            border-top: 1px solid #333;
            margin-top: 30px;
            padding-top: 3px;
            font-weight: bold;
        }
        .sig-label {
            font-size: 8px;
            color: #666;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 7px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Department of Education</h1>
            <h2>PHYSICAL COUNT OF PROPERTY, PLANT AND EQUIPMENT (PPE)</h2>
            <p>As at {{ $count->inventory_date->format('F j, Y') }}</p>
        </div>

        <div class="meta-info">
            <table>
                <tr>
                    <td class="meta-label">Count Number:</td>
                    <td>{{ $count->count_number }}</td>
                    <td class="meta-label">Inventory Period:</td>
                    <td>{{ $count->inventory_period ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Office / School:</td>
                    <td>{{ $count->school?->name ?? '—' }}</td>
                    <td class="meta-label">Location:</td>
                    <td>{{ $count->location ?? '—' }}</td>
                </tr>
            </table>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 3%;">#</th>
                    <th style="width: 10%;">Article</th>
                    <th style="width: 14%;">Description</th>
                    <th style="width: 8%;">Property No.</th>
                    <th style="width: 5%;">UoM</th>
                    <th style="width: 7%;">Unit Value</th>
                    <th style="width: 7%;">Qty per<br>Property Card</th>
                    <th style="width: 7%;">Qty per<br>Physical Count</th>
                    <th style="width: 6%;">Shortage<br>Qty</th>
                    <th style="width: 8%;">Shortage<br>Value</th>
                    <th style="width: 6%;">Overage<br>Qty</th>
                    <th style="width: 8%;">Overage<br>Value</th>
                    <th style="width: 11%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->article }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->property_number }}</td>
                    <td class="text-center">{{ $item->unit_of_measure }}</td>
                    <td class="text-right">{{ number_format($item->unit_value, 2) }}</td>
                    <td class="text-center">{{ $item->quantity_property_card }}</td>
                    <td class="text-center">{{ $item->quantity_physical_count }}</td>
                    <td class="text-center {{ $item->shortage_quantity > 0 ? 'shortage' : '' }}">
                        {{ $item->shortage_quantity ?: '—' }}
                    </td>
                    <td class="text-right {{ $item->shortage_value > 0 ? 'shortage' : '' }}">
                        {{ $item->shortage_value > 0 ? number_format($item->shortage_value, 2) : '—' }}
                    </td>
                    <td class="text-center {{ $item->overage_quantity > 0 ? 'overage' : '' }}">
                        {{ $item->overage_quantity ?: '—' }}
                    </td>
                    <td class="text-right {{ $item->overage_value > 0 ? 'overage' : '' }}">
                        {{ $item->overage_value > 0 ? number_format($item->overage_value, 2) : '—' }}
                    </td>
                    <td>{{ $item->remarks }}</td>
                </tr>
                @endforeach

                <tr class="grand-total">
                    <td colspan="8" class="text-right">GRAND TOTAL</td>
                    <td class="text-center shortage">{{ $items->sum('shortage_quantity') ?: '—' }}</td>
                    <td class="text-right shortage">{{ $totalShortageValue > 0 ? number_format($totalShortageValue, 2) : '—' }}</td>
                    <td class="text-center overage">{{ $items->sum('overage_quantity') ?: '—' }}</td>
                    <td class="text-right overage">{{ $totalOverageValue > 0 ? number_format($totalOverageValue, 2) : '—' }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <table class="signatures">
            <tr>
                <td>
                    <div class="sig-line">
                        {{ $count->conductedByEmployee?->full_name ?? '________________________' }}
                    </div>
                    <div class="sig-label">Prepared / Conducted By</div>
                </td>
                <td>
                    <div class="sig-line">
                        {{ $count->verifiedByEmployee?->full_name ?? '________________________' }}
                    </div>
                    <div class="sig-label">Verified By</div>
                </td>
            </tr>
        </table>

        <div class="footer">
            Generated on {{ now()->format('F j, Y H:i:s') }} &mdash; IEEPIS Physical Count Report
        </div>
    </div>
</body>
</html>
