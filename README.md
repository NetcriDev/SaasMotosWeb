# SaasMotosWeb

Plataforma SaaS para la gestión de talleres de motocicletas. Cada taller opera como un **tenant** independiente (`Team`) y administra sucursales, clientes, motos, catálogos y órdenes de trabajo desde un panel web en `/admin`.

## Características

- **Multi-tenant** con Filament Tenancy: registro de talleres, cambio de contexto y datos aislados por equipo.
- **Permisos por taller** con [Filament Shield](https://github.com/bezhanSalleh/filament-shield) (Spatie Permission + `team_id`).
- **Invitaciones por correo** con asignación de rol al aceptar.
- **Dashboard operativo** con KPIs y gráfico de órdenes por estado (Fase 1).
- **Listado de órdenes** con pestañas por estado y filtros colapsables (Fase 1).
- **Moneda configurable** para totales estimados, tipos de mantenimiento y widgets.
- **Sucursales, clientes, motos, catálogos y órdenes de trabajo** con estados, fechas y validaciones de negocio.
- **Historial relacionado** en fichas de cliente y moto (órdenes asociadas).
- **Datos de demostración** listos para probar el flujo completo.

## Stack tecnológico

| Capa | Tecnología |
|------|------------|
| Backend | PHP 8.3, Laravel 13 |
| Panel admin | Filament 5 |
| Permisos | Filament Shield 4, Spatie Laravel Permission |
| Frontend assets | Vite 8, Tailwind CSS 4 |
| Base de datos | SQLite por defecto (configurable a MySQL/PostgreSQL) |
| Tests | Pest 4 |

## Requisitos

- PHP 8.3+
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) (LTS recomendado) y npm
- Extensión PHP `sqlite3` (o el driver de tu base de datos elegida)

## Instalación

```bash
# Clonar e instalar dependencias
composer install
cp .env.example .env
php artisan key:generate

# Base de datos (SQLite por defecto)
touch database/database.sqlite
php artisan migrate

# Permisos del panel (primera vez o tras nuevos recursos Filament)
php artisan shield:generate --all --panel=admin --relationships

# Assets frontend
npm install
npm run build

# Datos de demostración (opcional)
php artisan db:seed
php artisan permission:cache-reset
```

También puedes usar el script de Composer que automatiza el setup inicial:

```bash
composer setup
```

> El `DatabaseSeeder` ejecuta `shield:generate` automáticamente si la tabla de permisos está vacía, y luego carga el taller demo con `super_admin` y roles invitables.

## Desarrollo

Arranca servidor Laravel, cola y Vite en paralelo:

```bash
composer dev
```

Comandos útiles:

| Comando | Descripción |
|---------|-------------|
| `php artisan serve` | Servidor de desarrollo |
| `npm run dev` | Vite en modo desarrollo |
| `php artisan migrate` | Ejecutar migraciones |
| `php artisan db:seed` | Cargar datos demo |
| `php artisan shield:generate --all --panel=admin --relationships` | Regenerar permisos y políticas |
| `php artisan team:assign-owner {email} --team="Nombre taller"` | Asignar `super_admin` a un usuario en un taller |
| `php artisan permission:cache-reset` | Limpiar caché de permisos |
| `composer test` | Ejecutar tests con Pest |

## Acceso al panel

- **URL del panel:** [http://localhost:8000/admin](http://localhost:8000/admin)
- **Registro:** habilitado para nuevos usuarios y talleres.
- **Página pública:** la ruta `/` muestra la vista de bienvenida de Laravel.

### Credenciales demo

Tras ejecutar `php artisan db:seed`:

| Campo | Valor |
|-------|-------|
| Email | `admin@taller.demo` |
| Contraseña | `password` |
| Taller | Taller Demo Motos |

El seeder crea dos sucursales, clientes, motos, catálogos (marcas, modelos, tipos de mantenimiento con precios), órdenes de trabajo de ejemplo y el usuario demo como **`super_admin`** del taller.

---

## Fase 1 — Dashboard y listado de órdenes

Funcionalidad orientada a la operación diaria del taller dentro del tenant activo.

### Dashboard (`/admin/{taller}`)

Widgets visibles solo con un taller seleccionado:

| Widget | Contenido |
|--------|-----------|
| **Órdenes de trabajo** (`WorkOrderStatsOverview`) | Ingresos del mes (entregadas), valor en taller (pipeline activo), activas, ingresadas hoy, en taller, listas para entrega. Enlaces a pestañas del listado. |
| **Órdenes por estado** (`WorkOrdersByStatusChart`) | Gráfico tipo doughnut con el reparto por estado. |

Los importes usan `App\Support\Money` y la configuración de moneda en `config/workshop.php`.

### Listado de órdenes

Ruta: **Órdenes de trabajo** en el menú del panel.

**Pestañas** (por defecto **Activas**):

| Pestaña | Criterio |
|---------|----------|
| Activas | Recibido, en taller o listo |
| Recibido / En taller / Listo / Entregado | Un estado cada una |
| Todas | Sin filtro de estado |

Cada pestaña muestra un **badge** con el conteo actual del taller.

**Filtros** (bloque colapsable encima de la tabla, persistidos en sesión):

- Sucursal
- Estado
- Rango de **fecha de ingreso** (desde / hasta), con indicadores activos

**Columnas destacadas:** cliente y moto con enlace a su ficha, total estimado formateado, estado con badge de color, fechas de ingreso y finalización.

Archivos principales:

```
app/Filament/Widgets/WorkOrderStatsOverview.php
app/Filament/Widgets/WorkOrdersByStatusChart.php
app/Filament/Resources/WorkOrders/Pages/ListWorkOrders.php
app/Filament/Resources/WorkOrders/Tables/WorkOrdersTable.php
app/Support/TenantWorkOrderQuery.php
app/Enums/WorkOrderStatus.php
```

---

## Roles y permisos (Filament Shield)

La gestión de roles está en el menú **Filament Shield → Roles** (`/admin/{slug-taller}/shield/roles`).

### Modelo multi-tenant

- Cada **taller** (`Team`) tiene sus propios roles y asignaciones (`team_id` en tablas de Spatie).
- Los **permisos** son globales (generados por Shield); los **roles** son por taller.
- El middleware `SyncShieldTenant` mantiene el contexto del tenant al evaluar permisos.

### Roles del sistema

| Rol | Uso |
|-----|-----|
| `super_admin` | Propietario / administrador total del taller. Se asigna al **registrar un taller**, en el **seeder demo** o con `team:assign-owner`. Tiene todos los permisos del panel. |
| `admin`, `recepcion`, `mecanico` | Roles invitables (creados vacíos en el demo). Debes asignar permisos en Shield según tu operación. |
| `panel_user` | Rol básico de Shield (si lo habilitas para usuarios sin permisos específicos). |

### Permiso custom

| Permiso | Descripción |
|---------|-------------|
| `ManageTeamInvitations` | Acceso a **Equipo → Invitaciones** para crear enlaces de invitación. |

### Comandos y mantenimiento

```bash
# Tras añadir recursos, páginas o widgets en Filament
php artisan shield:generate --all --panel=admin --relationships

# Super admin en un taller existente (usar ID de usuario, no email)
php artisan shield:super-admin --user=1 --panel=admin --tenant=1

# Alternativa por correo (comando del proyecto)
php artisan team:assign-owner admin@taller.demo --team="Taller Demo Motos"
```

Configuración: `config/filament-shield.php` (`tenant_model`, `custom_permissions`, pestaña de permisos custom).

---

## Invitaciones al equipo

Flujo para sumar usuarios sin compartir contraseñas.

### Panel

- Menú: **Equipo → Invitaciones** (`/admin/{taller}/invitaciones`).
- Requiere permiso `ManageTeamInvitations` (o rol `super_admin`).
- Formulario: correo + rol (`admin`, `recepcion`, `mecanico`). No se puede invitar como `owner`.
- Se genera un enlace con token (válido 7 días); puedes copiarlo desde la tabla.

### Aceptación

| Ruta | Acción |
|------|--------|
| `GET /invitacion/{token}` | Vista pública: muestra el taller y pide iniciar sesión o registrarse. |
| `POST /invitacion/{token}` | Acepta la invitación con el usuario autenticado (correo debe coincidir). |

Al aceptar:

1. El usuario se asocia al taller (`team_user`).
2. Se asigna el rol Shield indicado en la invitación (debe **existir** en ese taller).
3. Si el usuario inicia sesión o se registra con una invitación pendiente en sesión, `AcceptPendingTeamInvitation` completa el proceso automáticamente.

### Requisitos

1. Crear en Shield los roles `admin`, `recepcion` y/o `mecanico` (el demo los crea sin permisos).
2. Asignar permisos a esos roles.
3. Otorgar `ManageTeamInvitations` a quien gestione invitaciones (o usar `super_admin`).

Archivos: `app/Filament/Pages/ManageTeamInvitations.php`, `app/Services/TeamInvitationService.php`, `app/Models/TeamInvitation.php`, `app/Enums/TeamRole.php`.

---

## Moneda del taller

Importes mostrados en tipos de mantenimiento, órdenes de trabajo, dashboard y tablas usan `App\Support\Money::format()`, leyendo `config/workshop.php`.

### Variables de entorno

| Variable | Descripción | Por defecto |
|----------|-------------|-------------|
| `WORKSHOP_CURRENCY_SYMBOL` | Símbolo mostrado (`$`, `€`, `S/`, etc.) | `$` |
| `WORKSHOP_CURRENCY_CODE` | Código ISO (referencia / futuros informes) | `USD` |
| `WORKSHOP_CURRENCY_POSITION` | `before` → `$ 149.00` · `after` → `149,00 €` | `before` |
| `WORKSHOP_DECIMAL_SEPARATOR` | Separador decimal | `.` |
| `WORKSHOP_THOUSANDS_SEPARATOR` | Separador de miles | `,` |

### Ejemplos

```env
# Estados Unidos (por defecto)
WORKSHOP_CURRENCY_SYMBOL=$
WORKSHOP_CURRENCY_CODE=USD
WORKSHOP_CURRENCY_POSITION=before

# Euro
WORKSHOP_CURRENCY_SYMBOL=€
WORKSHOP_CURRENCY_CODE=EUR
WORKSHOP_CURRENCY_POSITION=after
WORKSHOP_DECIMAL_SEPARATOR=,
WORKSHOP_THOUSANDS_SEPARATOR=.

# Soles (Perú)
WORKSHOP_CURRENCY_SYMBOL=S/
WORKSHOP_CURRENCY_CODE=PEN
WORKSHOP_CURRENCY_POSITION=before
```

Tras cambiar `.env`, ejecuta `php artisan config:clear` si usas configuración en caché.

---

## Módulos del panel (Filament)

| Recurso / página | Descripción |
|------------------|-------------|
| Dashboard | KPIs y gráfico de órdenes (Fase 1) |
| Marcas / Modelos | Catálogo de motocicletas del taller |
| Tipos de mantenimiento | Servicios (precio y duración estimada) |
| Sucursales | Sedes del taller |
| Clientes | Ficha + motos y órdenes relacionadas |
| Motos | Ficha + órdenes relacionadas |
| Órdenes de trabajo | Flujo completo con pestañas y filtros |
| Filament Shield → Roles | Permisos por recurso, página y widget |
| Equipo → Invitaciones | Alta de miembros por correo |

### Estados de una orden

| Estado | Etiqueta |
|--------|----------|
| `received` | Recibido |
| `in_progress` | En taller |
| `ready` | Listo |
| `delivered` | Entregado |

**Activas** = recibido + en taller + listo (excluye entregadas).

---

## Estructura relevante

```
app/
├── Enums/
│   ├── TeamRole.php               # Roles de invitación
│   └── WorkOrderStatus.php        # Estados de órdenes
├── Filament/
│   ├── Pages/
│   │   ├── ManageTeamInvitations.php
│   │   └── Tenancy/RegisterTeam.php
│   ├── Resources/                 # CRUD del panel
│   └── Widgets/                   # Dashboard Fase 1
├── Http/Controllers/
│   └── AcceptTeamInvitationController.php
├── Policies/                      # Generadas por Shield
├── Support/
│   ├── Money.php
│   ├── ShieldBootstrap.php
│   ├── TenancyPermissions.php
│   ├── TenantWorkOrderQuery.php
│   └── WorkOrderRevenue.php
└── Providers/Filament/
    └── AdminPanelProvider.php     # Panel /admin + Shield + tenant
config/
├── filament-shield.php
├── permission.php
└── workshop.php                   # Moneda
database/seeders/
├── DatabaseSeeder.php
└── DemoDataSeeder.php
```

---

## Configuración general

Variables habituales en `.env`:

| Variable | Descripción |
|----------|-------------|
| `APP_URL` | URL base (enlaces de invitación y panel) |
| `APP_LOCALE` | Idioma de Laravel; etiquetas de negocio en español en código |
| `DB_*` | Conexión a base de datos |
| `WORKSHOP_CURRENCY_*` | Ver sección [Moneda del taller](#moneda-del-taller) |

---

## Tests

```bash
composer test
# o
php artisan test
```

Incluye pruebas de invitaciones (`tests/Feature/TeamInvitationTest.php`) y formato de moneda (`tests/Unit/MoneyTest.php`).

---

## Licencia

Proyecto basado en [Laravel](https://laravel.com), publicado bajo la [licencia MIT](https://opensource.org/licenses/MIT).
