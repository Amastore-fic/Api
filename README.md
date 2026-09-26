# CompraSegura · API

API REST (Laravel 13 + PostgreSQL) que consumen la web y la app Android. Monolito modular en capas: **rutas → middleware de rol → controlador → servicio → modelo**.

Todas las rutas viven bajo `/api/v1`, responden JSON y se autentican con `Authorization: Bearer <token>` (Laravel Sanctum).

## Arrancar (Docker)

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

La API queda en `http://localhost:8000/api/v1` y el chequeo de salud en `http://localhost:8000/up`.

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
