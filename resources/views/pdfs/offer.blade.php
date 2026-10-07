<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>Oferta #{{ $offer->id }}</title>
    <style>
        @page { margin: 32px 38px 42px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #172033; }
        .topbar { height: 8px; background: #f59e0b; }
        .header { width: 100%; padding: 22px 0 20px; border-bottom: 1px solid #dbe3ed; }
        .header td { vertical-align: top; }
        .logo { width: 215px; height: auto; }
        .brand-line { color: #64748b; font-size: 10px; letter-spacing: 1.4px; margin-top: 8px; }
        .document-title { color: #061426; font-size: 21px; font-weight: bold; margin: 0 0 6px; }
        .muted { color: #64748b; }
        .status { display: inline-block; margin-top: 8px; padding: 5px 10px; background: #fff4d6; color: #9a5b00; font-size: 10px; font-weight: bold; }
        .section { margin-top: 24px; }
        .section-title { color: #061426; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: .8px; }
        .client-card { margin-top: 9px; padding: 13px 15px; background: #f4f7fb; border-left: 4px solid #2563eb; }
        .offer-title { margin-top: 24px; padding-bottom: 10px; color: #061426; font-size: 16px; font-weight: bold; border-bottom: 2px solid #f59e0b; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .items th { padding: 10px 8px; background: #061426; color: #fff; font-size: 10px; text-align: left; }
        .items td { padding: 10px 8px; border-bottom: 1px solid #e2e8f0; }
        .text-right { text-align: right; }
        .total-row td { padding-top: 14px; color: #061426; font-size: 13px; font-weight: bold; border-top: 2px solid #061426; }
        .notes { padding: 12px 15px; background: #fffaf0; border-left: 4px solid #f59e0b; line-height: 1.5; }
        .footer { margin-top: 34px; padding-top: 14px; border-top: 1px solid #dbe3ed; font-size: 9px; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="topbar"></div>

    <table class="header" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width: 55%;">
                <img class="logo" src="{{ public_path('branding/op-logo.png') }}" alt="Om Priceput">
                <div class="brand-line">REPARAM. MONTAM. LASAM TOTUL CA ATUNCI.</div>
            </td>
            <td style="width: 45%; text-align: right;">
                <div class="document-title">DEVIZ #{{ $offer->id }}</div>
                <div class="muted">Data: {{ $offer->created_at->format('d.m.Y') }}</div>
                @if ($offer->valid_until)
                    <div class="muted">Valabila pana la: {{ $offer->valid_until->format('d.m.Y') }}</div>
                @endif
                <span class="status">{{ strtoupper($offer->status) }}</span>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Oferta pentru</div>
        <div class="client-card">
            <strong>{{ $offer->client->name }}</strong>
            @if ($offer->client->company_name) &middot; {{ $offer->client->company_name }} @endif
            @if ($offer->client->phone)<br><span class="muted">Telefon: {{ $offer->client->phone }}</span>@endif
            @if ($offer->client->email)<br><span class="muted">Email: {{ $offer->client->email }}</span>@endif
            @if ($offer->client->address)<br><span class="muted">Adresa: {{ $offer->client->address }}, {{ $offer->client->city }}</span>@endif
        </div>
    </div>

    <div class="offer-title">{{ $offer->title }}</div>

    <table class="items" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th>Descriere</th>
                <th class="text-right">Cant.</th>
                <th class="text-right">Pret unitar</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($offer->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2, ',', '.') }} lei</td>
                    <td class="text-right">{{ number_format($item->quantity * $item->unit_price, 2, ',', '.') }} lei</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL</td>
                <td class="text-right">{{ number_format($offer->total_amount, 2, ',', '.') }} lei</td>
            </tr>
        </tbody>
    </table>

    @if ($offer->notes)
        <div class="section">
            <div class="section-title">Note</div>
            <div class="notes">{{ $offer->notes }}</div>
        </div>
    @endif

    <div class="footer muted">
        Deviz generat prin platforma Om Priceput. Preturile sunt exprimate in lei si includ manopera si
        materialele listate mai sus. Lucrarea se considera acceptata dupa semnarea devizului.
    </div>
</body>
</html>
