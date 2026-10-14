<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>Lista de cumpărat</title>
    <style>
        @page { margin: 22px 28px 26px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #172033; }
        .topbar { height: 6px; background: #f59e0b; }
        .header { width: 100%; padding: 12px 0 10px; border-bottom: 1px solid #dbe3ed; }
        .header td { vertical-align: top; }
        .logo { width: 84px; height: auto; }
        .brand-line { color: #64748b; font-size: 8px; letter-spacing: 1px; margin-top: 5px; }
        .document-title { color: #061426; font-size: 17px; font-weight: bold; margin: 0 0 4px; }
        .muted { color: #64748b; }
        .supplier-title { margin-top: 16px; padding: 6px 10px; background: #061426; color: #fff; font-size: 10px; font-weight: bold; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .items th { padding: 5px; background: #eef2f7; color: #334155; font-size: 8.5px; text-align: left; }
        .items td { padding: 4px 5px; border-bottom: 1px solid #e2e8f0; }
        .items .total-row td { border-bottom: none; border-top: 1px solid #cbd5e1; background: #f8fafc; font-weight: bold; }
        .text-right { text-align: right; }
        .grand-total { margin-top: 16px; padding: 9px 12px; background: #061426; color: #fff; }
        .grand-total .line { font-size: 9px; color: #cbd5e1; }
        .grand-total .grand { font-size: 14px; font-weight: bold; }
        .footer { margin-top: 18px; padding-top: 8px; border-top: 1px solid #dbe3ed; font-size: 8px; line-height: 1.4; }
    </style>
</head>
<body>
    <div class="topbar"></div>

    <table class="header" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width: 55%;">
                <img class="logo" src="{{ public_path('branding/logo-trim.png') }}" alt="Om Priceput">
                <div class="brand-line">{{ mb_strtoupper($settings['company_name'] ?? 'Om Priceput') }}</div>
            </td>
            <td style="width: 45%; text-align: right;">
                <div class="document-title">LISTĂ DE CUMPĂRAT</div>
                <div class="muted">Perioadă: {{ $meta['period_label'] ?? '-' }}</div>
                <div class="muted">Generată: {{ now()->format('d.m.Y H:i') }}</div>
            </td>
        </tr>
    </table>

    @forelse ($groups as $group)
        <div class="supplier-title">{{ $group['supplier_name'] }}</div>
        <table class="items" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th style="width: 3%;">#</th>
                    <th>Material</th>
                    <th class="text-right" style="width: 8%;">U.M.</th>
                    <th class="text-right" style="width: 11%;">Necesar</th>
                    <th class="text-right" style="width: 11%;">În stoc</th>
                    <th class="text-right" style="width: 11%;">De cumpărat</th>
                    <th class="text-right" style="width: 13%;">Cost estimat</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['items'] as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['name'] }}</td>
                        <td class="text-right">{{ $item['unit'] }}</td>
                        <td class="text-right">{{ number_format((float) $item['needed'], 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format((float) $item['in_stock'], 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format((float) $item['to_buy'], 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format((float) $item['estimated_cost'], 2, ',', '.') }} lei</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="6" class="text-right">Subtotal {{ $group['supplier_name'] }}</td>
                    <td class="text-right">{{ number_format((float) $group['estimated_cost'], 2, ',', '.') }} lei</td>
                </tr>
            </tbody>
        </table>
    @empty
        <p class="muted" style="margin-top: 24px;">Nu există materiale de cumpărat pentru perioada selectată. Stocul acoperă necesarul.</p>
    @endforelse

    @if (! empty($groups))
        <table cellspacing="0" cellpadding="0" style="width: 100%; margin-top: 16px;">
            <tr>
                <td style="width: 58%;"></td>
                <td style="width: 42%;">
                    <table class="grand-total" cellspacing="0" cellpadding="0" style="width: 100%;">
                        <tr><td class="line">Total materiale: {{ number_format((float) ($totals['to_buy'] ?? 0), 2, ',', '.') }} unități</td></tr>
                        <tr><td class="grand" style="padding-top: 6px;">COST ESTIMAT: {{ number_format((float) ($totals['estimated_cost'] ?? 0), 2, ',', '.') }} lei</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    @endif

    <div class="footer muted">
        Listă generată prin platforma {{ $settings['company_name'] ?? 'Om Priceput' }}.
        Costurile sunt estimări pe baza prețului de achiziție din catalog și se actualizează cu prețul real de pe bon.
    </div>
</body>
</html>
