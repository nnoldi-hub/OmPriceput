<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Părerea ta - {{ \App\Models\Setting::get('company_name') ?: 'Om Priceput' }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f1f5f9; color: #0f172a; }
        .wrap { max-width: 520px; margin: 0 auto; padding: 16px; }
        .card { background: #fff; border-radius: 14px; padding: 20px; margin-bottom: 14px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        h1 { font-size: 20px; margin: 0 0 4px; }
        p.muted { color: #64748b; font-size: 13px; margin: 0; }
        label.field { display: block; margin-top: 16px; font-size: 13px; font-weight: 600; color: #334155; }
        input[type="text"], textarea {
            width: 100%; margin-top: 6px; padding: 12px; font-size: 16px; font-family: inherit;
            border: 1px solid #cbd5e1; border-radius: 10px; background: #fff;
        }
        textarea { min-height: 110px; resize: vertical; }
        .check { display: flex; align-items: flex-start; gap: 10px; margin-top: 16px; font-size: 14px; color: #334155; }
        .stars { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 4px; margin-top: 6px; }
        .stars input { display: none; }
        .stars label { font-size: 40px; line-height: 1; color: #e2e8f0; cursor: pointer; transition: color .1s; }
        .stars input:checked ~ label,
        .stars label:hover,
        .stars label:hover ~ label { color: #f59e0b; }
        .stars { direction: rtl; }
        .stars label { direction: ltr; }
        button { width: 100%; padding: 16px; margin-top: 20px; font-size: 17px; font-weight: 700; border: 0; border-radius: 12px; cursor: pointer; background: #ea580c; color: #fff; }
        button:hover { background: #f97316; }
        .flash { background: #e0f2fe; color: #075985; padding: 12px 14px; border-radius: 10px; margin-bottom: 14px; font-size: 15px; }
        .err { color: #b91c1c; font-size: 13px; margin-top: 6px; }
        .thanks { text-align: center; }
        .thanks .emoji { font-size: 44px; }
        a { color: #ea580c; }
    </style>
</head>
<body>
<div class="wrap">
    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <div class="card">
        <h1>{{ \App\Models\Setting::get('company_name') ?: 'Om Priceput' }}</h1>
        <p class="muted">Íți mulțumim că ai apelat la noi.</p>
    </div>

    <div class="card">
        @if ($review->status !== \App\Models\Review::STATUS_PENDING)
            <div class="thanks">
                <div class="emoji">🙏</div>
                <h1>Îți mulțumim!</h1>
                <p class="muted">Părerea ta a fost înregistrată și o vom publica după o scurtă verificare.</p>
                @if ($review->rating)
                    <p style="font-size: 28px; color: #f59e0b; margin: 14px 0 0;">
                        {{ str_repeat('★', $review->rating) }}<span style="color:#e2e8f0;">{{ str_repeat('★', 5 - $review->rating) }}</span>
                    </p>
                @endif
            </div>
        @else
            <h1>Cum a fost lucrarea?</h1>
            <p class="muted">Notează de la 1 la 5 stele și, dacă vrei, lasă câteva cuvinte.</p>

            @if ($errors->any())
                <div class="err">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('review.feedback.store', $token) }}">
                @csrf

                <label class="field">Nota ta</label>
                <div class="stars">
                    @foreach ([5, 4, 3, 2, 1] as $value)
                        <input type="radio" id="rating-{{ $value }}" name="rating" value="{{ $value }}"
                               @checked(old('rating') == $value) required>
                        <label for="rating-{{ $value }}">★</label>
                    @endforeach
                </div>

                <label class="field" for="comment">Ce ai apreciat sau ce putem îmbunătăți?</label>
                <textarea id="comment" name="comment" maxlength="2000">{{ old('comment') }}</textarea>

                <label class="field" for="author_name">Numele afișat (opțional)</label>
                <input type="text" id="author_name" name="author_name" maxlength="255" value="{{ old('author_name') }}" placeholder="Ex: Andrei M.">

                <label class="check">
                    <input type="checkbox" name="consent_to_publish" value="1" @checked(old('consent_to_publish'))>
                    <span>Accept ca recenzia mea (nume, oraș și text) să fie publicată pe site.</span>
                </label>

                <button type="submit">Trimite recenzia</button>
            </form>
        @endif
    </div>
</div>
</body>
</html>
