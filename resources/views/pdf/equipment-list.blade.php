<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Equipment Inventory</title>
    <style>
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; color: #333; font-size: 8px; }
        .container { width: 100%; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16px; color: #1a56db; }
        .header p { margin: 2px 0; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; color: #1a56db; font-weight: bold; }
        .footer { text-align: center; margin-top: 30px; font-size: 7px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Department of Education</h1>
            <p>ICT Equipment Inventory Report</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Property No.</th>
                    <th>Category</th>
                    <th>Brand/Model</th>
                    <th>Serial Number</th>
                    <th>School</th>
                    <th>Accountable Officer</th>
                    <th>Condition</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($equipment as $item)
                <tr>
                    <td>{{ $item->property_no }}</td>
                    <td>{{ $item->equipment_type }}</td>
                    <td>{{ $item->brand }} {{ $item->model }}</td>
                    <td>{{ $item->serial_number }}</td>
                    <td>{{ $item->school?->name }}</td>
                    <td>{{ $item->activeAssignment?->employee?->full_name ?? 'None' }}</td>
                    <td>{{ $item->condition }}</td>
                    <td>{{ ucfirst($item->accountability_status) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            Generated on {{ now()->format('F j, Y H:i:s') }}
        </div>
    </div>
</body>
</html>
