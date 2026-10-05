# Sistema Multinegocio

Plataforma SaaS multi-tenant para administrar varios negocios desde un mismo sistema. Cada negocio tiene sus datos aislados, sus usuarios con roles y accesos con vigencia, y un libro de movimientos encadenado que detecta cualquier alteración.

Nació de la dificultad real de un dueño de varios negocios que ofrecía bienes y servicios: llevar tanta información junta era complicado, sobre todo al tener que entregar el inventario.

## Funcionalidades

- **Negocios y rubros:** alta de negocios, activación o desactivación, y configuración por rubro.
- **Inventario:** almacenes, productos y traspasos entre almacenes.
- **Ventas:** turnos de caja, ventas con detalle de productos y reportes por negocio.
- **Libro de movimientos:** registro de eventos del negocio encadenado con SHA-256. Si una entrada se altera, la cadena se rompe y se detecta.
- **Accesos y roles:** permisos por usuario, negocio y rol, con vigencia por fechas, días y horario.
- **Soporte:** solicitudes de soporte e intervenciones auditadas de administración.

## Arquitectura multi-tenant

- **Aislamiento por columna:** las entidades de negocio comparten base de datos y llevan una columna `business_id`. Los controladores verifican la pertenencia del recurso antes de devolverlo o modificarlo.
- **Negocio activo:** el negocio con el que trabaja el usuario se resuelve desde el contexto de sesión, no desde un filtro opcional en cada consulta (`NegocioActivoResolver`).
- **Permisos con vigencia:** la autorización se resuelve en un servicio independiente de la autenticación (`AccessScheduler`), que evalúa si un acceso está activo en el momento de la petición.
- **Auditoría por negocio:** cada evento se guarda con su `business_id` y un hash SHA-256 que incluye el hash del evento anterior.

## Stack

- **Backend:** Laravel 13, PHP 8.3+, Spatie Permission
- **Frontend:** Inertia.js 2 con Vue 3, Tailwind CSS
- **Base de datos:** SQLite por defecto; PostgreSQL configurable con `DB_CONNECTION=pgsql`

## Puesta en marcha

Requisitos: PHP 8.3 o superior, Composer, Node.js y una base de datos.

```bash
composer run setup
composer run dev
```

`setup` instala dependencias, crea el archivo `.env` a partir de `.env.example`, genera la clave de la aplicación, ejecuta las migraciones y compila los assets. `dev` levanta el servidor, la cola, el visor de logs y Vite.

Para usar PostgreSQL, define en `.env`:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=multinegocio
DB_USERNAME=postgres
DB_PASSWORD=
```

## Estructura

```
app/
  Models/            # Business, Access, Almacen, Producto, Traspaso, Turno, Venta, ActivityLog...
  Services/          # NegocioActivoResolver, AccessScheduler, Bitacora, Intervenciones
  Http/Controllers/  # Negocio, Inventario, Ventas, Admin
routes/web.php       # rutas por módulo, protegidas con autenticación y verificación
docs/                # documentación de requisitos
```
