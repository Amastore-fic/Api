# Amastore · API

API REST (Laravel 13 + PostgreSQL + MongoDB + Redis) que consumen la web y la app Android. Monolito modular en capas: **rutas → middleware de rol → controlador → servicio → modelo**.

Todas las rutas viven bajo `/api/v1`, responden JSON y se autentican con `Authorization: Bearer <token>` (Laravel Sanctum).

### Dos bases de datos

| Base | Para qué | Contenedor |
|---|---|---|
| **PostgreSQL** (principal) | Todo lo que necesita relaciones y consistencia: usuarios, vendedores, productos, carrito, pedidos, pagos, calificaciones | `comprasegura_db` |
| **MongoDB** | Datos sin relaciones o que crecen mucho, como la bitácora de eventos | `comprasegura_mongo` |

La app entra a MongoDB con su propio usuario (no con el administrador). Ese usuario lo crea `docker/mongo/init-mongo.js` la primera vez que arranca el contenedor.

### Caché (Redis)

La caché de Laravel (`Cache::remember(...)`, `Cache::put(...)`, etc.) vive en Redis, en el contenedor `comprasegura_redis`.

- `docker-compose.yml` fija `CACHE_STORE=failover`: se usa Redis y, si no responde, la tabla `cache` de PostgreSQL. Una caída de Redis no tumba la app, pero la vuelve **muy lenta** (unos 4-5 s por cada uso de la caché, casi todo esperando a que Docker diga que el contenedor no existe). Si notas la API lenta, revisa `docker compose ps`.
- Redis solo guarda caché: no escribe nada en disco, así que al apagar el contenedor se vacía. **No guardes ahí nada que no se pueda volver a calcular.**
- Tiene un límite de 256 MB; si se llena, borra solo las claves que menos se usan.
- Para vaciar la caché: `docker compose exec app php artisan cache:clear`.

### Bitácora (MongoDB)

Cada intento de inicio de sesión queda registrado en la colección `bitacora`:

| Evento | Cuándo |
|---|---|
| `login_exitoso` | Inició sesión |
| `login_fallido` | Correo o contraseña incorrectos |
| `login_bloqueado` | Demasiados intentos fallidos seguidos |
| `login_cuenta_inactiva` | La cuenta la bloqueó un administrador |
| `logout` | Cerró sesión |

- Se guarda: evento, usuario y rol (si existe), IP, dispositivo, navegador y fecha.
- **Nunca** se guardan contraseñas ni tokens. Del correo solo se guarda su hash (SHA-256), para agrupar intentos contra una misma cuenta sin almacenarlo.
- Los registros se borran solos después de `BITACORA_DIAS_RETENCION` días (180 por defecto).
- Si MongoDB está caído, el inicio de sesión funciona igual; el error queda en `storage/logs/laravel.log`.
- La colección, su validación y sus índices se crean con la migración de `database/migrations/mongodb/`, que corre con el `php artisan migrate` de siempre.

Para ver los registros más recientes:

```bash
docker compose exec app php artisan bitacora:ver
docker compose exec app php artisan bitacora:ver --evento=login_fallido --limite=20
```

Las fechas se muestran en UTC.

### Correos

| Dónde | A dónde van |
|---|---|
| **Desarrollo** | A **Mailpit** (contenedor `comprasegura_mailpit`): los atrapa y se leen en `http://localhost:8025`. No le llegan a nadie, así que se puede probar con cualquier correo. |
| **Producción** | A la cuenta de **Gmail** del proyecto, por SMTP (`smtp.gmail.com`, puerto 587). |
| **Pruebas automáticas** | A ningún lado (`MAIL_MAILER=array`). |

Para probar el envío: `docker compose exec app php artisan correo:probar alguien@ejemplo.com`.

La contraseña de Gmail es una **contraseña de aplicación** (se crea en la cuenta de Google con la verificación en dos pasos activada) y va solo en el `.env`, nunca en el repositorio.

**Cola:** el contenedor `comprasegura_queue` envía en segundo plano los correos que no deben hacer esperar al usuario (implementan `ShouldQueue`), como los de una compra. El de la **contraseña temporal no va por la cola**, por dos motivos: la contraseña quedaría legible en la tabla `jobs`, y si el envío falla hay que revertirla.

### Recuperación de contraseña

`POST /auth/forgot-password` envía por correo una **contraseña temporal**, que convive con la actual:

- Sirve para entrar durante **10 minutos**; después deja de funcionar. Si se pide otra, solo vale la más reciente.
- Si se entra con la contraseña actual, la temporal se anula.
- Si se entra con la temporal, **reemplaza a la anterior** y se cierran las demás sesiones.
- Durante los **30 minutos** siguientes se puede cambiar sin escribir la contraseña actual (`PUT /auth/password/restablecer`, pantalla `/restablecer-password`). El campo `cambio_password_hasta` del usuario indica hasta cuándo; mientras esté vigente, la web muestra el aviso. Después, el cambio se hace desde `/perfil/seguridad`, que sí pide la actual.

Los tiempos se configuran en `config/auth.php` (`password_temporal`).

## Requisitos

- **Docker Desktop**, abierto y con el estado *Engine running*. No hace falta instalar PHP, PostgreSQL, MongoDB ni Redis.
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

Para comprobar MongoDB:

```bash
docker compose exec app php artisan mongodb:estado
```

Debe decir *"MongoDB responde correctamente"* con la versión del servidor y *Lectura y escritura: OK*.

Para comprobar Redis:

```bash
docker compose exec app php artisan redis:estado
```

Debe decir *"Redis responde correctamente"*, *Lectura y escritura: OK* y *Cache de la app: failover*.

### Si ya tenías el proyecto antes de que se agregaran MongoDB y Redis

Hay que reconstruir la imagen de PHP (ahora incluye las extensiones de MongoDB y Redis), instalar los paquetes nuevos y aplicar las migraciones:

```bash
git pull
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan migrate
docker compose exec app php artisan mongodb:estado
docker compose exec app php artisan redis:estado
```

Tu `.env` no necesita cambios: si no tiene las variables `MONGO_*` o `REDIS_*`, se usan las mismas de `.env.example`.

### Si ya tenías el proyecto antes de que se agregaran los correos

Ejecuta `docker compose up -d` para crear los contenedores `queue` y `mailpit`, y cambia en tu `.env` la línea `MAIL_MAILER=log` por estas tres:

```
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

Si no lo haces, todo sigue funcionando, pero los correos se siguen escribiendo en `storage/logs/laravel.log`.

## Uso diario

| Qué | Comando |
|---|---|
| Encender | `docker compose up -d` |
| Apagar | `docker compose down` |
| Ver logs | `docker compose logs -f app` |
| Aplicar migraciones nuevas (después de un `git pull`) | `docker compose exec app php artisan migrate` |
| Si cambió `docker/php/Dockerfile` o `docker-compose.yml` | `docker compose up -d --build` |
| Verificar MongoDB | `docker compose exec app php artisan mongodb:estado` |
| Verificar Redis | `docker compose exec app php artisan redis:estado` |
| Vaciar la caché | `docker compose exec app php artisan cache:clear` |
| Ver los correos de desarrollo | `http://localhost:8025` |
| Enviar un correo de prueba | `docker compose exec app php artisan correo:probar alguien@ejemplo.com` |
| Ver la cola | `docker compose logs -f queue` |
| Ver la bitácora | `docker compose exec app php artisan bitacora:ver` |
| Dependencias nuevas (si cambió `composer.lock`) | `docker compose exec app composer install` |
| Reiniciar la base con datos de prueba (**borra todo**) | `docker compose exec app php artisan migrate:fresh --seed` |

### Problemas comunes

- **`failed to connect to the docker API ... docker_engine`**: Docker Desktop no está abierto. Ábrelo y espera a que diga *Engine running*.
- **`"docker" no se reconoce como un comando`**: Docker Desktop no está instalado, o la terminal se abrió antes de instalarlo. Cierra la terminal y abre una nueva.
- **`port is already allocated` (8000, 5432, 27017 o 6379)**: otro programa usa ese puerto (por ejemplo, un PostgreSQL, MongoDB o Redis instalado en tu computadora). Cambia `APP_PORT`, `DB_FORWARD_PORT`, `MONGO_FORWARD_PORT` o `REDIS_FORWARD_PORT` en tu `.env` y vuelve a ejecutar `docker compose up -d`.
- **`mongodb:estado` dice *Authentication failed***: cambiaste `MONGO_USERNAME` o `MONGO_PASSWORD` después de que el contenedor de MongoDB ya existía. El usuario solo se crea la primera vez; para recrearlo hay que borrar los datos de MongoDB: `docker compose down` y luego `docker volume rm comprasegura_comprasegura-mongo`.

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

Las pruebas usan SQLite en memoria (ver `phpunit.xml`) y una bitácora en memoria (`tests/Fakes/BitacoraEnMemoria.php`): no tocan PostgreSQL, MongoDB ni Redis (la caché de las pruebas es `array`), ni necesitan que estén encendidos.

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

Listos: registro de cliente y vendedor, login, logout, perfil, catalogo (listado con búsqueda y filtro por categoría, ficha, categorías), direcciones, carrito, alta de categorías.

Pendientes (responden `501` con un `TODO` que describe la regla de negocio del MVP): pedidos, pago, calificaciones, gestión de productos del vendedor y el resto de administración.

### Carrito

Todas las rutas responden el carrito completo: `{ id, items, total, cantidad_articulos }`. Cada línea trae `id`, `id_producto`, `cantidad`, `subtotal`, `disponible` y los datos del `producto`.

| Ruta | Qué hace |
|---|---|
| `GET /carrito` | Devuelve el carrito del cliente (lo crea vacío si no tiene) |
| `POST /carrito/items` | `{ id_producto, cantidad }` (cantidad opcional, 1 por defecto). Si el producto ya está, suma las piezas |
| `PUT /carrito/items/{id}` | `{ cantidad }`: cambia la cantidad de una línea |
| `DELETE /carrito/items/{id}` | Quita una línea |

- Precios, subtotales y total los calcula el servidor; lo que mande el cliente como precio se ignora.
- No deja pasar de las existencias (422 en `cantidad`) ni agregar productos retirados o de vendedores bloqueados (422 en `id_producto`).
- Si un producto se retira o su vendedor es bloqueado después de agregarlo, la línea sigue apareciendo con `disponible: false` y no cuenta en el total.

## Reglas que no hay que perder de vista

- El total, precios y existencias los calcula **siempre** el servidor; los clientes solo muestran.
- Un pedido pertenece a un solo vendedor (supuesto del MVP).
- Las existencias se descuentan cuando el pago se **aprueba**, dentro de una transacción.
- Pago rechazado: no se genera pedido pagado, no se toca inventario, el carrito se conserva.
- Solo califica quien tiene un pedido pagado con ese producto (403 en otro caso).
- Los productos nunca se borran físicamente: pasan a `retirado`.

## Despliegue

`comprasegura-api.yml` es el pipeline de GitLab CI: corre las pruebas en `main` y `dev`, y en `main` despliega con `docker-compose.prod.yml`. La primera vez hay que crear el `.env` en el servidor a partir de `.env.production.example`.
