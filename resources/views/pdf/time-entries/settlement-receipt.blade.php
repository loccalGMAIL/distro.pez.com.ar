<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Recibo de liquidación {{ $settlement->numero() }}</title>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #1f2937;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo {
            max-width: 140px;
            max-height: 80px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .report-box {
            border: 1px solid #1f2937;
            padding: 8px 12px;
            text-align: right;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #6b7280;
            margin-top: 16px;
            margin-bottom: 4px;
        }

        .summary-table th {
            background-color: #f3f4f6;
            text-align: left;
            padding: 4px 6px;
            font-size: 9px;
            text-transform: uppercase;
            border-bottom: 1px solid #d1d5db;
        }

        .summary-table td {
            padding: 4px 6px;
            font-size: 11px;
            border-bottom: 1px solid #e5e7eb;
        }

        .lines-table th {
            background-color: #f3f4f6;
            text-align: left;
            padding: 4px 6px;
            font-size: 9px;
            text-transform: uppercase;
            border-bottom: 1px solid #d1d5db;
            white-space: nowrap;
        }

        .lines-table td {
            padding: 4px 6px;
            font-size: 10px;
            border-bottom: 1px solid #e5e7eb;
            white-space: nowrap;
        }

        .text-right {
            text-align: right;
        }

        .total-box {
            margin-top: 12px;
            border: 1px solid #1f2937;
            padding: 8px 12px;
            text-align: right;
            font-size: 14px;
            font-weight: bold;
        }

        .anulada {
            color: #b91c1c;
            font-weight: bold;
        }

        .firma {
            margin-top: 48px;
            width: 60%;
        }

        .firma td {
            border-top: 1px solid #1f2937;
            padding-top: 4px;
            font-size: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                @if ($company?->logo_path)
                    <img class="logo" src="{{ Illuminate\Support\Facades\Storage::disk('public')->path($company->logo_path) }}">
                @endif
                <div class="company-name">{{ $company?->razon_social ?? '-' }}</div>
                <div>CUIT: {{ $company?->cuit ?? '-' }}</div>
            </td>
            <td style="width: 40%;">
                <div class="report-box">
                    <div class="report-title">RECIBO DE HONORARIOS</div>
                    <div>{{ $settlement->numero() }}</div>
                    <div>Período: {{ $settlement->periodoLegible() }}</div>
                    <div>Pago: {{ $settlement->fecha_pago->format('d/m/Y') }}</div>
                    @if ($settlement->status === 'anulada')
                        <div class="anulada">ANULADA</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Liquidación</div>
    <table class="summary-table">
        <thead>
            <tr>
                <th>Empleado</th>
                <th class="text-right">Horas</th>
                <th class="text-right">Tarifa</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $settlement->user->name }}</td>
                <td class="text-right">{{ number_format((float) $settlement->horas, 2, ',', '.') }}</td>
                <td class="text-right">${{ number_format((float) $settlement->tarifa_hora, 2, ',', '.') }}</td>
                <td class="text-right">${{ number_format((float) $settlement->total, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Forma de pago</div>
    <div>Medio: {{ ucfirst($settlement->medio_pago) }}</div>
    @if ($settlement->referencia)
        <div>Referencia: {{ $settlement->referencia }}</div>
    @endif
    @if ($settlement->observaciones)
        <div>Observaciones: {{ $settlement->observaciones }}</div>
    @endif

    <div class="total-box">
        TOTAL PAGADO: ${{ number_format((float) $settlement->total, 2, ',', '.') }}
    </div>

    @if ($entries->isNotEmpty())
        <div class="section-title">Detalle de ciclos liquidados</div>
        <table class="lines-table">
            <thead>
                <tr>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th class="text-right">Horas</th>
                    <th class="text-right">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entries as $entry)
                    <tr>
                        <td>{{ $entry->started_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $entry->ended_at?->format('d/m/Y H:i') }}</td>
                        <td class="text-right">{{ number_format($entry->hours(), 2, ',', '.') }}</td>
                        <td class="text-right">${{ number_format($entry->pay(), 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="firma">
        <tr>
            <td>Firma del empleado</td>
        </tr>
    </table>

    <div style="margin-top: 16px; font-size: 9px; color: #6b7280;">
        Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
