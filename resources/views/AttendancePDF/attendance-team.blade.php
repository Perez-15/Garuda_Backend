<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Team Attendance Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #1e293b; font-size: 10px; background: #fff; }
        .page { padding: 28px 32px; }

        /* Header */
        .header { margin-bottom: 20px; padding-bottom: 12px; border-bottom: 3px solid #0f172a; }
        .company-name  { font-size: 17px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.4px; }
        .company-sub   { font-size: 8px; color: #94a3b8; margin-top: 2px; letter-spacing: 1px; text-transform: uppercase; }
        .report-title  { font-size: 10px; font-weight: bold; color: #334155; letter-spacing: 1px; text-transform: uppercase; }
        .report-period { font-size: 9px; color: #64748b; margin-top: 3px; }
        .report-gen    { font-size: 8px; color: #94a3b8; margin-top: 2px; }

        /* KPI Strip */
        .kpi-strip { width: 100%; border: 1px solid #e2e8f0; border-radius: 5px; margin-bottom: 18px; overflow: hidden; }
        .kpi-strip table { width: 100%; border-collapse: collapse; }
        .kpi-cell { text-align: center; padding: 12px 10px; border-right: 1px solid #e2e8f0; vertical-align: middle; }
        .kpi-cell:last-child { border-right: none; background: #0f172a; }
        .kpi-label { font-size: 7.5px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 4px; }
        .kpi-cell:last-child .kpi-label { color: #64748b; }
        .kpi-value { font-size: 22px; font-weight: bold; color: #0f172a; line-height: 1; }
        .kpi-value.green  { color: #16a34a; }
        .kpi-value.amber  { color: #d97706; }
        .kpi-value.red    { color: #dc2626; }
        .kpi-value.white  { color: #ffffff; }
        .kpi-sub { font-size: 7.5px; color: #94a3b8; margin-top: 3px; }
        .kpi-cell:last-child .kpi-sub { color: #475569; }

        
        /* Table */
        .section-header {
            display: block; font-size: 8px; font-weight: bold; color: #64748b;
            text-transform: uppercase; letter-spacing: 1px;
            padding-bottom: 5px; margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .data-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
        .data-table thead tr { background: #f8fafc; }
        .data-table th {
            padding: 6px 10px; text-align: left;
            font-size: 7.5px; font-weight: bold; color: #64748b;
            text-transform: uppercase; letter-spacing: 0.5px;
            border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;
        }
        .data-table td { padding: 6px 10px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr:nth-child(even) td { background: #f8fafc; }
        .c-center { text-align: center; }

        /* Status pills */
        .pill { display: inline-block; padding: 1px 7px; border-radius: 10px; font-size: 8px; font-weight: bold; }
        .pill-present { background: #dcfce7; color: #15803d; }
        .pill-late    { background: #fef9c3; color: #a16207; }
        .pill-absent  { background: #fee2e2; color: #b91c1c; }

        /* Footer */
        .footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 7.5px; color: #94a3b8; }

        table { page-break-inside: auto; }
        tr    { page-break-inside: avoid; }
    </style>
</head>
<body>
<div class="page">

   @php
    $path   = public_path('images/Garuda_Transparent.png');
    $base64 = '';
    if (file_exists($path)) {
        $type   = pathinfo($path, PATHINFO_EXTENSION);
        $data   = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    $roleMap = [
        'hr_admin'           => 'HR',
        'talent_acquisition' => 'Talent Acquisition',
        'accounting'         => 'Accounting',
        'marketing'          => 'Marketing',
        'super_admin'        => 'Super Admin',
    ];

    if (!function_exists('fmtTime')) {
        function fmtTime($val) {
            if (!$val) return '—';
            $d = DateTime::createFromFormat('H:i:s', $val) ?: DateTime::createFromFormat('H:i', $val);
            return $d ? $d->format('g:i A') : '—';
        }
    }

    if (!function_exists('fmtLateBy')) {
        function fmtLateBy($timeIn, $status) {
            if ($status !== 'Late' || !$timeIn) return '—';
            [$h, $m] = array_map('intval', explode(':', $timeIn));
            $inMins  = $h * 60 + $m;
            $late    = $inMins - (8 * 60 + 16);
            return $late > 0 ? "+{$late}m" : '—';
        }
    }

    if (!function_exists('fmtHours')) {
        function fmtHours($timeIn, $timeOut) {
            if (!$timeIn || !$timeOut) return '—';
            [$ih, $im] = array_map('intval', explode(':', $timeIn));
            [$oh, $om] = array_map('intval', explode(':', $timeOut));
            $diff = ($oh * 60 + $om) - ($ih * 60 + $im);
            if ($diff <= 0) return '—';
            $h = intdiv($diff, 60); $m = $diff % 60;
            return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
        }
    }
@endphp

    {{-- Header --}}
    <div class="header">
        <table style="width:100%; border:none; border-collapse:collapse;">
            <tr>
                <td style="border:none; vertical-align:middle;">
                    <table style="border:none; border-collapse:collapse;">
                        <tr>
                            @if($base64)
                            <td style="border:none; vertical-align:middle; padding-right:9px;">
                                <img src="{{ $base64 }}" style="width:42px; height:42px;" />
                            </td>
                            @endif
                            <td style="border:none; vertical-align:middle;">
                                <div class="company-name">Garuda Recruitment Agency</div>
                                <div class="company-sub">Recruitment &amp; Staffing Solutions</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="border:none; text-align:right; vertical-align:middle;">
                    <div class="report-title">Team Attendance Report</div>
                    <div class="report-period">{{ $periodLabel }}</div>
                    <div class="report-gen">Generated {{ $generatedAt }}</div>
                </td>
            </tr>
        </table>
    </div>

    

    {{-- KPI Strip --}}
    <div class="kpi-strip">
        <table>
            <tr>
                <td class="kpi-cell">
                    <div class="kpi-label">Total Records</div>
                    <div class="kpi-value">{{ number_format($stats['total']) }}</div>
                    <div class="kpi-sub">{{ $filterLabel }}</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-label">Present</div>
                    <div class="kpi-value green">{{ number_format($stats['present']) }}</div>
                    <div class="kpi-sub">On time</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-label">Late</div>
                    <div class="kpi-value amber">{{ number_format($stats['late']) }}</div>
                    <div class="kpi-sub">After 8:16 AM</div>
                </td>
                <td class="kpi-cell">a
                    <div class="kpi-label">Absent</div>
                    <div class="kpi-value red">{{ number_format($stats['absent']) }}</div>
                    <div class="kpi-sub">No record</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-label">Attendance Rate</div>
                    <div class="kpi-value white">
                        {{ $stats['total'] > 0 ? round((($stats['present'] + $stats['late']) / $stats['total']) * 100, 1) : 0 }}%
                    </div>
                    <div class="kpi-sub">Present + Late</div>
                </td>
            </tr>
        </table>
    </div>

  {{-- Records grouped by Employee --}}
<span class="section-header">Attendance Records</span>

@php
    $grouped = $records->groupBy(fn($rec) => $rec['user']->id);
@endphp

@foreach($grouped as $userId => $empRecords)
@php
    $user    = $empRecords->first()['user'];
    $role    = $user->roles?->first()?->name ?? '';
    $dept    = $roleMap[$role] ?? ucfirst(str_replace('_', ' ', $role));
    $present = $empRecords->where('status', 'Present')->count();
    $late    = $empRecords->where('status', 'Late')->count();
    $absent  = $empRecords->where('status', 'Absent')->count();
@endphp

{{-- Employee Header --}}
<div style="margin-top: 20px; margin-bottom: 6px; padding: 8px 12px; background: #f8fafc; border-left: 3px solid #0f172a; border-bottom: 1px solid #e2e8f0;">
    <table style="width:100%; border:none; border-collapse:collapse;">
        <tr>
            <td style="border:none; vertical-align:middle;">
                <div style="font-size:11px; font-weight:bold; color:#0f172a;">{{ $user->name }}</div>
                <div style="font-size:8px; color:#94a3b8; margin-top:1px;">
                    {{ $user->email }} &middot; {{ $dept }}
                </div>
            </td>
            <td style="border:none; text-align:right; vertical-align:middle;">
                <span style="font-size:8px; font-weight:bold; color:#16a34a; margin-right:10px;">
                    ✓ {{ $present }} Present
                </span>
                <span style="font-size:8px; font-weight:bold; color:#d97706; margin-right:10px;">
                    ⏱ {{ $late }} Late
                </span>
                <span style="font-size:8px; font-weight:bold; color:#dc2626;">
                    ✗ {{ $absent }} Absent
                </span>
            </td>
        </tr>
    </table>
</div>

{{-- Employee Attendance Table --}}
<table class="data-table" style="margin-bottom: 4px;">
    <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Time In</th>
            <th>Time Out</th>
            <th class="c-center">Status</th>
            <th class="c-center">Late By</th>
            <th class="c-center">Hours</th>
        </tr>
    </thead>
    <tbody>
        @foreach($empRecords as $j => $rec)
        @php
            $status  = $rec['status'];
            $pillCls = match($status) {
                'Present' => 'pill-present',
                'Late'    => 'pill-late',
                default   => 'pill-absent',
            };
            $dateStr = \Carbon\Carbon::parse($rec['date'])->format('d M Y');
            $lateBy  = fmtLateBy($rec['time_in'], $status);
            $hours   = fmtHours($rec['time_in'], $rec['time_out']);
        @endphp
        <tr>
            <td style="color:#94a3b8; font-size:8.5px;">{{ $j + 1 }}</td>
            <td style="color:#334155;">{{ $dateStr }}</td>
            <td style="font-variant-numeric:tabular-nums;">{{ fmtTime($rec['time_in']) }}</td>
            <td style="font-variant-numeric:tabular-nums;">{{ fmtTime($rec['time_out']) }}</td>
            <td class="c-center">
                <span class="pill {{ $pillCls }}">{{ $status }}</span>
            </td>
            <td class="c-center" style="color:{{ $status === 'Late' ? '#d97706' : '#cbd5e1' }};">
                {{ $lateBy }}
            </td>
            <td class="c-center" style="color:#334155; font-weight:{{ $hours !== '—' ? 'bold' : 'normal' }};">
                {{ $hours }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@endforeach

    {{-- Footer --}}
    <div class="footer">
        <table style="width:100%; border:none; border-collapse:collapse;">
            <tr>
                <td style="border:none;">Garuda Recruitment Agency &mdash; Confidential &amp; For Internal Use Only</td>
                <td style="border:none; text-align:right;">Generated by Garuda Recruitment System &middot; {{ $generatedAt }}</td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>