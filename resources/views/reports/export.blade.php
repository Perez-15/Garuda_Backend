<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recruitment Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            font-size: 11px;
            background: #fff;
        }

        .page { padding: 36px 40px; }

        /* ── Header ── */
        .logo-img     { width: 46px; height: 46px; }
        .company-name { font-size: 19px; font-weight: bold; color: #0f172a; letter-spacing: 0.4px; text-transform: uppercase; }
        .company-sub  { font-size: 8px; color: #94a3b8; margin-top: 2px; letter-spacing: 1px; text-transform: uppercase; }
        .report-title { font-size: 11px; font-weight: bold; color: #334155; letter-spacing: 1.2px; text-transform: uppercase; }
        .report-period { font-size: 10px; color: #64748b; margin-top: 4px; }
        .report-gen   { font-size: 8.5px; color: #94a3b8; margin-top: 3px; }

        /* ── KPI Strip ── */
        .kpi-strip {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 4px;
            overflow: hidden;
        }
        .kpi-strip table { width: 100%; border-collapse: collapse; }
        .kpi-cell {
            text-align: center;
            padding: 16px 10px;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .kpi-cell:last-child { border-right: none; }
        .kpi-cell.accent { background: #0f172a; }
        .kpi-label { font-size: 8px; font-weight: bold; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.9px; margin-bottom: 5px; }
        .kpi-cell.accent .kpi-label { color: #64748b; }
        .kpi-value        { font-size: 26px; font-weight: bold; color: #0f172a; line-height: 1; }
        .kpi-value.green  { color: #16a34a; }
        .kpi-value.blue   { color: #2563eb; }
        .kpi-value.amber  { color: #d97706; }
        .kpi-value.slate  { color: #64748b; }
        .kpi-value.white  { color: #ffffff; }
        .kpi-sub { font-size: 8px; color: #94a3b8; margin-top: 4px; }
        .kpi-cell.accent .kpi-sub { color: #475569; }

        /* ── Section Header ── */
        .section-header {
            display: block;
            font-size: 8.5px;
            font-weight: bold;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 1.1px;
            padding-bottom: 6px;
            margin-bottom: 10px;
            margin-top: 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        /* ── Two-column layout ── */
        .two-col   { width: 100%; border-collapse: collapse; }
        .col-left  { width: 50%; vertical-align: top; padding-right: 14px; }
        .col-right { width: 50%; vertical-align: top; padding-left: 14px; }

        /* ── Data Tables ── */
        .data-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .data-table thead tr { background: #f8fafc; }
        .data-table th {
            padding: 7px 10px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .data-table td {
            padding: 7px 10px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .data-table tbody tr:last-child td { border-bottom: none; }

        .c-center { text-align: center; }
        .c-right  { text-align: right; }
        .num      { text-align: center; font-weight: bold; color: #0f172a; }
        .muted    { color: #94a3b8; font-size: 9px; text-align: center; }

        /* ── Pills ── */
        .pill        { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .pill-green  { background: #dcfce7; color: #15803d; }
        .pill-amber  { background: #fef9c3; color: #a16207; }
        .pill-slate  { background: #f1f5f9; color: #64748b; }

        /* ── Status dot ── */
        .dot         { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 5px; vertical-align: middle; }
        .dot-blue    { background: #3b82f6; }
        .dot-green   { background: #22c55e; }
        .dot-purple  { background: #a855f7; }
        .dot-orange  { background: #f97316; }
        .dot-gray    { background: #9ca3af; }
        .dot-yellow  { background: #eab308; }
        .dot-teal    { background: #14b8a6; }
        .dot-red     { background: #ef4444; }

        /* ── Footer ── */
        .footer {
            margin-top: 32px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #94a3b8;
        }

        .empty-cell { text-align: center; color: #94a3b8; font-style: italic; padding: 14px; }

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

        $totalHired    = $hired    ?? 0;
        $totalActive   = $active   ?? 0;
        $totalPooling  = $pooling  ?? 0;
        $totalRejected = $rejected ?? 0;

        $prospectStatusMeta = [
            'sent_email'       => ['label' => 'Sent Email',       'dot' => 'dot-blue'],
            'updated'          => ['label' => 'Updated',          'dot' => 'dot-green'],
            'they_emailed'     => ['label' => 'They Emailed',     'dot' => 'dot-purple'],
            'hard_copy_needed' => ['label' => 'Hard Copy Needed', 'dot' => 'dot-orange'],
            'no_response'      => ['label' => 'No Response',      'dot' => 'dot-gray'],
            'after_1_month'    => ['label' => 'Follow Up (1 Mo)', 'dot' => 'dot-yellow'],
            'email_back'       => ['label' => 'Email Back',       'dot' => 'dot-teal'],
            'for_follow_up'    => ['label' => 'For Follow Up',    'dot' => 'dot-red'],
        ];

        $medals = ['🏆', '🥈', '🥉'];
    @endphp

    {{-- ══ HEADER ══ --}}
    <table style="width:100%; border:none; border-collapse:collapse; margin-bottom:28px; padding-bottom:14px; border-bottom: 3px solid #0f172a;">
        <tr>
            <td style="border:none; vertical-align:middle;">
                <table style="border:none; border-collapse:collapse;">
                    <tr>
                        @if($base64)
                        <td style="border:none; vertical-align:middle; padding-right:10px;">
                            <img src="{{ $base64 }}" class="logo-img" />
                        </td>
                        @endif
                        <td style="border:none; vertical-align:middle;">
                            <div class="company-name">{{ $companyName }}</div>
                            <div class="company-sub">Recruitment &amp; Staffing Solutions</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="border:none; text-align:right; vertical-align:middle;">
                <div class="report-title">Performance Report</div>
                <div class="report-period">
                    @if($startDate && $endDate)
                        {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    @else
                        All Time
                    @endif
                </div>
                <div class="report-gen">Generated {{ now()->format('d M Y, h:i A') }}</div>
            </td>
        </tr>
    </table>

    {{-- ══ KPI STRIP ══ --}}
    <div class="kpi-strip">
        <table>
            <tr>
                <td class="kpi-cell">
                    <div class="kpi-label">Total Applicants</div>
                    <div class="kpi-value blue">{{ number_format($totalApplicants) }}</div>
                    <div class="kpi-sub">In period</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-label">Hired</div>
                    <div class="kpi-value green">{{ number_format($totalHired) }}</div>
                    <div class="kpi-sub">Successfully placed</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-label">In-Process</div>
                    <div class="kpi-value amber">{{ number_format($totalActive) }}</div>
                    <div class="kpi-sub">Active pipeline</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-label">Pooling</div>
                    <div class="kpi-value amber">{{ number_format($totalPooling) }}</div>
                    <div class="kpi-sub">Talent pool</div>
                </td>
                <td class="kpi-cell">
                    <div class="kpi-label">Rejected</div>
                    <div class="kpi-value slate">{{ number_format($totalRejected) }}</div>
                    <div class="kpi-sub">Not proceeded</div>
                </td>
                <td class="kpi-cell accent">
                    <div class="kpi-label">Conversion Rate</div>
                    <div class="kpi-value white">{{ $conversionRate }}%</div>
                    <div class="kpi-sub">{{ $totalHired }} hired of {{ $totalApplicants }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══ SOURCE + PROSPECTS ══ --}}
    <table class="two-col">
        <tr>
            <td class="col-left">
                <span class="section-header">Applicants by Source</span>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="c-center" style="width:28px;">#</th>
                            <th>Source</th>
                            <th class="c-center">Count</th>
                            <th class="c-right">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bySource as $source => $count)
                        @php $sharePct = $totalApplicants > 0 ? round(($count / $totalApplicants) * 100, 1) : 0; @endphp
                        <tr>
                            <td class="muted">{{ $loop->iteration }}</td>
                            <td>{{ $source ?: 'Unknown' }}</td>
                            <td class="num">{{ number_format($count) }}</td>
                            <td class="c-right" style="color:#64748b; font-size:9px;">{{ $sharePct }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="empty-cell">No source data available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>

            <td class="col-right">
                <span class="section-header">
                    Prospects by Status
                    <span style="font-weight:normal; color:#94a3b8; text-transform:none; letter-spacing:0;">&nbsp;{{ $totalProspects ?? 0 }} total</span>
                </span>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th class="c-center">Count</th>
                            <th class="c-right">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalProspectCount = $prospectsByStatus->sum('count') ?: 1; @endphp
                        @forelse($prospectsByStatus as $item)
                        @php
                            $meta     = $prospectStatusMeta[$item->status] ?? ['label' => ucfirst(str_replace('_', ' ', $item->status)), 'dot' => 'dot-gray'];
                            $sharePct = round(($item->count / $totalProspectCount) * 100, 1);
                        @endphp
                        <tr>
                            <td>
                                <span class="dot {{ $meta['dot'] }}"></span>{{ $meta['label'] }}
                            </td>
                            <td class="num">{{ $item->count }}</td>
                            <td class="c-right" style="color:#64748b; font-size:9px;">{{ $sharePct }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="empty-cell">No prospect data available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══ BRANCH PERFORMANCE ══ --}}
    <span class="section-header">Branch Performance</span>
    <table class="data-table">
        <thead>
            <tr>
                <th class="c-center" style="width:32px;">#</th>
                <th>Branch</th>
                <th class="c-center">Applicants</th>
                <th class="c-center">Hired</th>
                <th class="c-center">In-Process</th>
                <th class="c-center">Pooling</th>
                <th class="c-right">Share</th>
            </tr>
        </thead>
        <tbody>
            @forelse($byBranch->take(10) as $i => $branch)
            @php $sharePct = $totalApplicants > 0 ? round(($branch->count / $totalApplicants) * 100, 1) : 0; @endphp
            <tr>
                <td class="muted">{{ $i + 1 }}</td>
                <td><strong>{{ $branch->branch_name }}</strong></td>
                <td class="num">{{ number_format($branch->count) }}</td>
                <td class="num" style="color:#16a34a;">{{ number_format($branch->hired ?? 0) }}</td>
                <td class="num" style="color:#d97706;">{{ number_format($branch->active ?? 0) }}</td>
                <td class="num" style="color:#d97706;">{{ number_format($branch->pooling ?? 0) }}</td>
                <td class="c-right" style="color:#64748b; font-size:9px;">{{ $sharePct }}%</td>
            </tr>
            @empty
            <tr><td colspan="7" class="empty-cell">No branch data available</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ══ TOP RECRUITERS ══ --}}
    <span class="section-header">Top Recruiters</span>
    <table class="data-table">
        <thead>
            <tr>
                <th class="c-center" style="width:32px;">#</th>
                <th>Recruiter</th>
                <th class="c-center">Added</th>
                <th class="c-center">Hired</th>
                <th class="c-center">In-Process</th>
                <th class="c-center">Pooling</th>
                <th class="c-center">Conversion</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recruiters as $i => $recruiter)
            @php
                $rate      = $recruiter->total_added > 0 ? round(($recruiter->total_hired / $recruiter->total_added) * 100, 1) : 0;
                $pillClass = $rate >= 22 ? 'pill-green' : ($rate >= 15 ? 'pill-amber' : 'pill-slate');
            @endphp
            <tr>
                <td class="muted c-center">{{ $medals[$i] ?? ($i + 1) }}</td>
                <td><strong>{{ $recruiter->name }}</strong></td>
                <td class="num">{{ number_format($recruiter->total_added) }}</td>
                <td class="num" style="color:#16a34a;">{{ number_format($recruiter->total_hired) }}</td>
                <td class="num" style="color:#d97706;">{{ number_format($recruiter->total_active ?? 0) }}</td>
                <td class="num" style="color:#d97706;">{{ number_format($recruiter->total_pooling ?? 0) }}</td>
                <td class="c-center">
                    <span class="pill {{ $pillClass }}">{{ $rate }}%</span>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="empty-cell">No recruiter activity recorded</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ══ FOOTER ══ --}}
    <div class="footer">
        <table style="width:100%; border:none; border-collapse:collapse;">
            <tr>
                <td style="border:none;">{{ $companyName }} &mdash; Confidential &amp; For Internal Use Only</td>
                <td style="border:none; text-align:right;">Generated by Garuda Recruitment System &middot; {{ now()->format('d M Y, h:i A') }}</td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>