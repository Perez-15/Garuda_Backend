<!DOCTYPE html>
<html>
<head>
    <title>Recruitment Report</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; font-size: 12px; margin: 0; padding: 0; }

        /* Header Layout */
        .header-container { width: 100%; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; margin-bottom: 20px; }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
        }

        .report-label { font-size: 12px; font-weight: bold; color: #718096; margin-bottom: 2px; }
        .report-date { font-size: 10px; color: #a0aec0; }

        /* Tables & Summary */
        .summary-box { margin: 15px 0; padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .summary-item { font-weight: bold; color: #4a5568; }
        .badge { font-weight: bold; color: #2f855a; background: #f0fff4; padding: 2px 5px; border-radius: 4px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 10px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; }
        th { background: #f1f5f9; font-weight: bold; color: #475569; text-transform: uppercase; font-size: 10px; }

        h3 { color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 20px; }
        .period-text { margin-bottom: 15px; font-size: 11px; color: #4a5568; }
    </style>
</head>
<body>

    @php
        $path = public_path('images/Garuda_Transparent.png');
        $base64 = '';
        if (file_exists($path)) {
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
    @endphp

    <div class="header-container">
        <table style="width: 100%; border: none; margin: 0; padding: 0;">
            <tr style="border: none;">
                <td style="border: none; width: 60%; vertical-align: middle;">
                    <table style="border: none; margin: 0; padding: 0;">
                        <tr style="border: none;">
                            @if($base64)
                            <td style="border: none; width: 60px; vertical-align: middle; padding-right: 10px;">
                                <img src="{{ $base64 }}" style="width: 55px; height: 55px;" />
                            </td>
                            @endif
                            <td style="border: none; vertical-align: middle;">
                                <span class="company-name">{{ $companyName }}</span>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="border: none; width: 40%; text-align: right; vertical-align: middle;">
                    <div class="report-label">PERFORMANCE REPORT</div>
                    <div class="report-date">Generated: {{ now()->format('d M Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="period-text">
        <strong>Reporting Period:</strong> {{ $startDate ?? 'All Time' }} to {{ $endDate ?? 'Present' }}
    </div>

    <div class="summary-box">
        <table style="border:none; margin:0; width: 100%;">
            <tr style="border:none;">
                <td style="border:none; width: 33%;">
                    <span class="summary-item">Total Applicants:</span> {{ $totalApplicants }}
                </td>
                <td style="border:none; width: 33%;">
                    <span class="summary-item">Hired:</span> {{ $hired }}
                </td>
                <td style="border:none; width: 33%; text-align: right;">
                    <span class="summary-item">Conversion Rate:</span>
                    <span class="badge">{{ $conversionRate }}%</span>
                </td>
            </tr>
        </table>
    </div>

    <div style="width: 100%;">
        <div style="width: 48%; float: left;">
            <h3>Applicants by Source</h3>
            <table>
                <thead>
                    <tr><th>Source</th><th>Count</th></tr>
                </thead>
                <tbody>
                    @foreach($bySource as $source => $count)
                    <tr><td>{{ $source }}</td><td>{{ $count }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="width: 48%; float: right;">
            <h3>Applicants by Status</h3>
            <table>
                <thead>
                    <tr><th>Status</th><th>Count</th></tr>
                </thead>
                <tbody>
                    @foreach($byStatus as $status => $count)
                    <tr><td>{{ ucfirst($status) }}</td><td>{{ $count }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="clear"></div>
    </div>

    <h3>Branch Performance</h3>
    <table>
        <thead>
            <tr>
                <th>Branch Name</th>
                <th>Total Applicants</th>
            </tr>
        </thead>
        <tbody>
            @forelse($byBranch as $branch)
            <tr>
                <td>{{ $branch->branch_name }}</td>
                <td>{{ $branch->count }}</td>
            </tr>
            @empty
            <tr><td colspan="2" style="text-align: center;">No data available</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3>Top Recruiters</h3>
    <table>
        <thead>
            <tr>
                <th>Recruiter Name</th>
                <th style="text-align: center;">Added</th>
                <th style="text-align: center;">Hired</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recruiters as $recruiter)
            <tr>
                <td>{{ $recruiter->name }}</td>
                <td style="text-align: center;">{{ $recruiter->total_added }}</td>
                <td style="text-align: center;">{{ $recruiter->total_hired }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align: center;">No recruiter activity recorded</td></tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>