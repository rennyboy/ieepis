<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile - {{ $employee->full_name }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 10px;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #1a56db;
        }
        .header p {
            margin: 2px 0;
            font-size: 12px;
        }
        .section {
            margin-bottom: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 10px;
            background-color: #f9f9f9;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1a56db;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px 10px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            font-size: 9px;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 10px;
        }
        .info-value.bold {
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 9px;
            color: #777;
        }
        .page-number {
            text-align: center;
            margin-top: 20px;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"header\">
            <h1>Department of Education</h1>
            <p>{{ optional($employee->school)->division ?? 'Division of [Unknown]' }}</p>
            <p>School: {{ optional($employee->school)->name ?? 'N/A' }}</p>
            <h1>Personnel Profile</h1>
        </div>

        <div class=\"section\">
            <div class=\"section-title\">Personal Information</div>
            <div class=\"info-grid\">
                <div class=\"info-item\">
                    <span class=\"info-label\">Full Name</span>
                    <span class=\"info-value bold\">{{ $employee->full_name }}</span>
                </div>
                <div class=\"info-item\">
                    <span class=\"info-label\">Employee Number</span>
                    <span class=\"info-value\">{{ $employee->employee_number }}</span>
                </div>
                <div class=\"info-item\">
                    <span class=\"info-label\">Position / Designation</span>
                    <span class=\"info-value\">{{ $employee->position }}</span>
                </div>
                <div class=\"info-item\">
                    <span class=\"info-label\">Employment Type</span>
                    <span class=\"info-value\">{{ ucfirst($employee->employment_type) }}</span>
                </div>
                <div class=\"info-item\">
                    <span class=\"info-label\">DepEd Email</span>
                    <span class=\"info-value\">{{ $employee->email }}</span>
                </div>
                <div class=\"info-item\">
                    <span class=\"info-label\">Mobile No.</span>
                    <span class=\"info-value\">{{ $employee->mobile_1 }} {{ $employee->mobile_2 ? '/ ' . $employee->mobile_2 : '' }}</span>
                </div>
                 <div class=\"info-item\" style=\"grid-column: 1 / -1;\">
                    <span class=\"info-label\">School / Office</span>
                    <span class=\"info-value bold\">{{ optional($employee->school)->name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <div class=\"section\">
            <div class=\"section-title\">Employment Details</div>
            <div class=\"info-grid\">
                <div class=\"info-item\">
                    <span class=\"info-label\">Department / Division</span>
                    <span class=\"info-value\">{{ $employee->department }}</span>
                </div>
                <div class=\"info-item\">
                    <span class=\"info-label\">RO Office</span>
                    <span class=\"info-value\">{{ $employee->ro_office }}</span>
                </div>
                <div class=\"info-item\">
                    <span class=\"info-label\">SDO Office</span>
                    <span class=\"info-value\">{{ $employee->sdo_office }}</span>
                </div>
                <div class=\"info-item\">
                    <span class=\"info-label\">Date Hired</span>
                    <span class=\"info-value\">{{ $employee->date_hired ? $employee->date_hired->format('F j, Y') : 'N/A' }}</span>
                </div>
                <div class=\"info-item\">
                    <span class=\"info-label\">Officer-In-Charge (OIC)</span>
                    <span class=\"info-value\">{{ $employee->is_oic ? 'Yes' : 'No' }}</span>
                </div>
                @if($employee->is_oic)
                <div class=\"info-item\">
                    <span class=\"info-label\">OIC Office / Division</span>
                    <span class=\"info-value\">{{ $employee->oic_office }}</span>
                </div>
                @endif
                <div class=\"info-item\">
                    <span class=\"info-label\">Non-DepEd Funded</span>
                    <span class=\"info-value\">{{ $employee->is_non_deped_funded ? 'Yes' : 'No' }}</span>
                </div>
                @if($employee->is_non_deped_funded)
                <div class=\"info-item\">
                    <span class=\"info-label\">Source of Funds</span>
                    <span class=\"info-value\">{{ $employee->source_of_funds }}</span>
                </div>
                @endif
                 <div class=\"info-item\">
                    <span class=\"info-label\">Status</span>
                    <span class=\"info-value bold\">{{ ucfirst($employee->status) }}</span>
                </div>
            </div>
        </div>

        @if ($employee->date_of_separation || $employee->cause_of_separation || $employee->detailed_from || $employee->detailed_to)
        <div class=\"section\">
            <div class=\"section-title\">Separation Details</div>
            <div class=\"info-grid\">
                <div class=\"info-item\">
                    <span class=\"info-label\">Date of Separation</span>
                    <span class=\"info-value\">{{ $employee->date_of_separation ? $employee->date_of_separation->format('F j, Y') : 'N/A' }}</span>
                </div>
                <div class=\"info-item\">
                    <span class=\"info-label\">Cause of Separation</span>
                    <span class=\"info-value\">{{ $employee->cause_of_separation ?? 'N/A' }}</span>
                </div>
                 <div class=\"info-item\">
                    <span class=\"info-label\">Detailed/Transferred From</span>
                    <span class=\"info-value\">{{ $employee->detailed_from ?? 'N/A' }}</span>
                </div>
                 <div class=\"info-item\">
                    <span class=\"info-label\">Detailed/Transferred To</span>
                    <span class=\"info-value\">{{ $employee->detailed_to ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
        @endif

        <div class=\"section\">
            <div class=\"section-title\">Accountability</div>
            <div class=\"info-grid\">
                <div class=\"info-item\">
                    <span class=\"info-label\">Current Equipment Count</span>
                    <span class=\"info-value bold\">{{ $employee->activeAssignments()->count() }}</span>
                </div>
            </div>
        </div>

        <div class=\"footer\">
            Generated on {{ now()->format('F j, Y H:i:s') }}
        </div>
    </div>
</body>
</html>
