<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Proces-verbal {{ $installation->report_number ?? 'PV-'.$installation->id }}</title>
    <style>
        @page { margin: 30px 38px 54px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #172033; }
        .topbar { height: 8px; background: #f59e0b; }
        .header { width: 100%; padding: 18px 0 16px; border-bottom: 1px solid #dbe3ed; }
        .header td { vertical-align: top; }
        .logo { width: 190px; height: auto; }
        .brand-line { color: #64748b; font-size: 9px; letter-spacing: 1.2px; margin-top: 6px; }
        h1 { color: #061426; font-size: 19px; margin: 0 0 4px; }
        .muted { color: #64748b; }
        .section { margin-top: 20px; }
        .section-title { color: #061426; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: .7px; }
        .info-card { margin-top: 8px; padding: 11px 13px; background: #f4f7fb; border-left: 4px solid #2563eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 9px; }
        td { padding: 5px 0; }
        .checklist-item { padding: 4px 0; }
        .badge { display: inline-block; padding: 3px 8px; background: #dbeafe; color: #1e3a8a; font-size: 10px; font-weight: bold; }
        .footer { position: fixed; bottom: -32px; left: 0; right: 0; padding-top: 10px; border-top: 1px solid #dbe3ed; color: #64748b; font-size: 9px; }
    </style>
</head>
<body>
    <div class="topbar"></div>
    <table class="header" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width: 55%;">
                <img class="logo" src="{{ public_path('branding/op-logo.png') }}" alt="{{ $settings['company_name'] }}">
                <div class="brand-line">SIGURANTA INCEPE CU VIZIBILITATE.</div>
            </td>
            <td style="width: 45%; text-align: right;">
                <h1>PROCES-VERBAL</h1>
                <div class="muted">{{ $installation->report_number ?? 'PV-'.$installation->id }}</div>
                <div class="muted">Generat la {{ now()->format('d.m.Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">{{ $installation->typeLabel() }} #{{ $installation->id }}</div>
        <div class="info-card">
        <table style="margin-top: 0;">
            <tr>
                <td style="width: 30%;"><strong>Client:</strong></td>
                <td>{{ $installation->client->name }}</td>
            </tr>
            <tr>
                <td><strong>Adresa:</strong></td>
                <td>{{ $installation->address ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Tehnician:</strong></td>
                <td>{{ $installation->technician->name ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Data programata:</strong></td>
                <td>{{ $installation->scheduled_at?->format('d.m.Y H:i') ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td><span class="badge">{{ strtoupper($installation->status) }}</span></td>
            </tr>
            <tr>
                <td><strong>Data receptiei:</strong></td>
                <td>{{ $installation->handover_at?->format('d.m.Y H:i') ?? '-' }}</td>
            </tr>
        </table>
        </div>
    </div>

    @if (!empty($installation->checklist))
        <div class="section">
            <strong>Checklist instalare</strong>
            @foreach ($installation->checklist as $item)
                <div class="checklist-item">
                    [{{ $item['done'] ? 'X' : ' ' }}] {{ $item['label'] }}
                </div>
            @endforeach
        </div>
    @endif

    @if ($installation->notes)
        <div class="section">
            <strong>Note tehnice</strong>
            <p>{{ $installation->notes }}</p>
        </div>
    @endif

    @if ($installation->labor_hours || $installation->materials)
        <div class="section">
            <strong>Executie</strong>
            <table>
                <tr><td style="width: 30%;"><strong>Ore lucrate:</strong></td><td>{{ $installation->labor_hours ?? '-' }}</td></tr>
                <tr><td><strong>Materiale:</strong></td><td>{{ !empty($installation->materials) ? implode(', ', $installation->materials) : '-' }}</td></tr>
            </table>
        </div>
    @endif

    @if (!empty($installation->material_items))
        <div class="section">
            <strong>Materiale consumate din stoc</strong>
            @foreach ($installation->material_items as $item)
                <div class="checklist-item">{{ $item['name'] ?? 'Material' }}: {{ $item['quantity'] }} {{ $item['unit'] ?? 'buc' }}</div>
            @endforeach
        </div>
    @endif

    @if (!empty($installation->service_items))
        <div class="section">
            <strong>Servicii / manopera planificata</strong>
            @foreach ($installation->service_items as $item)
                <div class="checklist-item">{{ $item['name'] ?? 'Serviciu' }}: {{ $item['quantity'] }} {{ $item['unit'] ?? 'serviciu' }}</div>
            @endforeach
        </div>
    @endif

    <div class="section">
        <strong>Raport costuri si profit</strong>
        <table>
            <tr><td style="width: 30%;"><strong>Valoare oferta:</strong></td><td>{{ number_format($installation->cost_report['offer_value'], 2, ',', '.') }} lei</td></tr>
            <tr><td><strong>Cost materiale:</strong></td><td>{{ number_format($installation->cost_report['material_cost'], 2, ',', '.') }} lei</td></tr>
            <tr><td><strong>Cost manopera:</strong></td><td>{{ number_format($installation->cost_report['labor_cost'], 2, ',', '.') }} lei</td></tr>
            <tr><td><strong>Cost total:</strong></td><td>{{ number_format($installation->cost_report['total_cost'], 2, ',', '.') }} lei</td></tr>
            <tr><td><strong>Cheltuieli reale:</strong></td><td>{{ number_format($installation->cost_report['actual_expenses'], 2, ',', '.') }} lei</td></tr>
            <tr><td><strong>Cost real total:</strong></td><td>{{ number_format($installation->cost_report['actual_total_cost'], 2, ',', '.') }} lei</td></tr>
            <tr><td><strong>Profit estimat:</strong></td><td>{{ number_format($installation->cost_report['estimated_profit'], 2, ',', '.') }} lei</td></tr>
            @if ($installation->cost_report['final_profit'] !== null)
                <tr><td><strong>Profit final real:</strong></td><td>{{ number_format($installation->cost_report['final_profit'], 2, ',', '.') }} lei</td></tr>
            @endif
        </table>
    </div>

    @if ($installation->customer_name || $installation->customer_notes)
        <div class="section">
            <strong>Confirmare client</strong>
            <p>Nume: {{ $installation->customer_name ?? '-' }}</p>
            <p>{{ $installation->customer_notes }}</p>
        </div>
    @endif

    @if ($installation->technician_signature || $installation->customer_signature)
        <div class="section">
            <strong>Semnaturi</strong>
            <table>
                <tr>
                    <td style="width: 50%;">
                        Tehnician<br>
                        @if ($installation->technician_signature)
                            <img src="{{ public_path(str_replace('/storage/', 'storage/', parse_url($installation->technician_signature, PHP_URL_PATH))) }}" style="max-width:180px;max-height:70px;">
                        @endif
                    </td>
                    <td>
                        Client<br>
                        @if ($installation->customer_signature)
                            <img src="{{ public_path(str_replace('/storage/', 'storage/', parse_url($installation->customer_signature, PHP_URL_PATH))) }}" style="max-width:180px;max-height:70px;">
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <div class="footer">
        <strong>{{ $settings['company_name'] }}</strong>
        @if ($settings['company_address']) &middot; {{ $settings['company_address'] }} @endif
        &middot; {{ $settings['company_email'] }} &middot; {{ $settings['company_phone'] }}
    </div>
</body>
</html>
