<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background: #f1f5f9; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; border-top: 2px solid #1e293b; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; background: #dbeafe; color: #1e3a8a; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ $settings['company_name'] }}</h1>
            <p class="muted">
                {{ $settings['company_address'] }}<br>
                {{ $settings['company_email'] }} &middot; {{ $settings['company_phone'] }}
            </p>
        </div>
        <div style="text-align: right;">
            <h1>Factura {{ $invoice->invoice_number }}</h1>
            <p class="muted">Data emiterii: {{ $invoice->issued_at?->format('d.m.Y') ?? $invoice->created_at->format('d.m.Y') }}</p>
            @if ($invoice->due_at)
                <p class="muted">Scadenta: {{ $invoice->due_at->format('d.m.Y') }}</p>
            @endif
            <span class="badge">{{ strtoupper($invoice->status) }}</span>
        </div>
    </div>

    <div>
        <strong>Client:</strong> {{ $invoice->client->name }}
        @if ($invoice->client->company_name) ({{ $invoice->client->company_name }}) @endif<br>
        @if ($invoice->client->address) Adresa: {{ $invoice->client->address }}, {{ $invoice->client->city }} @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Descriere</th>
                <th class="text-right">Valoare</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->offer->title ?? 'Servicii si manopera' }}</td>
                <td class="text-right">{{ number_format($invoice->amount, 2) }} lei</td>
            </tr>
            <tr class="total-row">
                <td class="text-right">Total</td>
                <td class="text-right">{{ number_format($invoice->amount, 2) }} lei</td>
            </tr>
        </tbody>
    </table>

    <div class="muted" style="margin-top: 30px;">
        TVA inclus ({{ $settings['vat_percentage'] }}%). Factura generata automat prin platforma {{ $settings['company_name'] }}.
    </div>
</body>
</html>
