<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnel List</title>
    <style>
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; color: #333; font-size: 9px; }
        .container { width: 100%; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16px; color: #1a56db; }
        .header p { margin: 2px 0; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; color: #1a56db; font-weight: bold; }
        .footer { text-align: center; margin-top: 30px; font-size: 8px; color: #777; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Department of Education</h1>
            <h2>LIST OF PERSONNEL</h2>
        </div>

        <table>
            <thead>
                <tr>
                    <th>FullName</th>
                    <th>Employee No.</th>
                    <th>Position</th>
                    <th>School / Office</th>
                    <th>Status</th>
                    <th class="text-center">ICT Equipment</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee)
                <tr>
                    <td>{{ $employee->full_name }}</td>
                    <td>{{ $employee->employee_number }}</td>
                    <td>{{ $employee->position }}</td>
                    <td>{{ $employee->school?->name }}</td>
                    <td>{{ ucfirst($employee->status) }}</td>
                    <td class="text-center">{{ $employee->activeAssignments()->count() }}</td>
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
