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

        /* ── Page layout ── */
        .page { padding: 32px 36px; }

        /* ── Header ── */
        .header {
            width: 100%;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #0f172a;
        }
        .header-inner { width: 100%; }
        .header-left { width: 60%; vertical-align: middle; }
        .header-right { width: 40%; text-align: right; vertical-align: middle; }

        .logo-cell { width: 52px; vertical-align: middle; padding-right: 10px; }
        .logo-cell img { width: 48px; height: 48px; }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .company-tagline { font-size: 9px; color: #94a3b8; margin-top: 2px; letter-spacing: 0.8px; text-transform: uppercase; }

        .report-title { font-size: 11px; font-weight: bold; color: #475569; letter-spacing: 1px; text-transform: uppercase; }
        .report-period { font-size: 10px; color: #64748b; margin-top: 3px; }
        .report-generated { font-size: 9px; color: #94a3b8; margin-top: 2px; }

        /* ── KPI Summary Strip ── */
        .kpi-strip {
            width: 100%;
            margin-bottom: 24px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 0;
        }
        .kpi-strip table { width: 100%; border-collapse: collapse; }
        .kpi-cell {
            text-align: center;
            padding: 14px 10px;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .kpi-cell:last-child { border-right: none; }
        .kpi-label {
            font-size: 8px;
            font-weight: bold;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
        }
        .kpi-value { font-size: 22px; font-weight: bold; color: #0f172a; line-height: 1; }
        .kpi-value.green  { color: #16a34a; }
        .kpi-value.blue   { color: #2563eb; }
        .kpi-value.amber  { color: #d97706; }
        .kpi-value.purple { color: #7c3aed; }
        .kpi-sub { font-size: 8px; color: #94a3b8; margin-top: 3px; }

        /* ── Section headers ── */
        .section-header {
            font-size: 9px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            margin-top: 20px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
            display: block;
        }
        .section-header:first-of-type { margin-top: 0; }

        /* ── Two-column layout ── */
        .two-col { width: 100%; margin-bottom: 20px; }
        .col-left  { width: 49%; vertical-align: top; padding-right: 8px; }
        .col-right { width: 49%; vertical-align: top; padding-left: 8px; }

        /* ── Tables ── */
        .data-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .data-table thead tr { background: #f1f5f9; }
        .data-table th {
            padding: 7px 10px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid #e2e8f0;
        }
        .data-table td {
            padding: 7px 10px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr:nth-child(even) td { background: #f8fafc; }

        .data-table .rank {
            font-weight: bold;
            color: #94a3b8;
            font-size: 9px;
            text-align: center;
        }
        .data-table .num { text-align: center; font-weight: bold; color: #0f172a; }
        .data-table .rate-pill {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
        }
        .rate-high { background: #dcfce7; color: #15803d; }
        .rate-mid  { background: #fef9c3; color: #a16207; }
        .rate-low  { background: #f1f5f9; color: #64748b; }

        /* ── Bar chart (CSS) ── */
        .bar-row { margin-bottom: 6px; }
        .bar-label { font-size: 9px; color: #475569; margin-bottom: 2px; }
        .bar-track { background: #e2e8f0; border-radius: 3px; height: 10px; width: 100%; }
        .bar-fill  { background: #6366f1; border-radius: 3px; height: 10px; }
        .bar-count { font-size: 9px; color: #64748b; margin-left: 4px; display: inline; }

        /* ── Funnel ── */
        .funnel-stage { margin-bottom: 0; text-align: center; }
        .funnel-bar {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            margin: 0 auto;
        }
        .funnel-arrow { font-size: 9px; color: #cbd5e1; text-align: center; line-height: 1.4; }
        .funnel-drop-pct { font-size: 8px; color: #94a3b8; }

        /* ── Footer ── */
        .footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #94a3b8;
        }
        .footer-inner { width: 100%; }
        .footer-left  { width: 50%; vertical-align: middle; }
        .footer-right { width: 50%; text-align: right; vertical-align: middle; }

        .empty-row td { text-align: center; color: #94a3b8; font-style: italic; padding: 12px; }

        /* ── No page breaks inside tables ── */
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

        // Derived stats
        $totalHired    = $hired ?? 0;
        $totalActive   = $active ?? 0;
        $totalPooling  = $pooling ?? 0;
        $totalRejected = $rejected ?? 0;

        // Source bar chart max
        $maxSource = $bySource->max() ?: 1;

        // Funnel stages
        $funnelStages = collect([
            ['label' => 'Total Applicants', 'value' => $totalApplicants, 'color' => '#dbeafe', 'text' => '#1e40af'],
            ['label' => 'In-Process',       'value' => $totalActive,     'color' => '#fef9c3', 'text' => '#854d0e'],
            ['label' => 'Pooling',          'value' => $totalPooling,    'color' => '#ffedd5', 'text' => '#9a3412'],
            ['label' => 'Hired',            'value' => $totalHired,      'color' => '#dcfce7', 'text' => '#14532d'],
        ])->filter(fn($s) => $s['value'] > 0)->values();
    @endphp

    {{-- ── Header ── --}}
    <div class="header">
        <table class="header-inner" style="border:none; margin:0;">
            <tr>
                <td class="header-left" style="border:none;">
                    <table style="border:none; margin:0;">
                        <tr style="border:none;">
                            @if($base64)
                            <td class="logo-cell" style="border:none;"><img src="{{ $base64 }}" /></td>
                            @endif
                            <td style="border:none; vertical-align:middle;">
                                <div class="company-name">{{ $companyName }}</div>
                                <div class="company-tagline">Recruitment &amp; Staffing Solutions</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="header-right" style="border:none;">
                    <div class="report-title">Performance Report</div>
                    <div class="report-period">
                        @if($startDate && $endDate)
                            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                        @else
                            All Time
                        @endif
                    </div>
                    <div class="report-generated">Generated {{ now()->format('d M Y, h:i A') }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── KPI Strip ── --}}
    <div class="kpi-strip">
        <table style="border:none; margin:0; border-collapse:collapse;">
            <tr>
                <td class="kpi-cell" style="border:none; border-right: 1px solid #e2e8f0;">
                    <div class="kpi-label">Total Applicants</div>
                    <div class="kpi-value blue">{{ number_format($totalApplicants) }}</div>
                    <div class="kpi-sub">In period</div>
                </td>
                <td class="kpi-cell" style="border:none; border-right: 1px solid #e2e8f0;">
                    <div class="kpi-label">Hired</div>
                    <div class="kpi-value green">{{ number_format($totalHired) }}</div>
                    <div class="kpi-sub">Successfully placed</div>
                </td>
                <td class="kpi-cell" style="border:none; border-right: 1px solid #e2e8f0;">
                    <div class="kpi-label">In-Process</div>
                    <div class="kpi-value amber">{{ number_format($totalActive) }}</div>
                    <div class="kpi-sub">Active pipeline</div>
                </td>
                <td class="kpi-cell" style="border:none; border-right: 1px solid #e2e8f0;">
                    <div class="kpi-label">Pooling</div>
                    <div class="kpi-value amber">{{ number_format($totalPooling) }}</div>
                    <div class="kpi-sub">Talent pool</div>
                </td>
                <td class="kpi-cell" style="border:none;">
                    <div class="kpi-label">Conversion Rate</div>
                    <div class="kpi-value purple">{{ $conversionRate }}%</div>
                    <div class="kpi-sub">{{ $totalHired }} of {{ $totalApplicants }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── Source + Funnel (two columns) ── --}}
    <table class="two-col" style="border:none; margin:0; border-collapse:collapse;">
        <tr>
            {{-- Source (left) --}}
            <td class="col-left" style="border:none;">
                <span class="section-header">Applicants by Source</span>
                @if($bySource->isNotEmpty())
                    @foreach($bySource as $source => $count)
                    @php $pct = $maxSource > 0 ? round(($count / $maxSource) * 100) : 0; @endphp
                    <div class="bar-row">
                        <div class="bar-label">
                            {{ $source ?: 'Unknown' }}
                            <span class="bar-count">{{ $count }}</span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: {{ $pct }}%;"></div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <p style="color:#94a3b8; font-style:italic; font-size:10px;">No source data available.</p>
                @endif
            </td>

            {{-- Funnel (right) --}}
            <td class="col-right" style="border:none;">
                <span class="section-header">Recruitment Funnel</span>
                @php $maxFunnel = $funnelStages->max('value') ?: 1; @endphp
                @foreach($funnelStages as $i => $stage)
                    @php $widthPct = max(round(($stage['value'] / $maxFunnel) * 100), 30); @endphp
                    @if($i > 0)
                        @php
                            $prevVal = $funnelStages[$i - 1]['value'];
                            $dropPct = $prevVal > 0 ? round(($stage['value'] / $prevVal) * 100) : 0;
                        @endphp
                        <div class="funnel-arrow">▼ <span class="funnel-drop-pct">{{ $dropPct }}% passed through</span></div>
                    @endif
                    <div class="funnel-stage">
                        <div class="funnel-bar" style="width:{{ $widthPct }}%; background:{{ $stage['color'] }}; color:{{ $stage['text'] }};">
                            {{ $stage['label'] }}: <strong>{{ number_format($stage['value']) }}</strong>
                        </div>
                    </div>
                @endforeach
                @if($funnelStages->isEmpty())
                    <p style="color:#94a3b8; font-style:italic; font-size:10px;">No funnel data available.</p>
                @endif
            </td>
        </tr>
    </table>

    {{-- ── Branch Performance ── --}}
    <span class="section-header">Branch Performance</span>
    <table class="data-table" style="margin-bottom:20px;">
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Branch Name</th>
                <th style="text-align:center;">Applicants</th>
                <th style="text-align:center; width:140px;">Share</th>
            </tr>
        </thead>
        <tbody>
            @forelse($byBranch as $i => $branch)
            @php $sharePct = $totalApplicants > 0 ? round(($branch->count / $totalApplicants) * 100) : 0; @endphp
            <tr>
                <td class="rank">{{ $i + 1 }}</td>
                <td>{{ $branch->branch_name }}</td>
                <td class="num">{{ number_format($branch->count) }}</td>
                <td style="vertical-align:middle; padding: 6px 10px;">
                    <div style="display:inline-block; width:{{ max($sharePct, 2) }}%; background:#8b5cf6; height:8px; border-radius:2px; vertical-align:middle;"></div>
                    <span style="font-size:8px; color:#64748b; margin-left:4px;">{{ $sharePct }}%</span>
                </td>
            </tr>
            @empty
            <tr class="empty-row"><td colspan="4">No branch data available</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ── Top Recruiters ── --}}
    <span class="section-header">Top Recruiters</span>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Recruiter</th>
                <th style="text-align:center;">Added</th>
                <th style="text-align:center;">Hired</th>
                <th style="text-align:center;">Rate</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recruiters as $i => $recruiter)
            @php
                $rate = $recruiter->total_added > 0
                    ? round(($recruiter->total_hired / $recruiter->total_added) * 100, 1)
                    : 0;
                $rateClass = $rate >= 22 ? 'rate-high' : ($rate >= 15 ? 'rate-mid' : 'rate-low');
                $medals = ['🏆', '🥈', '🥉'];
            @endphp
            <tr>
                <td class="rank">{{ $medals[$i] ?? ($i + 1) }}</td>
                <td><strong>{{ $recruiter->name }}</strong></td>
                <td class="num">{{ $recruiter->total_added }}</td>
                <td class="num" style="color:#16a34a;">{{ $recruiter->total_hired }}</td>
                <td style="text-align:center;">
                    <span class="rate-pill {{ $rateClass }}">{{ $rate }}%</span>
                </td>
            </tr>
            @empty
            <tr class="empty-row"><td colspan="5">No recruiter activity recorded</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ── Footer ── --}}
    <div class="footer">
        <table class="footer-inner" style="border:none; margin:0;">
            <tr>
                <td class="footer-left" style="border:none;">
                    {{ $companyName }} &mdash; Confidential
                </td>
                <td class="footer-right" style="border:none;">
                    Generated by Garuda Recruitment System &middot; {{ now()->format('d M Y') }}
                </td>
            </tr>
        </table>
    </div>

</div>
</body>
</html> 