<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante {{ $comprobanteNumero }}</title>
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

        .comprobante-box {
            border: 1px solid #1f2937;
            padding: 8px 12px;
            text-align: right;
        }

        .comprobante-numero {
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

        .customer-box {
            border: 1px solid #d1d5db;
            padding: 8px 12px;
        }

        .lines-table {
            margin-top: 8px;
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

        .totals-table {
            width: 260px;
            margin-left: auto;
            margin-top: 12px;
        }

        .totals-table td {
            padding: 3px 8px;
        }

        .totals-table .total-row td {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #1f2937;
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
                <div>{{ App\Models\CompanySetting::condicionIvaOptions()[$company?->condicion_iva] ?? '-' }}</div>
                <div>{{ $company?->domicilio_fiscal }}</div>
                @if ($company?->telefono)
                    <div>Tel: {{ $company->telefono }}</div>
                @endif
                @if ($company?->email)
                    <div>{{ $company->email }}</div>
                @endif
            </td>
            <td style="width: 40%;">
                <div class="comprobante-box">
                    <div>COMPROBANTE</div>
                    <div class="comprobante-numero">{{ $comprobanteNumero }}</div>
                    <div>Fecha: {{ $sale->fecha->format('d/m/Y') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Cliente</div>
    <div class="customer-box">
        <div><strong>{{ $sale->customer->razon_social }}</strong></div>
        <div>CUIT: {{ $sale->customer->cuit ?? '-' }}</div>
        <div>{{ trim(($sale->customer->domicilio ?? '').' '.($sale->customer->localidad ?? '')) ?: '-' }}</div>
        <div>Condición de pago: {{ $sale->condicion_pago }}</div>
    </div>

    <table class="lines-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Precio unit.</th>
                <th class="text-right">Descuento</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->lines as $line)
                <tr>
                    <td>{{ $line->product->barcode ?? '-' }}</td>
                    <td>{{ $line->product->nombre }}</td>
                    <td class="text-right">{{ rtrim(rtrim(number_format((float) $line->cantidad, 3, ',', '.'), '0'), ',') }}</td>
                    <td class="text-right">{{ number_format((float) $line->precio_unit, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format((float) $line->descuento, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format((float) $line->subtotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">{{ number_format((float) $sale->subtotal, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Descuento</td>
            <td class="text-right">{{ number_format((float) $sale->descuento, 2, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td>Total</td>
            <td class="text-right">{{ number_format((float) $sale->total, 2, ',', '.') }}</td>
        </tr>
    </table>
</body>
</html>
