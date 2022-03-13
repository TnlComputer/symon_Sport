const mysql = require("mysql");

const conection = mysql.createConnection({
  $hostDb: "localhost",
  $nomDb: "tnlcom55_ssp",
  $userDb: "tnlcom55_ssp",
  $passDb: "Camisa00!QW",
});

conection.connect((err) => {
  if (err) throw err;
  console.log("La conexion funciona");
});

conection.query("SELECT * FROM trabajos", (err, rows) => {
  if (err) throw err;
  console.log("Los datos de la tabla son estos:");
  console.log(rows);
});

conection.end();
