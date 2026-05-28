<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DCP Distribution Report</title>
    <style>
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; color: #333; font-size: 9px; }
        .container { width: 100%; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 14px; }
        .header h1 { margin: 0; font-size: 16px; color: #1a56db; }
        .header p { margin: 2px 0; font-size: 11px; }
        .stats { width: 100%; border-collapse: collapse; margin: 10px 0 4px; }
        .stats td { width: 25%; border: 1px solid #ddd; padding: 8px; text-align: center; }
        .stats .num { font-size: 18px; font-weight: bold; color: #1a56db; }
        .stats .lbl { font-size: 8px; color: #555; }
        .chart { text-align: center; margin: 14px 0; page-break-inside: avoid; }
        .chart img { width: 90%; max-width: 720px; }
        .chart .missing { color: #999; font-style: italic; padding: 20px; border: 1px dashed #ccc; }
        h2 { font-size: 12px; color: #1a56db; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-top: 18px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { border: 1px solid #ddd; padding: 5px; text-align: center; }
        table.data th { background-color: #f2f2f2; color: #1a56db; font-weight: bold; }
        table.data td.level { text-align: left; font-weight: bold; }
        table.data tr.totals td { background-color: #eef2ff; font-weight: bold; }
        .footer { text-align: center; margin-top: 22px; font-size: 8px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Department of Education</h1>
            <p>DCP Distribution Dashboard Report</p>
        </div>

        {{-- Stats overview (mirrors DcpStatsOverview widget) --}}
        <table class="stats">
            <tr>
                <td><div class="num">{{ $totals['l4t'] }}</div><div class="lbl">Total L4T — Laptops for Teaching</div></td>
                <td><div class="num">{{ $totals['l4nt'] }}</div><div class="lbl">Total L4NT — Laptops for Non-Teaching</div></td>
                <td><div class="num">{{ $totals['stv'] }}</div><div class="lbl">Total Smart TV — SmartTV Packages</div></td>
                <td><div class="num">{{ $totals['psi_pop'] }}</div><div class="lbl">Overall PSI Population</div></td>
            </tr>
        </table>

        {{-- Charts (rendered server-side via QuickChart) --}}
        <div class="chart">
            @if($distributionChart)
                <img src="{{ $distributionChart }}" alt="DCP Distribution by School Level">
            @else
                <div class="missing">Distribution chart unavailable (chart service unreachable). Data is in the table below.</div>
            @endif
        </div>
        <div class="chart">
            @if($populationChart)
                <img src="{{ $populationChart }}" alt="Total ICT Packages and PSI Population">
            @else
                <div class="missing">Population chart unavailable (chart service unreachable). Data is in the table below.</div>
            @endif
        </div>

        {{-- Percentages summary table (mirrors DcpPercentagesTable widget) --}}
        <h2>DCP Distribution Percentages Summary</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>SCHOOL LEVEL</th>
                    <th>L4NT</th>
                    <th>L4T</th>
                    <th>STV</th>
                    <th>TOTAL</th>
                    <th>PSI POP</th>
                    <th>% ICT</th>
                    <th>% L4T</th>
                    <th>% STV</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td class="level">{{ $row['level'] }}</td>
                    <td>{{ $row['l4nt'] }}</td>
                    <td>{{ $row['l4t'] }}</td>
                    <td>{{ $row['stv'] }}</td>
                    <td>{{ $row['total'] }}</td>
                    <td>{{ $row['psi_pop'] }}</td>
                    <td>{{ $row['percent_ict'] }}</td>
                    <td>{{ $row['percent_l4t'] }}</td>
                    <td>{{ $row['percent_stv'] }}</td>
                </tr>
                @endforeach
                @php $pop = (int) $totals['psi_pop']; @endphp
                <tr class="totals">
                    <td class="level">TOTAL</td>
                    <td>{{ $totals['l4nt'] }}</td>
                    <td>{{ $totals['l4t'] }}</td>
                    <td>{{ $totals['stv'] }}</td>
                    <td>{{ $totals['total'] }}</td>
                    <td>{{ $pop }}</td>
                    <td>{{ $pop ? round(($totals['total'] / $pop) * 100) . '%' : '0%' }}</td>
                    <td>{{ $pop ? round(($totals['l4t'] / $pop) * 100) . '%' : '0%' }}</td>
                    <td>{{ $pop ? round(($totals['stv'] / $pop) * 100) . '%' : '0%' }}</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            Generated on {{ now()->format('F j, Y H:i:s') }}
        </div>
    </div>
</body>
</html>
