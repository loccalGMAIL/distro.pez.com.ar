# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto sigue [Semantic Versioning](https://semver.org/lang/es/) (ver
`.ai/decisions/0002-version-app.md`).

## [Unreleased]

## [0.5.3] - 2026-08-22

### Added

- Nuevo sector **Usuarios** en Configuración: alta y edición de usuarios
  (nombre, email, contraseña, activo) con asignación de uno o más roles.
- Nuevo sector **Roles** en Configuración (aportado por `filament-shield`):
  matriz de permisos editable por rol, con una fila por cada resource,
  página y widget del panel.
- Nuevo sector **Registro de actividades** en Configuración: auditoría de
  alta/edición/baja de los modelos de negocio (Productos, Compras, Ventas,
  Clientes, Proveedores, Pagos, Gastos, Movimientos de stock, Usuarios,
  etc.), con vista de detalle mostrando qué cambió en cada evento.

### Changed

- El campo fijo `users.role` (enum `admin/vendedor/deposito/chofer` sin
  ninguna autorización real detrás) se reemplazó por roles y permisos de
  verdad vía `spatie/laravel-permission` + `filament-shield`. Los roles
  legados conservan el mismo acceso que ya tenían (antes no existía ninguna
  Policy en la app) y ahora se pueden restringir por resource desde
  Configuración → Roles.

## [0.5.2] - 2026-08-22

### Added

- Alta rápida de proveedor y de producto desde el escaneo de facturas de
  compra (`ScanPurchase`): si la IA no encuentra coincidencia para el
  proveedor o para una línea, se sugiere crearlo ahí mismo con un modal
  precargado con lo que se leyó de la factura (razón social/CUIT del
  proveedor; nombre, unidad y costo del producto), sin salir del escaneo.
- El card "Nueva compra" del dashboard ahora lleva directo al escaneo de
  facturas (`ScanPurchase`) en vez de abrir el modal de alta manual.

### Changed

- El nombre que se muestra arriba a la izquierda del sidebar ahora sale de
  la razón social cargada en Configuración → General (`CompanySetting`),
  en vez del `APP_NAME` fijo del `.env`. Si todavía no hay razón social
  cargada, sigue mostrando el nombre de la app como antes. El texto admite
  hasta dos líneas sin romper el alto del header (`brandLogoHeight` pasó a
  `auto`) para nombres largos.
- Tamaño de letra del nombre del comercio y del número de versión en el
  sidebar, subido un 10% (14px→15.4px y 10px→11px).

### Fixed

- Reemplazado el favicon por defecto de Laravel por un ícono propio: una
  "d" minúscula en naranja (Lato Bold) dentro de un círculo negro, aplicado
  a `favicon.ico`, `favicon.svg` y `apple-touch-icon.png`.
- La tabla de líneas del modal de edición de una compra (`PurchaseForm`)
  partía el nombre del producto letra por letra en pantallas grandes: la
  tabla vivía dentro de la sección compartida con "Resumen" (2/3 del
  ancho) y la columna "Producto" era la única sin ancho fijo, así que
  absorbía todo el faltante y quedaba demasiado angosta. Ahora la tabla de
  líneas ocupa todo el ancho de la página.

## [0.5.1] - 2026-08-22

### Added

- Alta rápida de cliente desde el modal de creación de venta (`SaleForm`): el
  Select de cliente permite crear uno nuevo en un modal sin salir de la venta
  (`createOptionForm`), con el mínimo de campos que la tabla `customers`
  exige sin default (razón social y lista de precios) — el resto se completa
  después desde Clientes.
- Filtro por cliente en la tabla de Ventas.
- Número de versión de la app debajo del nombre del comercio (logo del
  panel), en tipografía chica y gris para no competir con el nombre.

## [0.5.0] - 2026-08-20

### Added

- Módulo de Compras llevado al mismo nivel que Ventas: alta por modal (líneas
  como repeater estilo factura, con buscador de producto por nombre/código de
  barras y escaneo con cámara), estado `borrador` editable, y acciones
  "Confirmar"/"Anular" en `Purchase` que generan `stock_movements` (tipo
  `compra`/`devolucion_prov`) y actualizan `Product.costo_ultimo` con el
  último precio pagado.
- Filtro por proveedor en la tabla de Compras.
- Escaneo de facturas de compra con IA (Claude vision, `claude-haiku-4-5` por
  default vía `ANTHROPIC_MODEL`): página nueva "Escanear factura" con un
  flujo de 2 pasos (Capturar → Revisar). La IA transcribe encabezado y líneas
  sin hacer ninguna cuenta propia (ni aritmética, ni conversión de unidades,
  ni adivina fechas/números de comprobante inciertos); un humano revisa y
  corrige antes de crear la compra en `borrador`. Las correcciones de
  producto se recuerdan por proveedor (`supplier_product_links`), así que el
  próximo escaneo de ese proveedor ya viene vinculado.
- Card "Nueva compra" en el dashboard, con su propio color/ícono para
  diferenciarse de "Nueva venta" a simple vista.

### Changed

- El archivo adjunto de una compra (`archivo_path`, ya sea cargado a mano o
  por el escaneo con IA) pasa del disco público al privado, servido por una
  ruta autenticada nueva (`purchases.archivo`) — las facturas tienen CUIT y
  precios de proveedores.

### Fixed

- Las rutas fuera del panel de Filament con `middleware('auth')` (comprobante
  de venta, archivo de compra) rompían con un 500 para un usuario no
  logueado en vez de redirigir al login, porque el middleware genérico
  buscaba una ruta con nombre `login` que no existe (Filament registra la
  suya con otro nombre por panel).

## [0.4.0] - 2026-08-20

### Added

- Configuración del comercio (nuevo cluster "Configuración" → página
  "General"): datos de facturación (razón social, CUIT, condición IVA,
  domicilio fiscal, teléfono, email, punto de venta) y logotipo, en un
  modelo `CompanySetting` de fila única.
- Comprobante en PDF al confirmar una venta, armado con los datos del
  comercio, del cliente y las líneas de la venta (incluye código de barras
  del producto). Se genera al vuelo en cada visita (no se guarda en el
  servidor) vía `barryvdh/laravel-dompdf`; la numeración
  (`{punto_venta}-{numero}`) sale siempre del correlativo real de la venta,
  sin contador aparte. Accesible desde una notificación "Ver comprobante"
  al confirmar/finalizar, o desde el botón "Comprobante" de la tabla de
  ventas.

### Changed

- La columna "numero" de la tabla de ventas ahora es ordenable.

## [0.3.0] - 2026-08-20

### Added

- Login con Google (Laravel Socialite) en el panel de Filament: botón
  "Continuar con Google" adicional al login por contraseña existente. Sin
  auto-registro: solo pueden loguearse usuarios ya existentes y `activo`;
  el resto es rechazado con una notificación.
- Escaneo de código de barras con la cámara del dispositivo en el buscador de
  producto de la línea de venta (`SaleForm`). Visible solo en mobile; usa la
  API nativa `BarcodeDetector` del navegador, sin agregar dependencias JS.
- La sección "Resumen" del modal de venta ahora es un slide-over en mobile
  (oculto por defecto, se abre con un botón flotante), manteniendo el layout
  de sidebar sin cambios en desktop.
- Bloqueo de la creación de una venta cuando el total es $0 (notificación de
  error en vez de dejar pasar una venta vacía o descontada a cero).

### Changed

- El botón para confirmar la venta en el modal pasó de decir "Crear" a
  "Finalizar".

### Fixed

- Migraciones duplicadas de `mcp_tokens` y `mcp_uploads` (publicadas además de
  las que ya carga automáticamente `guava/filament-mcp`), que rompían toda la
  suite de tests basada en `RefreshDatabase`.
- Mensajes de validación de formularios mostraban la clave cruda sin traducir
  (ej. `"validation.required"`) en vez de un mensaje en español, por falta de
  `lang/es/validation.php`.

## [0.1.0] - 2026-08-20

### Added

- Número de versión de la aplicación (`config('app.version')`, `APP_VERSION`)
  siguiendo SemVer.
- Bitácora de decisiones del proyecto en `.ai/decisions/`.

### Changed

- Reemplazo de la página `Dashboard` por defecto de Filament por un widget de
  acceso rápido a "Nueva venta".
