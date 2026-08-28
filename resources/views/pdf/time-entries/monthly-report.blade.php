<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de fichajes</title>
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
                    <div class="report-title">REPORTE DE FICHAJES</div>
                    <div>
                        Período:
                        {{ $desde ? \Illuminate\Support\Carbon::parse($desde)->format('d/m/Y') : 'Sin límite' }}
                        —
                        {{ $hasta ? \Illuminate\Support\Carbon::parse($hasta)->format('d/m/Y') : 'Sin límite' }}
                    </div>
                    <div>Generado: {{ now()->format('d/m/Y H:i') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Totales por empleado</div>
    <table class="summary-table">
        <thead>
            <tr>
                <th>Empleado</th>
                <th class="text-right">Horas</th>
                <th class="text-right">A cobrar</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary as $row)
                <tr>
                    <td>{{ $row['user']->name }}</td>
                    <td class="text-right">{{ number_format($row['hours'], 2, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($row['pay'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Detalle de ciclos</div>
    <table class="lines-table">
        <thead>
            <tr>
                <th>Empleado</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th class="text-right">Horas</th>
                <th class="text-right">A cobrar</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($entries as $entry)
                <tr>
                    <td>{{ $entry->user->name }}</td>
                    <td>{{ $entry->started_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $entry->ended_at?->format('d/m/Y H:i') }}</td>
                    <td class="text-right">{{ number_format($entry->hours(), 2, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($entry->pay(), 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
