<?php

namespace App\Services;

use App\Models\PerceptionType;
use App\Models\Product;
use App\Models\SupplierProductLink;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * @phpstan-type CatalogItem array{id: int, nombre: string, barcode: string|null, categoria: string|null}
 * @phpstan-type PerceptionCatalogItem array{id: int, nombre: string}
 * @phpstan-type InvoiceLine array{descripcion: string, description_key: string, cantidad: float, unidad: string, precio_unitario: float, subtotal: float, matched_product_id: int|null, consistente: bool}
 * @phpstan-type InvoicePerception array{descripcion: string, description_key: string, monto: float, matched_perception_type_id: int|null}
 * @phpstan-type InvoiceExtraction array{proveedor: mixed, cuit: mixed, tipo_comprobante: mixed, punto_venta: mixed, numero: string|null, fecha: string|null, vencimiento: string|null, subtotal: float|null, total: float|null, lineas: array<int, InvoiceLine>, percepciones: array<int, InvoicePerception>}
 */
class InvoiceExtractor
{
    /**
     * Mapeo de texto libre de unidad (como puede venir escrito en una
     * factura) a los valores reales del enum Product::base_unit. Todo lo que
     * no matchea cae en 'unidad' por defecto.
     */
    private const UNIT_MAP = [
        'kg' => 'kg', 'kilo' => 'kg', 'kilos' => 'kg', 'kilogramo' => 'kg', 'kilogramos' => 'kg',
        'g' => 'gramo', 'gr' => 'gramo', 'grs' => 'gramo', 'gramo' => 'gramo', 'gramos' => 'gramo',
        'l' => 'litro', 'lt' => 'litro', 'lts' => 'litro', 'litro' => 'litro', 'litros' => 'litro',
        'ml' => 'mililitro', 'mililitro' => 'mililitro', 'mililitros' => 'mililitro',
        'docena' => 'docena', 'doc' => 'docena', 'docenas' => 'docena',
        'caja' => 'caja', 'cajas' => 'caja', 'cj' => 'caja',
        'bulto' => 'bulto', 'bultos' => 'bulto',
        'paquete' => 'paquete', 'paq' => 'paquete', 'paquetes' => 'paquete',
        'm' => 'metro', 'mt' => 'metro', 'mts' => 'metro', 'metro' => 'metro', 'metros' => 'metro',
        'u' => 'unidad', 'un' => 'unidad', 'unid' => 'unidad', 'unidad' => 'unidad', 'unidades' => 'unidad',
        'bolsa' => 'unidad', 'bolsas' => 'unidad', 'bidon' => 'unidad', 'bidones' => 'unidad',
    ];

    private const ANTHROPIC_VERSION = '2023-06-01';

    private const CONSISTENCY_TOLERANCE = 0.05;

    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly ?string $model = null,
    ) {}

    /**
     * Sube la imagen/PDF de una factura a Claude y devuelve la extracción ya
     * normalizada (decimales, unidades, matches validados contra el
     * catálogo real). No hace ninguna cuenta propia: transcribe lo que dice
     * la factura, la aritmética y las decisiones quedan para la revisión
     * humana.
     *
     * @return InvoiceExtraction
     */
    public function extract(string $absolutePath, string $mime): array
    {
        $apiKey = $this->apiKey ?? config('services.anthropic.key');

        if (blank($apiKey)) {
            throw new RuntimeException('ANTHROPIC_API_KEY no está configurada.');
        }

        $model = $this->model ?? config('services.anthropic.model');
        $catalog = $this->activeCatalog();
        $perceptionCatalog = $this->activePerceptionCatalog();

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => self::ANTHROPIC_VERSION,
            'content-type' => 'application/json',
        ])
            ->timeout(90)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 4096,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        $this->documentBlock($absolutePath, $mime),
                        ['type' => 'text', 'text' => $this->prompt($catalog, $perceptionCatalog)],
                    ],
                ]],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Error llamando a la API de Claude: '.$response->body());
        }

        $content = $response->json('content');
        $textBlock = collect(is_array($content) ? $content : [])->firstWhere('type', 'text');
        $text = is_array($textBlock) ? (string) ($textBlock['text'] ?? '') : '';

        return $this->normalize(
            $this->parseJson($text),
            array_column($catalog, 'id'),
            array_column($perceptionCatalog, 'id'),
        );
    }

    /**
     * @return array{type: string, source: array{type: string, media_type: string, data: string}}
     */
    private function documentBlock(string $absolutePath, string $mime): array
    {
        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            throw new RuntimeException("No se pudo leer el archivo {$absolutePath}.");
        }

        $data = base64_encode($contents);

        return [
            'type' => $mime === 'application/pdf' ? 'document' : 'image',
            'source' => [
                'type' => 'base64',
                'media_type' => $mime,
                'data' => $data,
            ],
        ];
    }

    /**
     * @return array<int, CatalogItem>
     */
    private function activeCatalog(): array
    {
        return Product::query()
            ->where('activo', true)
            ->with('category:id,nombre')
            ->get(['id', 'nombre', 'barcode', 'product_category_id'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'nombre' => $product->nombre,
                'barcode' => $product->barcode,
                'categoria' => $product->category?->nombre,
            ])
            ->all();
    }

    /**
     * @return array<int, PerceptionCatalogItem>
     */
    private function activePerceptionCatalog(): array
    {
        return PerceptionType::query()
            ->where('activo', true)
            ->get(['id', 'nombre'])
            ->map(fn (PerceptionType $perceptionType): array => [
                'id' => $perceptionType->id,
                'nombre' => $perceptionType->nombre,
            ])
            ->all();
    }

    /**
     * @param  array<int, CatalogItem>  $catalog
     * @param  array<int, PerceptionCatalogItem>  $perceptionCatalog
     */
    private function prompt(array $catalog, array $perceptionCatalog): string
    {
        $catalogJson = collect($catalog)
            ->map(fn (array $p): string => "{$p['id']}: {$p['nombre']}"
                .($p['barcode'] ? " (código {$p['barcode']})" : '')
                .($p['categoria'] ? " [{$p['categoria']}]" : ''))
            ->implode("\n");

        $perceptionCatalogJson = collect($perceptionCatalog)
            ->map(fn (array $p): string => "{$p['id']}: {$p['nombre']}")
            ->implode("\n");

        return <<<PROMPT
            Sos un transcriptor de facturas de compra. Tu única tarea es transcribir
            fielmente lo que está escrito en la imagen/PDF adjunto, en JSON. NO hagas
            ninguna cuenta: no multipliques, no dividas, no sumes IVA, no conviertas
            unidades. Cada campo debe ser exactamente lo que ves impreso, solo
            reformateado al tipo pedido.

            Catálogo de productos existentes (id: nombre) — usalo únicamente para
            sugerir a qué producto corresponde cada línea, NUNCA inventes un id que no
            esté en esta lista:
            {$catalogJson}

            Catálogo de tipos de percepción existentes (id: nombre) — usalo únicamente
            para sugerir a qué tipo corresponde cada percepción detectada, NUNCA inventes
            un id que no esté en esta lista:
            {$perceptionCatalogJson}

            Reglas importantes:
            - Fechas: en Argentina se escriben DD/MM/AAAA (día primero). "02/06/2026" es
              el 2 de junio de 2026, NO el 6 de febrero. Devolvé fecha/vencimiento como
              "AAAA-MM-DD". Si no podés determinar la fecha con certeza, devolvé null.
            - Número de comprobante: suele tener formato PPPP-NNNNNNNN (punto de venta
              de 4 dígitos, guión, número de 8 dígitos). Leé el punto de venta con
              cuidado dígito por dígito — NO asumas "0001" por defecto. Si no podés
              leerlo con certeza, devolvé null en vez de adivinar.
            - Montos: devolvé cada monto como aparece impreso (como string, con los
              separadores originales de miles/decimales, ej. "13.891,40"), no lo
              conviertas vos.
            - Por cada línea, fijate que cantidad × precio_unitario sea aproximadamente
              igual al subtotal de esa línea — es una forma común de error confundir la
              columna "Total" con el precio unitario. Si no cierra, transcribí igual lo
              que está impreso (no ajustes números para que cierren).
            - matched_product_id: solo completalo si estás razonablemente seguro de que
              la línea corresponde a ese producto del catálogo. Si no hay match claro,
              null.
            - Percepciones: son todos los montos adicionales que el proveedor factura por
              separado del subtotal de productos, INCLUYENDO el IVA. No hay un campo
              aparte para el IVA: si la factura lo discrimina, agregalo como una
              percepción más (ej. "IVA 21%", "IVA 10,5%") igual que "Perc. IIBB Bs As" o
              "Percepción RG 2408". NO confundas una percepción con el descuento. Si la
              factura no tiene ningún monto adicional (ni siquiera IVA discriminado),
              devolvé "percepciones": [].
            - matched_perception_type_id: igual que matched_product_id, pero contra el
              catálogo de tipos de percepción. Si no hay match claro, null.

            Devolvé ÚNICAMENTE este JSON (sin texto adicional, sin fences de markdown):
            {
              "proveedor": string|null,
              "cuit": string|null,
              "tipo_comprobante": "factura_a"|"factura_b"|"factura_c"|"remito"|"otro"|null,
              "punto_venta": string|null,
              "numero": string|null,
              "fecha": string|null,
              "vencimiento": string|null,
              "subtotal": string|null,
              "total": string|null,
              "lineas": [
                {
                  "descripcion": string,
                  "cantidad": string,
                  "unidad": string,
                  "precio_unitario": string,
                  "subtotal": string,
                  "matched_product_id": number|null
                }
              ],
              "percepciones": [
                {
                  "descripcion": string,
                  "monto": string,
                  "matched_perception_type_id": number|null
                }
              ]
            }
            PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJson(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(json)?/i', '', $text);
        $text = preg_replace('/```$/', '', $text);

        $data = json_decode(trim($text), associative: true);

        if (! is_array($data)) {
            throw new RuntimeException('La respuesta de la IA no pudo interpretarse como JSON.');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $catalogIds
     * @param  array<int, int>  $perceptionCatalogIds
     * @return InvoiceExtraction
     */
    private function normalize(array $data, array $catalogIds, array $perceptionCatalogIds): array
    {
        $lines = collect($this->rawLines($data))
            ->map(function (array $line) use ($catalogIds): array {
                $descripcion = (string) ($line['descripcion'] ?? '');
                $cantidad = $this->parseDecimal($line['cantidad'] ?? null) ?? 0.0;
                $precioUnitario = $this->parseDecimal($line['precio_unitario'] ?? null) ?? 0.0;
                $subtotal = $this->parseDecimal($line['subtotal'] ?? null);
                $matchedId = $line['matched_product_id'] ?? null;

                return [
                    'descripcion' => $descripcion,
                    'description_key' => SupplierProductLink::normalizeDescription($descripcion),
                    'cantidad' => $cantidad,
                    'unidad' => $this->normalizeUnit($line['unidad'] ?? null),
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $subtotal ?? round($cantidad * $precioUnitario, 2),
                    'matched_product_id' => in_array($matchedId, $catalogIds, true) ? (int) $matchedId : null,
                    'consistente' => $subtotal === null || abs($subtotal - ($cantidad * $precioUnitario)) <= max(self::CONSISTENCY_TOLERANCE, $subtotal * 0.02),
                ];
            })
            ->values()
            ->all();

        $percepciones = collect($this->rawPercepciones($data))
            ->map(function (array $percepcion) use ($perceptionCatalogIds): array {
                $descripcion = (string) ($percepcion['descripcion'] ?? '');
                $matchedId = $percepcion['matched_perception_type_id'] ?? null;

                return [
                    'descripcion' => $descripcion,
                    'description_key' => SupplierProductLink::normalizeDescription($descripcion),
                    'monto' => $this->parseDecimal($percepcion['monto'] ?? null) ?? 0.0,
                    'matched_perception_type_id' => in_array($matchedId, $perceptionCatalogIds, true) ? (int) $matchedId : null,
                ];
            })
            ->values()
            ->all();

        return [
            'proveedor' => $data['proveedor'] ?? null,
            'cuit' => $data['cuit'] ?? null,
            'tipo_comprobante' => $data['tipo_comprobante'] ?? null,
            'punto_venta' => $data['punto_venta'] ?? null,
            'numero' => $this->composeNumero($data['punto_venta'] ?? null, $data['numero'] ?? null),
            'fecha' => $this->normalizeDate($data['fecha'] ?? null),
            'vencimiento' => $this->normalizeDate($data['vencimiento'] ?? null),
            'subtotal' => $this->parseDecimal($data['subtotal'] ?? null),
            'total' => $this->parseDecimal($data['total'] ?? null),
            'lineas' => $lines,
            'percepciones' => $percepciones,
        ];
    }

    /**
     * Las líneas tal cual vinieron en el JSON de la IA, descartando lo que no
     * sea un objeto (la respuesta no está garantizada).
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function rawLines(array $data): array
    {
        $lines = $data['lineas'] ?? null;

        if (! is_array($lines)) {
            return [];
        }

        return array_values(array_filter($lines, is_array(...)));
    }

    /**
     * Las percepciones tal cual vinieron en el JSON de la IA, descartando lo
     * que no sea un objeto. Si la clave "percepciones" no está presente
     * (respuesta vieja o factura sin percepciones), devuelve [].
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function rawPercepciones(array $data): array
    {
        $percepciones = $data['percepciones'] ?? null;

        if (! is_array($percepciones)) {
            return [];
        }

        return array_values(array_filter($percepciones, is_array(...)));
    }

    private function composeNumero(?string $puntoVenta, ?string $numero): ?string
    {
        if (blank($numero)) {
            return null;
        }

        if (blank($puntoVenta)) {
            return $numero;
        }

        return "{$puntoVenta}-{$numero}";
    }

    private function normalizeUnit(?string $unit): string
    {
        $key = mb_strtolower(trim((string) $unit));

        return self::UNIT_MAP[$key] ?? 'unidad';
    }

    private function normalizeDate(?string $date): ?string
    {
        if (blank($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        return $date;
    }

    /**
     * Convierte un monto tal como viene impreso (formato AR "13.891,40" o
     * US "1,148,785.38") a un float. Nunca hace ninguna operación aritmética
     * más allá de reinterpretar los separadores.
     */
    private function parseDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = preg_replace('/[^\d.,\-]/', '', trim((string) $value));

        if ($value === '' || $value === null) {
            return null;
        }

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $value = $lastComma > $lastDot
                ? str_replace(',', '.', str_replace('.', '', $value)) // AR: . miles, , decimal
                : str_replace(',', '', $value); // US: , miles, . decimal
        } elseif ($lastComma !== false) {
            $decimals = strlen($value) - $lastComma - 1;
            $value = $decimals === 3
                ? str_replace(',', '', $value)
                : str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
