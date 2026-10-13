<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Programare #{{ $i->id }} - Om Priceput</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f1f5f9; color: #0f172a; }
        .wrap { max-width: 520px; margin: 0 auto; padding: 16px; }
        .card { background: #fff; border-radius: 14px; padding: 18px; margin-bottom: 14px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #64748b; font-size: 13px; }
        dl { margin: 12px 0 0; }
        dt { font-size: 12px; color: #64748b; margin-top: 12px; }
        dd { margin: 2px 0 0; font-size: 16px; white-space: pre-line; }
        a { color: #ea580c; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 13px; font-weight: 600; }
        .b-wait { background: #fef3c7; color: #92400e; }
        .b-ok { background: #dcfce7; color: #166534; }
        .b-no { background: #fee2e2; color: #991b1b; }
        .flash { background: #e0f2fe; color: #075985; padding: 12px 14px; border-radius: 10px; margin-bottom: 14px; font-size: 15px; }
        .btns { display: grid; gap: 10px; }
        button { width: 100%; padding: 16px; font-size: 17px; font-weight: 700; border: 0; border-radius: 12px; cursor: pointer; }
        .yes { background: #16a34a; color: #fff; }
        .no { background: #fff; color: #b91c1c; border: 2px solid #fecaca; }
    </style>
</head>
<body>
<div class="wrap">
    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <div class="card">
        <h1>Programare #{{ $i->id }}</h1>
        <p class="muted">Om Priceput</p>

        @if ($i->status === 'cancelled')
            <span class="badge b-no">Refuzată / anulată</span>
        @elseif ($i->status === 'completed')
            <span class="badge b-ok">Finalizată</span>
        @elseif ($i->confirmed_at)
            <span class="badge b-ok">Confirmată</span>
        @else
            <span class="badge b-wait">În așteptare</span>
        @endif

        <dl>
            <dt>Client</dt>
            <dd>{{ $i->client?->name }}</dd>

            @if ($i->client?->phone)
                <dt>Telefon</dt>
                <dd><a href="tel:{{ $i->client->phone }}">{{ $i->client->phone }}</a></dd>
            @endif

            <dt>Data și ora</dt>
            <dd>{{ $i->scheduled_at ? $i->scheduled_at->format('d.m.Y H:i') : 'Nu a ales o oră. Sună clientul.' }}</dd>

            <dt>Adresă</dt>
            <dd>{{ $i->address ?: '-' }}</dd>

            @if (count($services))
                <dt>Lucrări dorite</dt>
                <dd>{{ implode("\n", $services) }}</dd>
            @endif

            @if ($i->notes)
                <dt>Note</dt>
                <dd>{{ $i->notes }}</dd>
            @endif

            @if ($i->customer_notes)
                <dt>Mesajul clientului</dt>
                <dd>{{ $i->customer_notes }}</dd>
            @endif
        </dl>
    </div>

    @if (in_array($i->status, ['scheduled', 'in_progress'], true))
        <form method="POST" action="{{ route('booking.action.update', $token) }}" class="btns">
            @csrf
            @if ($i->scheduled_at && ! $i->confirmed_at)
                <button type="submit" name="action" value="confirm" class="yes">Confirmă programarea</button>
            @endif
            <button type="submit" name="action" value="cancel" class="no"
                    onclick="return confirm('Refuzi această programare? Clientul va primi SMS.')">Refuză / alt termen</button>
        </form>
    @endif
</div>
</body>
</html>