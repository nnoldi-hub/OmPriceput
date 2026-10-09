<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>Deviz #{{ $offer->id }}</title>
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
        .status { display: inline-block; margin-top: 5px; padding: 3px 8px; background: #fff4d6; color: #9a5b00; font-size: 8.5px; font-weight: bold; }
        .section { margin-top: 14px; }
        .section-title { color: #061426; font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .8px; }
        .client-card { margin-top: 6px; padding: 8px 11px; background: #f4f7fb; border-left: 3px solid #2563eb; }
        .offer-title { margin-top: 14px; padding-bottom: 6px; color: #061426; font-size: 13px; font-weight: bold; border-bottom: 2px solid #f59e0b; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 7px; }
        .items th { padding: 5px; background: #061426; color: #fff; font-size: 8.5px; text-align: left; }
        .items td { padding: 4px 5px; border-bottom: 1px solid #e2e8f0; }
        .items .subtotal-row td { border-bottom: none; border-top: 1px solid #cbd5e1; background: #f8fafc; font-weight: bold; }
        .text-right { text-align: right; }
        .section-heading { margin-top: 13px; color: #061426; font-size: 10px; font-weight: bold; }
        .section-hint { color: #64748b; font-size: 8px; }
        .total-box { margin-top: 14px; padding: 9px 12px; background: #061426; color: #fff; }
        .total-box .line { font-size: 9px; color: #cbd5e1; }
        .total-box .grand { font-size: 14px; font-weight: bold; }
        .notes { padding: 8px 11px; background: #fffaf0; border-left: 3px solid #f59e0b; line-height: 1.4; }
        .footer { margin-top: 18px; padding-top: 8px; border-top: 1px solid #dbe3ed; font-size: 8px; line-height: 1.4; }
    </style>
</head>
<body>
    @php
        $createdAt = $offer->created_at;
        $validUntil = $offer->valid_until ?? $createdAt->copy()->addDays(\App\Models\Offer::DEFAULT_VALIDITY_DAYS);
        if ($validUntil->lt($createdAt->copy()->startOfDay())) {
            $validUntil = $createdAt->copy()->addDays(\App\Models\Offer::DEFAULT_VALIDITY_DAYS);
        }
        $materials = $offer->itemsForSection(\App\Models\OfferItem::SECTION_MATERIALS);
        $labor = $offer->itemsForSection(\App\Models\OfferItem::SECTION_LABOR);
        $clientMaterials = $offer->itemsForSection(\App\Models\OfferItem::SECTION_CLIENT_MATERIALS);
    @endphp

    <div class="topbar"></div>

    <table class="header" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width: 55%;">
                <img class="logo" src="{{ public_path('branding/logo-trim.png') }}" alt="Om Priceput">
                <div class="brand-line">REPARĂM. MONTĂM. LĂSĂM TOTUL CA ATUNCI.</div>
            </td>
            <td style="width: 45%; text-align: right;">
                <div class="document-title">DEVIZ #{{ $offer->id }}</div>
                <div class="muted">Data: {{ $createdAt->format('d.m.Y') }}</div>
                <div class="muted">Valabilă până la: {{ $validUntil->format('d.m.Y') }}</div>
                <span class="status">{{ strtoupper($offer->status) }}</span>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Ofertă pentru</div>
        <div class="client-card">
            <strong>{{ $offer->client->name }}</strong>
            @if ($offer->client->company_name) &middot; {{ $offer->client->company_name }} @endif
            @if ($offer->client->phone)<br><span class="muted">Telefon: {{ $offer->client->phone }}</span>@endif
            @if ($offer->client->email)<br><span class="muted">Email: {{ $offer->client->email }}</span>@endif
            @if ($offer->client->address)<br><span class="muted">Adresă: {{ $offer->client->address }}, {{ $offer->client->city }}</span>@endif
        </div>
    </div>

    <div class="offer-title">{{ $offer->title }}</div>

    @if ($materials->isNotEmpty())
        <div class="section-heading">A. Materiale necesare</div>
        <div class="section-hint">Materialele furnizate de noi.</div>
        <table class="items" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th>Descriere</th>
                    <th class="text-right">Cant.</th>
                    <th class="text-right">U.M.</th>
                    <th class="text-right">Preț unitar</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($materials as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">{{ $item->unit }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2, ',', '.') }} lei</td>
                        <td class="text-right">{{ number_format($item->subtotal, 2, ',', '.') }} lei</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="4" class="text-right">Subtotal materiale (A)</td>
                    <td class="text-right">{{ number_format($offer->materialsSubtotal(), 2, ',', '.') }} lei</td>
                </tr>
            </tbody>
        </table>
    @endif

    @if ($labor->isNotEmpty())
        <div class="section-heading">B. Manoperă</div>
        <div class="section-hint">Serviciile prestate de echipa noastră.</div>
        <table class="items" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th>Descriere</th>
                    <th class="text-right">Cant.</th>
                    <th class="text-right">U.M.</th>
                    <th class="text-right">Preț unitar</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($labor as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">{{ $item->unit }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2, ',', '.') }} lei</td>
                        <td class="text-right">{{ number_format($item->subtotal, 2, ',', '.') }} lei</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="4" class="text-right">Subtotal manoperă (B)</td>
                    <td class="text-right">{{ number_format($offer->laborSubtotal(), 2, ',', '.') }} lei</td>
                </tr>
            </tbody>
        </table>
    @endif

    @if ($clientMaterials->isNotEmpty())
        <div class="section-heading">C. Materiale achiziționate de client</div>
        <div class="section-hint">Listă informativă. Aceste materiale nu intră în totalul devizului.</div>
        <table class="items" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th>Descriere</th>
                    <th class="text-right">Cant.</th>
                    <th class="text-right">U.M.</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clientMaterials as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">{{ $item->unit }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table cellspacing="0" cellpadding="0" style="width: 100%; margin-top: 12px;">
        <tr>
            <td style="width: 58%;"></td>
            <td style="width: 42%;">
                <table class="total-box" cellspacing="0" cellpadding="0" style="width: 100%;">
                    <tr><td class="line">Materiale (A): {{ number_format($offer->materialsSubtotal(), 2, ',', '.') }} lei</td></tr>
                    <tr><td class="line">Manoperă (B): {{ number_format($offer->laborSubtotal(), 2, ',', '.') }} lei</td></tr>
                    <tr><td class="grand" style="padding-top: 6px;">TOTAL (A + B): {{ number_format($offer->pricedSubtotal(), 2, ',', '.') }} lei</td></tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($offer->notes)
        <div class="section">
            <div class="section-title">Observații</div>
            <div class="notes">{{ $offer->notes }}</div>
        </div>
    @endif

    <div class="footer muted">
        Deviz generat prin platforma Om Priceput. Prețurile sunt exprimate în lei și includ manopera și
        materialele listate mai sus. Materialele achiziționate de client sunt menționate doar informativ.
        Lucrarea se consideră acceptată după semnarea devizului.
    </div>
</body>
</html>
