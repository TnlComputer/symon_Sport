<?php
// Lectura de listas de precios de proveedores: Excel (.xlsx) o CSV / TXT, sin librerías externas.

const LISTA_MAX_FILAS = 60000;
const LISTA_MAX_MB = 15;

// Columnas que se pueden tomar de la planilla (clave => texto)
const LISTA_CAMPOS = ['codigo' => 'Código', 'descripcion' => 'Descripción', 'marca' => 'Marca', 'rubro' => 'Rubro', 'precio' => 'Precio'];

// Palabras con las que se reconoce cada columna en los títulos
const LISTA_PISTAS = [
  'codigo' => ['codigo', 'cod', 'cód', 'articulo', 'artículo', 'sku', 'referencia', 'ref', 'nro'],
  'descripcion' => ['descripcion', 'descripción', 'detalle', 'producto', 'nombre', 'articulo descripcion'],
  'marca' => ['marca', 'fabricante', 'linea', 'línea'],
  'rubro' => ['rubro', 'categoria', 'categoría', 'familia', 'grupo', 'seccion', 'sección'],
  'precio' => ['precio', 'lista', 'p. lista', 'precio lista', 'costo', 'importe', 'valor', 'pvp', 'neto'],
];

/**
 * Lee la planilla y devuelve [filas, error]. Cada fila es un array de celdas; los números de Excel llegan como float.
 */
function leerPlanilla($ruta, $nombreOriginal)
{
  $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
  if ($ext === 'xlsx') return leerXlsx($ruta);
  if (in_array($ext, ['csv', 'txt'], true)) return leerCsv($ruta);
  if ($ext === 'xls') return [null, 'Ese es el formato viejo de Excel (.xls). Abrilo y guardalo como .xlsx o .csv.'];
  return [null, 'Usá una planilla .xlsx o .csv.'];
}

function leerCsv($ruta)
{
  $texto = file_get_contents($ruta);
  if (!mb_check_encoding($texto, 'UTF-8')) $texto = mb_convert_encoding($texto, 'UTF-8', 'Windows-1252'); // listas hechas en Excel de Windows
  $texto = preg_replace('/^\xEF\xBB\xBF/', '', $texto);
  $lineas = preg_split('/\r\n|\r|\n/', $texto);

  // separador: el que más aparece en las primeras líneas
  $muestra = implode("\n", array_slice($lineas, 0, 20));
  $conteo = [';' => substr_count($muestra, ';'), ',' => substr_count($muestra, ','), "\t" => substr_count($muestra, "\t"), '|' => substr_count($muestra, '|')];
  arsort($conteo);
  $sep = array_key_first($conteo);

  while ($lineas && trim(end($lineas)) === '') array_pop($lineas);
  $filas = [];
  foreach ($lineas as $linea) { // las líneas vacías quedan, así la fila N es la misma que ve el usuario en Excel
    $filas[] = trim($linea) === '' ? [] : array_map('trim', str_getcsv($linea, $sep, '"', ''));
    if (count($filas) > LISTA_MAX_FILAS) return [null, 'La lista tiene más de ' . LISTA_MAX_FILAS . ' filas.'];
  }
  return [$filas, null];
}

function leerXlsx($ruta)
{
  if (!class_exists('ZipArchive')) return [null, 'El servidor no puede leer Excel. Guardá la lista como .csv.'];
  $zip = new ZipArchive();
  if ($zip->open($ruta) !== true) return [null, 'No se pudo abrir el Excel.'];

  // textos compartidos
  $textos = [];
  if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
    $sst = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET);
    foreach ($sst->si as $si) {
      if (isset($si->t)) {
        $textos[] = (string)$si->t;
      } else { // texto con formato: varias partes
        $t = '';
        foreach ($si->r as $r) $t .= (string)$r->t;
        $textos[] = $t;
      }
    }
  }

  // primera hoja del libro
  $hoja = 'xl/worksheets/sheet1.xml';
  if (($rels = $zip->getFromName('xl/_rels/workbook.xml.rels')) !== false && ($libro = $zip->getFromName('xl/workbook.xml')) !== false) {
    $wb = simplexml_load_string($libro, 'SimpleXMLElement', LIBXML_NONET);
    $primera = $wb->sheets->sheet[0] ?? null;
    if ($primera) {
      $rid = (string)$primera->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
      foreach (simplexml_load_string($rels, 'SimpleXMLElement', LIBXML_NONET)->Relationship as $rel) {
        if ((string)$rel['Id'] === $rid) $hoja = 'xl/' . ltrim(preg_replace('#^/?xl/#', '', (string)$rel['Target']), '/');
      }
    }
  }
  $xml = $zip->getFromName($hoja);
  $zip->close();
  if ($xml === false) return [null, 'El Excel no tiene hojas con datos.'];

  $ws = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
  $filas = [];
  foreach ($ws->sheetData->row as $row) {
    // las filas vacías no vienen en el archivo: se completan para que la fila N sea la misma que en Excel
    $numero = (int)$row['r'] ?: count($filas) + 1;
    while (count($filas) < $numero - 1) $filas[] = [];
    $fila = [];
    foreach ($row->c as $c) {
      $col = columnaExcel((string)$c['r']);
      $tipo = (string)$c['t'];
      if ($tipo === 's') $valor = $textos[(int)$c->v] ?? '';
      elseif ($tipo === 'inlineStr') $valor = (string)$c->is->t;
      elseif ($tipo === 'str' || $tipo === 'b' || $tipo === 'e') $valor = (string)$c->v;
      else $valor = (string)$c->v === '' ? '' : (float)$c->v; // número
      $fila[$col] = is_string($valor) ? trim($valor) : $valor;
    }
    $completa = $fila ? array_fill(0, max(array_keys($fila)) + 1, '') : [];
    foreach ($fila as $i => $v) $completa[$i] = $v;
    $filas[] = implode('', array_map('strval', $completa)) === '' ? [] : $completa;
    if (count($filas) > LISTA_MAX_FILAS) return [null, 'La lista tiene más de ' . LISTA_MAX_FILAS . ' filas.'];
  }
  return [$filas, null];
}

// "AB12" -> 27 (columna desde 0)
function columnaExcel($ref)
{
  $letras = preg_replace('/\d/', '', strtoupper($ref));
  $n = 0;
  foreach (str_split($letras) as $l) $n = $n * 26 + (ord($l) - 64);
  return max(0, $n - 1);
}

/**
 * Precio escrito como en Argentina o como en Excel: "$ 1.234,56", "1234.56", "1,234.56", 1234.56 -> float (null si no es un número)
 */
function numeroLista($v)
{
  if (is_float($v) || is_int($v)) return (float)$v;
  $s = preg_replace('/[^\d,.\-]/', '', (string)$v);
  if ($s === '' || !preg_match('/\d/', $s)) return null;
  $coma = strrpos($s, ',');
  $punto = strrpos($s, '.');
  if ($coma !== false && $punto !== false) { // el último separador es el decimal
    $s = $coma > $punto ? str_replace(['.', ','], ['', '.'], $s) : str_replace(',', '', $s);
  } elseif ($coma !== false) {
    $s = preg_match('/^-?\d{1,3}(,\d{3})+$/', $s) ? str_replace(',', '', $s) : str_replace(',', '.', $s);
  } elseif ($punto !== false && preg_match('/^-?\d{1,3}(\.\d{3})+$/', $s)) {
    $s = str_replace('.', '', $s); // 1.234 = mil doscientos treinta y cuatro
  }
  return is_numeric($s) ? (float)$s : null;
}

// Fila de títulos: la primera (de las 20 primeras) donde se reconocen al menos dos columnas
function filaTitulos($filas)
{
  foreach (array_slice($filas, 0, 20, true) as $i => $fila) {
    if (count(array_filter(adivinarColumnas($fila))) >= 2) return $i;
  }
  return 0;
}

// A qué columna corresponde cada campo según los títulos: ['codigo' => 0, 'precio' => 3, ...]
function adivinarColumnas($titulos)
{
  $normal = fn($t) => trim(preg_replace('/[^a-z0-9 .]/', '', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$t) ?: (string)$t)));
  $mapa = array_fill_keys(array_keys(LISTA_CAMPOS), null);
  foreach (LISTA_PISTAS as $campo => $pistas) {
    $pistas = array_map($normal, $pistas);
    foreach ($titulos as $i => $titulo) {
      if (in_array($i, $mapa, true) || !is_string($titulo) || $titulo === '') continue;
      $t = $normal($titulo);
      foreach ($pistas as $p) {
        if ($t === $p || str_starts_with($t, $p . ' ') || str_starts_with($t, $p . '.')) {
          $mapa[$campo] = $i;
          continue 3;
        }
      }
    }
  }
  return $mapa;
}
