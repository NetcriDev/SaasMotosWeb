# SaasMotosWeb

Plataforma SaaS para la gestión de talleres de motocicletas. Cada taller opera como un **tenant** independiente y administra sucursales, clientes, motos, catálogos y órdenes de trabajo desde un panel web.

## Características

- **Multi-tenant** con equipos (`Team`) mediante Filament Tenancy: registro de talleres y cambio de contexto por usuario.
- **Sucursales**: varias sedes por taller (dirección, teléfono).
- **Clientes** asociados a sucursal y taller.
- **Motos** vinculadas a cliente, sucursal, marca y modelo del catálogo.
- **Catálogos por taller**: marcas, modelos y tipos de mantenimiento.
- **Órdenes de trabajo** con estados (`Recibido`, `En taller`, `Listo`, `Entregado`), fechas de recepción/entrega y validación de que la moto pertenezca al cliente.
- **Datos de demostración** listos para probar el flujo completo.

## Stack tecnológico

| Capa | Tecnología |
|------|------------|
| Backend | PHP 8.3, Laravel 13 |
| Panel admin | Filament 5 |
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

# Assets frontend
npm install
npm run build

# Datos de demostración (opcional)
php artisan db:seed
```

También puedes usar el script de Composer que automatiza el setup inicial:

```bash
composer setup
```

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

El seeder crea dos sucursales, clientes, motos, catálogos (marcas, modelos, tipos de mantenimiento) y órdenes de trabajo de ejemplo.

## Módulos del panel (Filament)

| Recurso | Descripción |
|---------|-------------|
| Marcas / Modelos | Catálogo de motocicletas del taller |
| Tipos de mantenimiento | Servicios ofrecidos (aceite, frenos, etc.) |
| Sucursales | Sedes del taller |
| Clientes | Clientes por sucursal |
| Motos | Vehículos registrados |
| Órdenes de trabajo | Flujo de recepción y entrega en taller |

## Estructura relevante

```
app/
├── Enums/WorkOrderStatus.php      # Estados de órdenes
├── Filament/Resources/            # CRUD del panel admin
├── Filament/Pages/Tenancy/        # Registro de talleres
├── Models/                        # Team, Branch, Client, Motorcycle, WorkOrder...
└── Providers/Filament/            # Configuración del panel (/admin)
database/
├── migrations/                    # Esquema multi-tenant
└── seeders/DemoDataSeeder.php     # Datos de prueba
```

## Configuración

Variables habituales en `.env`:

- `APP_URL` — URL base de la aplicación
- `DB_CONNECTION` — por defecto `sqlite`; descomenta y configura MySQL/PostgreSQL si lo necesitas
- `APP_LOCALE` — idioma de la aplicación (las etiquetas de estados de órdenes están en español en código)

## Tests

```bash
composer test
# o
php artisan test
```

## Licencia

Proyecto basado en [Laravel](https://laravel.com), publicado bajo la [licencia MIT](https://opensource.org/licenses/MIT).
