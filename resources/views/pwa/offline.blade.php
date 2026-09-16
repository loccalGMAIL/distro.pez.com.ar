{{--
    Pantalla que muestra el service worker cuando una navegación falla sin
    conexión. Es deliberadamente autosuficiente: nada de Vite, nada de fuentes
    externas y el logo va en SVG inline, porque cuando esta vista se ve no hay
    red para ir a buscar ningún asset suelto.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#111827" media="(prefers-color-scheme: dark)">
    <title>Sin conexión — {{ $name }}</title>
    <style>
        :root {
            color-scheme: light dark;
            --bg: #f9fafb;
            --surface: #ffffff;
            --text: #111827;
            --muted: #6b7280;
            --ring: rgba(9, 9, 11, 0.06);
            --accent: #f97316;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #111827;
                --surface: #1f2937;
                --text: #f9fafb;
                --muted: #9ca3af;
                --ring: rgba(255, 255, 255, 0.1);
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: calc(1.5rem + env(safe-area-inset-top)) 1.5rem calc(1.5rem + env(safe-area-inset-bottom));
            background: var(--bg);
            color: var(--text);
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            line-height: 1.5;
        }

        .card {
            width: 100%;
            max-width: 26rem;
            padding: 2rem;
            border-radius: 0.75rem;
            background: var(--surface);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05), 0 0 0 1px var(--ring);
            text-align: center;
        }

        .logo {
            width: 4rem;
            height: 4rem;
        }

        h1 {
            margin: 1.25rem 0 0.5rem;
            font-size: 1.25rem;
            font-weight: 600;
        }

        p {
            margin: 0;
            color: var(--muted);
            font-size: 0.925rem;
        }

        button {
            margin-top: 1.5rem;
            width: 100%;
            padding: 0.7rem 1rem;
            border: 0;
            border-radius: 0.5rem;
            background: var(--accent);
            color: #ffffff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        button:active {
            filter: brightness(0.92);
        }
    </style>
</head>
<body>
    <main class="card">
        <svg class="logo" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ $name }}">
            <circle cx="32" cy="32" r="30.016" fill="#111111"/>
            <g transform="translate(17.30498771485676 50.877969566955365) scale(0.07084770153231179)" fill="#F97316">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M268.100-106.400L268.100-267.400Q253.400-285.250 236.075-292.600Q218.750-299.950 198.800-299.950Q179.200-299.950 163.450-292.600Q147.700-285.250 136.500-270.375Q125.300-255.500 119.350-232.575Q113.400-209.650 113.400-178.500Q113.400-147 118.475-125.125Q123.550-103.250 133-89.425Q142.450-75.600 156.100-69.475Q169.750-63.350 186.550-63.350Q213.500-63.350 232.400-74.550Q251.300-85.750 268.100-106.400M268.100-520.100L354.550-520.100L354.550 0L301.700 0Q284.550 0 280-15.750L272.650-50.400Q250.950-25.550 222.775-10.150Q194.600 5.250 157.150 5.250Q127.750 5.250 103.250-7Q78.750-19.250 61.075-42.525Q43.400-65.800 33.775-100.100Q24.150-134.400 24.150-178.500Q24.150-218.400 35-252.700Q45.850-287 66.150-312.200Q86.450-337.400 114.800-351.575Q143.150-365.750 178.500-365.750Q208.600-365.750 229.950-356.300Q251.300-346.850 268.100-330.750"/>
            </g>
        </svg>

        <h1>Sin conexión</h1>

        <p>
            No se pudo contactar al servidor. Revisá el wifi o los datos del
            celular y volvé a intentar.
        </p>

        <button type="button" onclick="window.location.reload()">
            Reintentar
        </button>
    </main>
</body>
</html>
