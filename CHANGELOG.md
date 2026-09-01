# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/),
y este proyecto sigue [Semantic Versioning](https://semver.org/lang/es/) (ver
`.ai/decisions/0002-version-app.md`).

## [Unreleased]

## [0.8.0] - 2026-09-01

### Added

- Liquidación de honorarios de fichaje. Hasta ahora el reporte sumaba todos
  los ciclos cerrados para siempre: no había forma de asentar que un período
  ya se pagó, así que el "a cobrar" nunca volvía a cero. Ahora, desde
  Configuración → Reporte de fichajes, el botón "Liquidar honorarios" toma
  todo lo pendiente de un empleado hasta una fecha de corte, muestra el
  detalle (ciclos, horas, tarifa y total) antes de confirmar y deja asentado
  el pago con su fecha, medio de pago y referencia. Los fichajes liquidados
  dejan de contar como pendientes, así que el contador arranca de cero para
  el período siguiente.
- Confirmar una liquidación genera además el egreso en Finanzas: un gasto
  con categoría "Honorarios" (se crea sola la primera vez) a nombre del
  empleado, por el total liquidado y con el mismo medio de pago. El
  comprobante del gasto lleva el número de la liquidación (`LIQ-000001`)
  para poder cruzarlos a ojo.
- Nueva pantalla Configuración → Liquidaciones con el historial: empleado,
  período, horas, tarifa, total, fecha y medio de pago, y quién la hizo.
  Desde ahí se descarga el recibo de honorarios en PDF (con el detalle de
  los ciclos incluidos y espacio de firma) y se puede anular una
  liquidación: los fichajes vuelven a contar como pendientes y el gasto
  asociado se da de baja. La liquidación anulada no se borra, queda
  marcada como tal para poder auditar que el pago existió y se revirtió.
- El reporte de fichajes suma un filtro de estado —"Pendientes de liquidar"
  (por defecto), "Liquidados" o todos— y una columna que muestra a qué
  liquidación pertenece cada ciclo. El filtro también viaja al PDF.

### Changed

- La tarifa horaria de un fichaje ya liquidado queda congelada al momento
  del pago. Antes el "a cobrar" se calculaba siempre contra la
  `hourly_rate` vigente del usuario, así que cambiarle la tarifa reescribía
  retroactivamente todo su histórico. Ahora eso sólo afecta a los fichajes
  todavía pendientes: un recibo ya emitido no se mueve. Los fichajes
  pendientes siguen valorizándose con la tarifa vigente, como hasta ahora.

## [0.7.6] - 2026-08-31

### Changed

- En la tabla de Compras, el toggle "Ocultar anuladas" ahora viene encendido
  por defecto: el listado abre directamente sin las compras anuladas y hay
  que apagarlo para volver a verlas.
- En Nueva venta, en celular el grid de productos muestra solo las primeras
  6 tarjetas; el resto se despliega con un botón "Ver más" (y se vuelve a
  plegar con "Ver menos"), para que el carrito y el resumen queden a mano
  sin tener que scrollear todo el catálogo. En pantallas más grandes se
  siguen viendo todos los productos, como hasta ahora.

### Fixed

- Los widgets del dashboard "Ventas del mes" y "Producto más vendido"
  ignoraban las ventas del último día del mes: el rango de fechas se
  comparaba contra la fecha sin hora, y una venta del 31 guardada como
  "31 a las 00:00" quedaba fuera del filtro.

## [0.7.5] - 2026-08-29

### Added

- Catálogo de "Tipos de percepción" (Configuración) para las percepciones
  que cargan los proveedores en sus facturas (IIBB, IVA, RG 2408, etc.), que
  varían de comprobante en comprobante incluso para el mismo proveedor.
  Se cargan como líneas en Compras (alta manual y escaneo con IA) y entran
  en el total (`subtotal − descuento + percepciones`). El escaneo de
  facturas detecta percepciones contra ese catálogo igual que ya hace con
  productos, con memoria de vínculos confirmados por proveedor y alta
  rápida del tipo (precargada con el texto leído) si la IA no encuentra
  match.
- Confirmar una compra ahora genera de verdad la deuda con el proveedor
  (`Purchase.saldo`, `Supplier.balance`), y anularla la revierte por
  completo, incluidos pagos parciales ya imputados. Antes estos campos
  existían en el esquema pero ningún código los escribía. `Supplier.balance`
  pasa a ser de solo lectura (se calcula, ya no se edita a mano). Nuevo
  comando `php artisan app:recalculate-supplier-balances` para recalcular
  compras ya confirmadas.

### Changed

- El IVA de una factura de compra deja de ser un campo aparte: ahora se
  carga como una percepción más (ej. "IVA 21%"), tanto a mano como
  detectado por la IA al escanear. El histórico de `Purchase.iva` se migró
  a percepciones sin cambiar ningún total ya calculado. El campo
  "Descripción" de percepciones se sacó del alta manual (se sigue viendo,
  de solo lectura, en la revisión del escaneo con IA).
- Todas las columnas de la tabla de Proveedores, salvo la razón social,
  ahora son ocultables.
- Los montos que todavía mostraban "ARS" en vez de "$" (Proveedores,
  Clientes, Pagos, imputaciones de pago, Movimientos de stock y Gastos)
  quedan con el mismo formato que ya usaban Ventas, Compras y Productos.
- En la tabla de Compras, el filtro "Trashed" (que hablaba de registros
  "borrados", pero en realidad filtraba por soft-delete, un mecanismo
  distinto de anular una compra) se reemplaza por un toggle "Ocultar
  anuladas" que filtra por `status != 'anulada'`.

### Fixed

- En la tabla de líneas del escaneo de facturas, al subtotal de cada ítem
  le faltaba la celda visible (quedaba como campo oculto), lo que corría
  una columna al botón de borrar. Se agregó el subtotal visible y se bajó
  la tipografía de cantidad/costo/subtotal/percepciones para que la fila
  quede más distribuida.
- Al editar a mano el descuento o el monto de una percepción en una compra
  ya guardada, el valor se multiplicaba por ~100 en cada apertura y
  guardado (ej. $18.060 terminaba en $1.806, y de nuevo en $180.600 si se
  volvía a guardar sin corregir). El mask de dinero del campo esperaba
  coma como separador decimal, pero el valor crudo que viene de la base
  usa punto — el mask no lo reconocía y reinterpretaba el número entero
  como si fueran miles. Afectaba también, con el mismo riesgo, al
  descuento.

## [0.7.3] - 2026-08-29

### Added

- Columna "Stock" en la tabla de Productos: muestra el total actual sumado
  entre depósitos (con tooltip de desglose por depósito) y resalta en rojo
  los productos en o por debajo de su stock mínimo. Se agrega una acción
  rápida "Ajustar stock" en cada fila para cargar una corrección sin salir
  de la pantalla.
- Las columnas que se ocultan/muestran en las tablas (Productos, Compras,
  Ventas, Listas de precio, Depósitos, Clientes, Proveedores, Gastos, Pagos,
  Usuarios y Actividad) ahora se guardan por usuario y persisten después de
  cerrar sesión, en vez de perderse en cada logout.
- El dashboard ahora se puede personalizar por rol: desde Configuración →
  Roles → pestaña "Widgets" se elige qué cards ve cada perfil (antes solo el
  widget de fichaje respetaba esto; el resto se mostraba a cualquiera con
  acceso al panel).

### Changed

- El recurso "Movimientos de stock" se muda del cluster Compras al cluster
  Catálogo, junto a Productos y Depósitos, ya que no es exclusivo de
  compras.

## [0.7.2] - 2026-08-28

### Added

- Botón "Compartir lista de precios" en la vista de Productos, junto al
  buscador de la tabla: al elegir una lista (Minorista, Mayorista o VIP) abre
  en una pestaña nueva un PDF con el encabezado de la empresa, código de
  barra, nombre, presentación y precio de cada producto activo en esa lista.
- La misma acción de compartir lista de precios se agrega en dos lugares más:
  la card "Compartir lista" del dashboard (reemplaza a "Nuevo cliente") y un
  botón en la navbar mobile, al lado del botón de home.
- Switch "Se puede compartir" en cada lista de precios: si está apagado, la
  lista no aparece en ninguno de los botones de compartir y su PDF devuelve
  404. Las listas existentes y las nuevas quedan compartibles por defecto.
- **Fichaje por hora**: nuevo rol "administrativo" con un botón en el
  dashboard para marcar inicio/fin de jornada (visible solo para ese rol).
  Cada usuario administrativo tiene una tarifa horaria seteable desde
  Configuración → Usuarios. El dueño (roles `admin`/`Dueño`) gestiona los
  ciclos manualmente desde Configuración → Fichajes y accede a un informe
  filtrable por empleado y período (Configuración → Reporte de fichajes),
  con horas y monto a cobrar totalizados por empleado y descarga en PDF.

### Changed

- El widget "Nuevo cliente" del dashboard se reemplaza por "Compartir lista".
- Más separación entre los botones de la navbar mobile (menú, home,
  compartir lista), que quedaban pegados por el margen negativo por defecto
  de los `icon-button` de Filament.
- El rol "Dueño" (hasta ahora creado a mano en producción, sin rastro en el
  código) queda versionado en `ShieldSeeder`, con los mismos permisos de
  fichajes que tiene `admin` (gestión manual de ciclos + informe), sin el
  botón de fichar.

## [0.7.1] - 2026-08-26

### Changed

- El widget "Nuevo proveedor" del dashboard se reemplaza por "Productos",
  que enlaza al listado de productos.
- La columna SKU de la tabla de Productos ahora es ocultable.
- Los precios de la tabla de Productos (Costo y cada lista de precios) se
  muestran con denominación "$" en vez de "ARS".
- Las columnas de listas de precios en la tabla de Productos se ordenan por
  Minorista, Mayorista y VIP, en vez de alfabéticamente.

## [0.7.0] - 2026-08-26

### Added

- Grid de productos clicables en Crear Venta: tapear una card abre un modal
  para ingresar la cantidad y agrega (o suma, si el producto ya estaba en el
  carrito) una línea de venta, sin pasar por el buscador de producto.
- El buscador global de la navbar muestra, para productos, el precio de la
  lista de precios predeterminada junto al nombre (ej. "Aceite de Girasol
  1.5L — $2.430,00").

### Changed

- **Crear venta vuelve a ser una página propia** (antes se abría como modal
  desde el listado, al no tener página `create` registrada). El widget
  "Nueva venta" del dashboard, que todavía apuntaba al patrón de modal viejo,
  ahora enlaza a la página nueva.
- Fecha, Lista de precios, Depósito, Usuario y Observaciones quedan en un
  acordeón colapsado por defecto debajo de Cliente (siguen siendo editables)
  para que la carga rápida de una venta solo pida el cliente a simple vista.
- La tabla de líneas de venta muestra sus 5 columnas completas (Código,
  Producto, Cantidad, Precio, Subtotal) en pantallas grandes; en mobile se
  aplana a una tarjeta compacta con "Producto ×cantidad" + Subtotal.
- Se sacó el descuento por línea y el escaneo de código de barras de la
  carga de líneas de Ventas (el escaneo sigue disponible en Compras).

## [0.6.1] - 2026-08-23

### Changed

- `composer types:check` (PHPStan nivel 7) pasa en verde: se resolvieron los
  152 errores que el proyecto arrastraba y que venían haciendo fallar el CI
  en **todos** los PRs desde el primero. El chequeo corre antes de los tests,
  así que hasta ahora los tests del CI ni siquiera llegaban a ejecutarse.
- Los 18 modelos declaran los genéricos de sus relaciones y de `HasFactory`
  (`@return BelongsTo<Customer, $this>`, `@use HasFactory<SaleFactory>`, …).
  Es documentación de tipos: no cambia el comportamiento, pero hace que el
  análisis estático entienda `$sale->customer->razon_social` y compañía.
- `InvoiceExtractor` declara la forma exacta de la extracción
  (`@phpstan-type`) y devuelve el catálogo como array plano en vez de
  `Collection`, así el tipo sobrevive al pasar de un método a otro.
- `ShieldSeeder` quedó reducido a lo que esta app usa (roles y permisos): el
  archivo generado por `shield:generate --seeder` traía además ramas de
  tenants, usuarios y pivot que acá nunca se ejecutan. El JSON de permisos es
  el mismo, y el resultado de `db:seed` es idéntico (164 permisos; admin 164,
  vendedor/deposito/chofer 128 cada uno).

### Fixed

- Escaneo de factura: si el archivo subido no se puede guardar en disco, ahora
  avisa con una notificación en vez de romper con un error de tipo al intentar
  prepararlo.
- `InvoiceExtractor` falla con un mensaje claro si no puede leer el archivo de
  la factura, en vez de mandar una imagen vacía a la API.

## [0.6.0] - 2026-08-23

### Added

- **Los ingresos al sistema quedan registrados**: cada inicio de sesión,
  cierre de sesión, intento fallido, bloqueo por demasiados intentos y
  restablecimiento de contraseña se guarda en el Registro de actividades
  (Configuración → Actividad), con usuario, fecha, IP y navegador. Aplica
  tanto al login con email/contraseña como al de Google, incluidos los
  intentos rechazados (email desconocido o usuario inactivo), que anotan el
  motivo del rechazo. Las contraseñas nunca se guardan en el registro.
- Auditoría de los modelos que todavía no la tenían: líneas de venta,
  líneas de compra, imputaciones de pago, categorías de productos,
  categorías de gastos y vínculos proveedor-producto. Con esto, cualquier
  alta, edición o baja de datos del sistema queda registrada.
- Filtros en el Registro de actividades: por tipo (cambios de datos /
  ingresos al sistema), evento, usuario, modelo y rango de fechas. La
  descripción ahora es buscable y la IP se puede mostrar como columna
  opcional.

### Changed

- Los eventos del Registro de actividades se muestran en castellano
  ("Creación", "Edición", "Ingreso", "Ingreso fallido", …) en vez del nombre
  interno en inglés, con colores por tipo de evento.

## [0.5.5] - 2026-08-23

### Added

- Nuevas cards de acceso rápido en el dashboard, "Nuevo cliente" y "Nuevo
  proveedor", junto a las ya existentes "Nueva compra"/"Nueva venta" (ahora
  las 4 en una sola fila en desktop).
- Nuevos widgets de análisis en el dashboard: **Ventas del mes** (total $ de
  ventas confirmadas del mes actual), **Producto más vendido** (por
  cantidad, ventas confirmadas del mes actual) y **Total de productos
  activos**.
- Botón para volver al dashboard en la barra superior, visible solo en
  mobile (al lado del menú hamburguesa), ya que en ese tamaño de pantalla
  el logo/nombre de la empresa (que también linkea al dashboard) queda
  oculto.

### Changed

- En mobile, las 4 cards de acceso rápido del dashboard se acomodan de a 2
  por fila; las cards de análisis siguen ocupando el ancho completo.
- Las cards de acceso rápido del dashboard ya no muestran una aclaración
  debajo del título (ej. "Cargar una compra nueva"), solo el título.
- La card "Producto más vendido" ocupa 2 columnas de ancho para no cortar
  nombres de producto largos.
- Los clientes y proveedores nuevos quedan **activos** por defecto. El
  campo `activo` de esos formularios no tenía un valor por defecto (a
  diferencia del de Productos, que sí), así que el toggle arrancaba
  apagado y había que activarlo a mano después de cada alta.

## [0.5.4] - 2026-08-23

### Fixed

- La búsqueda global del panel (barra superior, al lado del ícono de
  usuario) no devolvía resultados de ningún resource: Filament requiere que
  cada Resource declare qué atributos son buscables globalmente, y ninguno
  lo hacía. Se agregó esa configuración a Productos, Listas de precios,
  Depósitos, Clientes, Proveedores, Ventas, Compras, Gastos, Pagos y
  Usuarios (por nombre/código/CUIT/email/número de comprobante, según el
  resource, incluyendo campos de relación como cliente o proveedor).
  Registro de actividades y Movimientos de stock quedan fuera a propósito
  (no tienen un título de una sola columna).

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
