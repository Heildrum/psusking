# Elixir - Perfumería

Sitio web de comercio electrónico para una perfumería, con catálogo público, carrito de compras, flujo de pago por transferencia bancaria manual y panel de administración con autenticación de dos factores (2FA).

---

##  ¿Qué hace?

### Público (`/Elixir/`)
- **Galería de productos** con imágenes, precios y stock
- **Carrito de compras** (agregar, quitar, vaciar) almacenado en sesión PHP
- **Checkout** formulario con datos del cliente (nombre, correo)
- **Pantalla de pago** que muestra los datos de la cuenta bancaria del negocio para que el cliente transfiera
- **Botón "Ya transferí"** que cambia el estado del pedido a `Pendiente` para que el admin lo revise
- Diseño responsive (adaptado a celulares)

### Administración (`/Elixir/privado/`)
- **Login en 2 pasos:**
  1. Usuario y contraseña
  2. Código 6 dígitos de Google Authenticator (2FA)
- **Dashboard** con estadísticas (total productos, pedidos, stock)
- **CRUD de productos** (crear, editar, eliminar con imagen)
- **Gestión de pedidos** (ver pedidos entrantes, aprobar pago manualmente)
- **Importación masiva** de productos desde archivos Excel (.xlsx/.xls) o CSV
- **Configuración de cuenta bancaria** (datos que ve el cliente al pagar)
- **Configuración de Mercado Pago** (credenciales y visor de pagos)
- **Cierre de sesión**

### Seguridad
- Contraseñas hasheadas con `password_hash()` (bcrypt)
- Autenticación 2FA con Google Authenticator (código TOTP de 6 dígitos)
- QR generado localmente con `chillerlan/php-qrcode` (sin API externa)
- Tokens CSRF en todos los formularios del admin y checkout
- Protección de fuerza bruta (5 intentos por IP cada 15 minutos) en login y 2FA
- Regeneración de ID de sesión en cada paso exitoso de autenticación
- Credenciales de base de datos almacenadas en archivo `.env` fuera de la raíz web
- Panel admin sin enlace público (solo accesible por URL directa)

---

## ❌ ¿Qué NO hace?

- **No tiene pasarela de pago automática** — el pago es manual por transferencia bancaria; el admin aprueba desde el panel
- **No envía correos electrónicos** — ni confirmación de pedido, ni notificaciones al admin
- **No tiene registro de clientes** — el cliente compra como invitado, solo ingresa nombre y correo
- **No tiene historial de pedidos para el cliente** — una vez cerrada la sesión no puede consultar sus órdenes anteriores
- **No tiene seguimiento de envíos** — ni tracking, ni cálculo de costos de despacho
- **No calcula impuestos** — IVA u otros impuestos no se aplican automáticamente
- **No tiene búsqueda de productos** — ni buscador por texto ni filtros
- **No tiene categorías ni etiquetas** — los productos se muestran todos juntos
- **No tiene multidioma** — solo español
- **No tiene cupones ni descuentos**
- **No tiene alertas de stock bajo**
- **No tiene Webpay+ / Transbank integrado** — el archivo `pagar.php` y `confirmar_pago.php` son restos de una implementación anterior (Transbank) que no está conectada al flujo actual
- **No tiene panel de clientes** — cada cliente no puede ver su historial ni estado de pedido

---

##  Requisitos

- PHP 8.0+
- MySQL 5.7+ / MariaDB
- Composer
- Extensiones PHP: `pdo_mysql`, `gd`, `mbstring`, `curl`, `zip` (para PhpSpreadsheet)

---

## ⚙️ Instalación

1. Clonar el repositorio dentro del web root:
   ```
   git clone https://github.com/tu-usuario/elixir.git public_html/Elixir
   ```

2. Instalar dependencias de Composer:
   ```
   cd public_html/Elixir
   composer install --no-dev
   ```

3. Crear el archivo `.env` **fuera** del web root (ej: `public_html/../Elixir.env`):
   ```
   DB_HOST=localhost
   DB_NAME=tienda_perfumes
   DB_USER=tu_usuario
   DB_PASS=tu_contraseña
   DB_CHARSET=utf8mb4
   ```

4. Importar esquema de base de datos:
   ```
   mysql -u tu_usuario -p tienda_perfumes < tablas.sql
   ```

5. Crear carpeta de imágenes con permisos de escritura:
   ```
   mkdir privado/imagenes
   chmod 755 privado/imagenes
   ```

6. Visitar una sola vez `/Elixir/privado/registrar_admin.php` para crear el admin, escanear el QR con Google Authenticator y **no volver a visitar esa página** (regenera el secreto 2FA cada vez).

7. Acceder al panel: `/Elixir/privado/ingreso_secreto_dueno.php`
   - Usuario: `admin`
   - Contraseña: `Psusking2024`

---

## 📁 Estructura del proyecto

```
Elixir/
├── Elixir.php              # Entry point (incluye index.php)
├── index.php               # Galería pública + carrito
├── conexion.php            # Conexión PDO a MySQL (lee desde .env)
├── csrf_helper.php         # Generación y validación de tokens CSRF
├── carrito.php             # Lógica del carrito (agregar/quitar/vaciar)
├── checkout.php            # Formulario de compra
├── pago.php                # Muestra datos bancarios, botón "Ya transferí"
├── pagar.php               # (Obsoleto) Integración Transbank Webpay+
├── confirmar_pago.php      # (Obsoleto) Retorno de Transbank
├── tablas.sql              # Esquema completo de la base de datos
├── .env.example            # Plantilla para el archivo de configuración
├── composer.json
├── vendor/                 # Dependencias (PhpSpreadsheet, php-qrcode)
├── privado/
│   ├── ingreso_secreto_dueno.php   # Login paso 1 (usuario/contraseña)
│   ├── verificar_telefono.php      # Login paso 2 (código 2FA)
│   ├── panel_control.php           # Panel de administración
│   ├── formulario_creacion.php     # Formulario de producto (crear/editar)
│   ├── procesar_producto.php       # Guarda producto en BD
│   ├── eliminar_producto.php       # Elimina producto y su imagen
│   ├── importar_productos.php      # Importación Excel/CSV
│   ├── mercadolibre_config.php     # Configuración Mercado Pago
│   ├── registrar_admin.php         # Crea/admin actualiza admin (usar una sola vez)
│   ├── cerrar_sesion.php           # Cierra sesión
│   ├── GoogleAuthenticator.php     # Clase PHPGangsta GoogleAuthenticator
│   └── imagenes/                   # Imágenes de productos
```

---

## 🗄️ Base de datos

- `administradores` — usuarios admin con hash de contraseña y secreto 2FA
- `productos` — catálogo de perfumes (nombre, descripción, precio, imagen, stock)
- `pedidos` — órdenes de compra (cliente, total, estado: `Por_Pagar` → `Pendiente` → `Aprobado`)
- `detalle_pedidos` — desglose de productos por pedido
- `cuenta_bancaria` — datos de la cuenta para transferencias (una fila, id=1)
- `Pasarela` — credenciales de Mercado Pago (opcional)
- `payments` — registro de pagos por pasarela (opcional)
- `login_attempts` — registro de intentos de login para bloqueo por IP

---

## 🔄 Flujo de compra

1. Cliente navega la galería, agrega productos al carrito
2. Completa formulario en `/checkout.php` con nombre y correo
3. Se crea el pedido con estado `Por_Pagar` y se redirige a `/pago.php?pedido_id=N`
4. Cliente ve los datos bancarios del negocio, hace la transferencia
5. Cliente hace clic en **"Ya transferí"** → el pedido pasa a `Pendiente`
6. Admin ingresa al panel, sección Pedidos, hace clic en **"Aprobar"** → el pedido pasa a `Aprobado`

---

## 🧪 Credenciales por defecto (desarrollador)

- URL admin: `/Elixir/privado/ingreso_secreto_dueno.php`
- Usuario: `admin`
- Contraseña: `Psusking2024`
- 2FA: escanear QR en `/Elixir/privado/registrar_admin.php` con Google Authenticator

> **⚠️ Importante:** `registrar_admin.php` regenera el secreto 2FA en cada carga. Visitarlo nuevamente desincronizará el código de tu celular.

---

## 📝 Notas

- El proyecto físicamente está en `C:\xampp\htdocs\Psusking` y se accede mediante un alias de Apache `/Elixir` que apunta a esa carpeta
- En producción (Hostinger): subir a `public_html/Elixir/` y crear `Elixir.env` fuera de `public_html`
- Los archivos `pagar.php` y `confirmar_pago.php` son código legacy de Transbank Webpay+ que no forma parte del flujo actual
- La tabla `Pasarela` y `payments` están preparadas para una futura integración con Mercado Pago pero actualmente no se usan en el flujo de compra
