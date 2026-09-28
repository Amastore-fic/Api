# CompraSegura · API

API REST (Laravel 13 + PostgreSQL) que consumen la web y la app Android. Monolito modular en capas: **rutas → middleware de rol → controlador → servicio → modelo**.

Todas las rutas viven bajo `/api/v1`, responden JSON y se autentican con `Authorization: Bearer <token>` (Laravel Sanctum).

## Requisitos

- **Docker Desktop**, abierto y con el estado *Engine running*. No hace falta instalar PHP ni PostgreSQL.
- En Windows, Docker Desktop necesita WSL 2. Si no lo tienes, abre PowerShell como administrador, ejecuta `wsl --install` y reinicia.

## Arrancar (primera vez)

**Windows (cmd o PowerShell):**

```bat
copy .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

**Linux / macOS / Git Bash:**

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

La API queda en `http://localhost:8000/api/v1` y el chequeo de salud en `http://localhost:8000/up`. Para comprobarla, abre `http://localhost:8000/api/v1/productos` en el navegador: deben aparecer 6 productos.

## Uso diario

| Qué | Comando |
|---|---|
| Encender | `docker compose up -d` |
| Apagar | `docker compose down` |
| Ver logs | `docker compose logs -f app` |
| Aplicar migraciones nuevas (después de un `git pull`) | `docker compose exec app php artisan migrate` |
| Dependencias nuevas (si cambió `composer.lock`) | `docker compose exec app composer install` |
| Reiniciar la base con datos de prueba (**borra todo**) | `docker compose exec app php artisan migrate:fresh --seed` |

### Problemas comunes

- **`failed to connect to the docker API ... docker_engine`**: Docker Desktop no está abierto. Ábrelo y espera a que diga *Engine running*.
- **`"docker" no se reconoce como un comando`**: Docker Desktop no está instalado, o la terminal se abrió antes de instalarlo. Cierra la terminal y abre una nueva.
- **`port is already allocated` (8000 o 5432)**: otro programa usa ese puerto (por ejemplo, un PostgreSQL instalado en Windows). Cambia `APP_PORT` o `DB_FORWARD_PORT` en tu `.env` y vuelve a ejecutar `docker compose up -d`.

Usuarios de prueba (contraseña `password123`):

| Rol | Correo |
|---|---|
| Administrador | `admin@comprasegura.test` |
| Vendedor | `vendedor1@comprasegura.test`, `vendedor2@comprasegura.test` |
| Cliente | `cliente@comprasegura.test` |

## Pruebas

```bash
docker compose exec app php artisan test
```

Las pruebas usan SQLite en memoria (ver `phpunit.xml`), no tocan PostgreSQL.

## Estructura

```
app/
  Enums/                 RolUsuario, EstadoProducto, EstadoPedido, EstadoPago
  Http/
    Controllers/Api/V1/  Auth, Catalogo, Carrito, Direccion, Pedido, Pago, Calificacion
      Vendedor/          Producto, Pedido
      Admin/             Usuario, Pedido, Categoria, Producto
    Middleware/          VerificarRol  (alias 'rol:cliente,vendedor,...')
    Requests/            Validaciones por caso de uso
  Models/                Usuario, Vendedor, Categoria, Producto, Direccion, Carrito,
                         CarritoItem, Pedido, PedidoDetalle, Pago, Calificacion
  Policies/              PedidoPolicy (quien puede ver/pagar cada pedido)
  Services/              Logica de negocio (Catalogo, Pedido, Pago)
    Pagos/               PasarelaPago (interfaz) + PasarelaSimulada (sandbox)
config/pagos.php         Pasarela activa y parametros del sandbox
database/migrations/     Esquema PostgreSQL versionado
database/seeders/        Categorias, usuarios de prueba y catalogo inicial
routes/api.php           Todas las rutas del MVP agrupadas por rol
docker/                  Dockerfile PHP-FPM (dev con Xdebug / prod) y Nginx
```

## Estado de los endpoints

Listos: registro de cliente y vendedor, login, logout, perfil, catalogo (listado con búsqueda y filtro por categoría, ficha, categorías), direcciones, alta de categorías.

Pendientes (responden `501` con un `TODO` que describe la regla de negocio del MVP): carrito, pedidos, pago, calificaciones, gestión de productos del vendedor y el resto de administración.

## Reglas que no hay que perder de vista

- El total, precios y existencias los calcula **siempre** el servidor; los clientes solo muestran.
- Un pedido pertenece a un solo vendedor (supuesto del MVP).
- Las existencias se descuentan cuando el pago se **aprueba**, dentro de una transacción.
- Pago rechazado: no se genera pedido pagado, no se toca inventario, el carrito se conserva.
- Solo califica quien tiene un pedido pagado con ese producto (403 en otro caso).
- Los productos nunca se borran físicamente: pasan a `retirado`.

## Despliegue

`comprasegura-api.yml` es el pipeline de GitLab CI: corre las pruebas en `main` y `dev`, y en `main` despliega con `docker-compose.prod.yml`. La primera vez hay que crear el `.env` en el servidor a partir de `.env.production.example`.
