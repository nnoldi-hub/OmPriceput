<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Situatie financiara {{ $client->name }}</title>
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
        .summary-grid { width: 100%; margin-top: 10px; }
        .summary-grid td { width: 25%; padding: 10px; background: #f4f7fb; text-align: center; }
        .summary-value { font-size: 15px; font-weight: bold; color: #061426; }
        .summary-label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 9px; }
        table.data th, table.data td { padding: 6px 5px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        table.data th { background: #f1f5f9; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #475569; }
        .text-right { text-align: right; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 4px; background: #dbeafe; color: #1e3a8a; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .badge-overdue { background: #fee2e2; color: #991b1b; }
        .badge-paid { background: #dcfce7; color: #166534; }
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
                <h1>SITUATIE FINANCIARA</h1>
                <div class="muted">Client: {{ $client->name }}</div>
                <div class="muted">Generat la {{ now()->format('d.m.Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Date client</div>
        <div class="info-card">
            <table style="margin-top: 0;">
                <tr>
                    <td style="width: 30%;"><strong>Client:</strong></td>
                    <td>{{ $client->name }}@if($client->company_name) ({{ $client->company_name }}) @endif</td>
                </tr>
                <tr>
                    <td><strong>Adresa:</strong></td>
                    <td>{{ implode(', ', array_filter([$client->address, $client->city, $client->county])) ?: '-' }}</td>
                </tr>
                <tr>
                    <td><strong>Contact:</strong></td>
                    <td>{{ $client->phone ?? '-' }} @if($client->email) &middot; {{ $client->email }} @endif</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Rezumat financiar</div>
        <table class="summary-grid">
            <tr>
                <td>
                    <div class="summary-value">{{ number_format($summary['invoiceTotal'], 2) }} lei</div>
                    <div class="summary-label">Total facturat</div>
                </td>
                <td>
                    <div class="summary-value" style="color:#166534;">{{ number_format($summary['invoicePaid'], 2) }} lei</div>
                    <div class="summary-label">Total achitat</div>
                </td>
                <td>
                    <div class="summary-value" style="{{ $summary['invoiceBalance'] > 0 ? 'color:#991b1b;' : '' }}">{{ number_format($summary['invoiceBalance'], 2) }} lei</div>
                    <div class="summary-label">Sold ramas</div>
                </td>
                <td>
                    <div class="summary-value">{{ $summary['overdueInvoices'] }}</div>
                    <div class="summary-label">Facturi restante</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Facturi ({{ $invoices->count() }})</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Numar</th>
                    <th>Emitere</th>
                    <th>Scadenta</th>
                    <th class="text-right">Suma</th>
                    <th class="text-right">Achitat</th>
                    <th class="text-right">Sold</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->issued_at?->format('d.m.Y') ?? $invoice->created_at->format('d.m.Y') }}</td>
                        <td>{{ $invoice->due_at?->format('d.m.Y') ?? '-' }}</td>
                        <td class="text-right">{{ number_format($invoice->amount, 2) }} lei</td>
                        <td class="text-right">{{ number_format($invoice->paid_amount, 2) }} lei</td>
                        <td class="text-right">{{ number_format(max($invoice->amount - $invoice->paid_amount, 0), 2) }} lei</td>
                        <td>
                            <span class="badge @if($invoice->status === 'overdue') badge-overdue @elseif($invoice->status === 'paid') badge-paid @endif">
                                {{ $invoice->status }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Istoric plati</div>
        @if ($payments->count())
            <table class="data">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Factura</th>
                        <th>Metoda</th>
                        <th>Referinta</th>
                        <th class="text-right">Suma</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at?->format('d.m.Y') ?? '-' }}</td>
                            <td>{{ $payment->invoice->invoice_number }}</td>
                            <td class="capitalize">{{ $payment->payment_method ?? '-' }}</td>
                            <td>{{ $payment->payment_reference ?? '-' }}</td>
                            <td class="text-right">{{ number_format($payment->amount, 2) }} lei</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="muted">Nicio plata inregistrata.</p>
        @endif
    </div>

    <div class="footer">
        <strong>{{ $settings['company_name'] }}</strong>
        @if ($settings['company_address']) &middot; {{ $settings['company_address'] }} @endif
        &middot; {{ $settings['company_email'] }} &middot; {{ $settings['company_phone'] }}
    </div>
</body>
</html>
