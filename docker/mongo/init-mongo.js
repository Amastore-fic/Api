// Se ejecuta UNA sola vez: cuando el contenedor de MongoDB arranca con el volumen vacio.
// Crea el usuario que usa la aplicacion, con permisos solo sobre la base del proyecto
// (la app nunca se conecta con el usuario administrador "root").
//
// Si cambias usuario o contrasena despues de la primera vez, hay que borrar el volumen:
//   docker compose down && docker volume rm comprasegura_comprasegura-mongo

const baseDeDatos = process.env.MONGO_INITDB_DATABASE
const usuario = process.env.MONGO_APP_USERNAME
const contrasena = process.env.MONGO_APP_PASSWORD

db.getSiblingDB(baseDeDatos).createUser({
  user: usuario,
  pwd: contrasena,
  roles: [{ role: 'readWrite', db: baseDeDatos }],
})

print(`Usuario "${usuario}" creado con permisos de lectura y escritura sobre "${baseDeDatos}".`)
