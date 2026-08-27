<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Lista de precios {{ $priceList->nombre }}</title>
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

        .price-list-box {
            border: 1px solid #1f2937;
            padding: 8px 12px;
            text-align: right;
        }

        .price-list-nombre {
            font-size: 16px;
            font-weight: bold;
        }

        .products-table {
            margin-top: 16px;
        }

        .products-table th {
            background-color: #f3f4f6;
            text-align: left;
            padding: 4px 6px;
            font-size: 9px;
            text-transform: uppercase;
            border-bottom: 1px solid #d1d5db;
            white-space: nowrap;
        }

        .products-table td {
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
                <div class="price-list-box">
                    <div>LISTA DE PRECIOS</div>
                    <div class="price-list-nombre">{{ $priceList->nombre }}</div>
                    <div>Fecha: {{ now()->format('d/m/Y') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="products-table">
        <thead>
            <tr>
                <th>Código de barra</th>
                <th>Producto</th>
                <th>Presentación</th>
                <th class="text-right">Precio</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td>{{ $product->barcode ?? '-' }}</td>
                    <td>{{ $product->nombre }}</td>
                    <td>{{ $product->baseUnitLabel() }}</td>
                    <td class="text-right">$ {{ number_format((float) $priceList->precioPara($product), 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
