<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — WhatsApp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&family=Fraunces:opsz,wght@9..144,500;9..144,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #163230;
            --paper: #f3ece0;
            --card: #fffaf2;
            --rule: #d8cbb6;
            --sunset: #c45c3e;
            --whatsapp: #128c7e;
            --whatsapp-bright: #25d366;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
        }

        body {
            font-family: "Atkinson Hyperlegible", system-ui, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 500px at 10% -10%, rgba(196, 92, 62, 0.18), transparent 55%),
                radial-gradient(900px 420px at 110% 0%, rgba(18, 140, 126, 0.16), transparent 50%),
                var(--paper);
            line-height: 1.5;
        }

        .wrap {
            max-width: 34rem;
            margin: 0 auto;
            padding: 1.5rem 1.15rem 3rem;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--rule);
            border-radius: 1.35rem;
            padding: 1.75rem 1.4rem 1.5rem;
            box-shadow: 0 18px 40px rgba(22, 50, 48, 0.08);
        }

        .kicker {
            letter-spacing: 0.16em;
            text-transform: uppercase;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--sunset);
            margin: 0 0 0.35rem;
        }

        h1 {
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(2rem, 8vw, 2.8rem);
            font-weight: 700;
            line-height: 1.05;
            margin: 0 0 0.75rem;
        }

        .lede {
            font-size: 1.12rem;
            margin: 0 0 1.4rem;
        }

        .cta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
            width: 100%;
            background: var(--whatsapp);
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.2rem;
            padding: 1rem 1.1rem;
            border-radius: 999px;
            box-shadow: 0 10px 24px rgba(18, 140, 126, 0.28);
        }

        .cta:focus-visible {
            outline: 3px solid var(--sunset);
            outline-offset: 3px;
        }

        .cta svg {
            width: 1.6rem;
            height: 1.6rem;
            fill: var(--whatsapp-bright);
        }

        .number {
            margin: 1.15rem 0 0;
            text-align: center;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .hint {
            margin: 0.35rem 0 0;
            text-align: center;
            color: #4a5f5d;
            font-size: 0.95rem;
        }

        ol {
            margin: 1.5rem 0 0;
            padding: 0;
            list-style: none;
            counter-reset: step;
        }

        ol li {
            counter-increment: step;
            display: grid;
            grid-template-columns: 2rem 1fr;
            gap: 0.7rem;
            margin: 0 0 0.85rem;
            align-items: start;
        }

        ol li::before {
            content: counter(step);
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: var(--ink);
            color: var(--card);
            font-weight: 700;
        }

        .alt {
            margin-top: 1.4rem;
            padding-top: 1.15rem;
            border-top: 1px dashed var(--rule);
            font-size: 0.98rem;
        }

        .alt a {
            color: var(--ink);
            font-weight: 700;
        }

        .unconfigured {
            background: #fff3e8;
            border: 1px solid #e7c8b0;
            border-radius: 0.9rem;
            padding: 1rem 1.1rem;
        }

        code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 0.92em;
        }
    </style>
</head>
<body>
    <main class="wrap">
        <article class="card">
            <p class="kicker">Wi-Fi helpline</p>
            <h1>{{ $title }}</h1>
            <p class="lede">No U.S. dialing needed. Message the helpline from WhatsApp over Wi-Fi.</p>

            @if ($configured)
                <a class="cta" href="{{ $clickToChatUrl }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2C6.58 2 2.15 6.4 2.15 11.83c0 1.74.46 3.45 1.32 4.95L2 22l5.39-1.41a10.1 10.1 0 0 0 4.65 1.13h.01c5.46 0 9.89-4.4 9.89-9.84C21.94 6.4 17.5 2 12.04 2zm5.76 14.13c-.24.68-1.4 1.3-1.94 1.34-.5.04-1.12.06-1.81-.11-.42-.11-.95-.31-1.64-.6-2.89-1.25-4.77-4.16-4.92-4.35-.14-.19-1.18-1.57-1.18-3 0-1.42.75-2.12 1.01-2.41.26-.29.57-.36.76-.36h.55c.18 0 .42-.07.66.5.24.58.82 2 .89 2.15.07.14.12.31.02.5-.1.19-.14.31-.28.48-.14.16-.3.37-.42.49-.14.14-.28.29-.12.56.16.26.7 1.16 1.5 1.88 1.04.93 1.91 1.22 2.18 1.36.27.14.43.12.59-.07.16-.19.68-.79.86-1.06.18-.26.36-.22.61-.13.25.1 1.57.74 1.84.87.27.14.45.2.52.31.07.12.07.68-.17 1.36z"/></svg>
                    Open WhatsApp
                </a>
                <p class="number">{{ $displayNumber }}</p>
                <p class="hint">Save this page, or print it for hotel desks and meeting lists.</p>
            @else
                <div class="unconfigured">
                    <p style="margin:0">This helpline has not published a WhatsApp number yet. Operators set <code>$whatsapp_number</code> in <code>config.php</code>.</p>
                </div>
            @endif

            <ol>
                <li>Send a city, county, or zip code to get nearby meetings.</li>
                <li>Send <strong>{{ $keyword }}</strong> and your location to reach a volunteer — for example <strong>{{ $keyword }} Orlando</strong>.</li>
            </ol>

            @if ($webrtcEnabled)
                <p class="alt">You can also <a href="{{ url('/widget-demo.html') }}">call from this browser over Wi-Fi</a> without WhatsApp.</p>
            @endif
        </article>
    </main>
</body>
</html>
