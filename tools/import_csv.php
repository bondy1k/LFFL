<?php
// tools/import_csv.php
// SECURITY: change this token before running
$token = $_GET['token'] ?? '';
if ($token !== 'change-this-token') {
  http_response_code(403);
  echo "Forbidden";
  exit;
}

$config = require __DIR__ . '/../config.php';
$db = $config['db'];

$dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_LOCAL_INFILE => true
];
$pdo = new PDO($dsn, $db['user'], $db['pass'], $options);

$dataDir = realpath($config['data_dir']);
if (!$dataDir) {
  throw new RuntimeException("Data dir not found");
}

function tableNameFromFile($filename) {
  $t = strtolower(pathinfo($filename, PATHINFO_FILENAME));
  $t = preg_replace('/[^a-z0-9_]+/', '_', $t);
  return $t;
}

function mysqlColName($h) {
  $c = strtolower(trim($h));
  $c = preg_replace('/\s+/', '_', $c);
  $c = preg_replace('/[^a-z0-9_]/', '', $c);
  if ($c === '' || preg_match('/^\d/', $c)) $c = 'col_' . substr(md5($h),0,6);
  return $c;
}

function guessType($sample) {
  if (preg_match('/^-?\d+$/', $sample)) return 'INT';
  if (preg_match('/^-?\d+\.\d+$/', $sample)) return 'DECIMAL(18,6)';
  $t = strtotime($sample);
  if ($t !== false && $t > 0) {
    return (preg_match('/\d{2}:\d{2}/', $sample)) ? 'DATETIME' : 'DATE';
  }
  $len = max(64, min(255, strlen($sample) + 10));
  return "VARCHAR($len)";
}

function inferTypes(array $rows) {
  $headers = array_keys($rows[0]);
  $types = array_fill_keys($headers, 'VARCHAR(255)');
  foreach ($headers as $h) {
    $seen = [];
    $count = 0;
    foreach ($rows as $r) {
      if (!isset($r[$h]) || $r[$h] === '' || $r[$h] === null) continue;
      $count++;
      $seen[] = guessType((string)$r[$h]);
      if ($count >= 200) break;
    }
    if (!$seen) { $types[$h] = 'VARCHAR(255)'; continue; }
    if (in_array('VARCHAR(255)', $seen, true)) { $types[$h] = 'VARCHAR(255)'; continue; }
    if (in_array('DATETIME', $seen, true)) { $types[$h] = 'DATETIME'; continue; }
    if (in_array('DATE', $seen, true)) { $types[$h] = 'DATE'; continue; }
    if (in_array('DECIMAL(18,6)', $seen, true)) { $types[$h] = 'DECIMAL(18,6)'; continue; }
    if (in_array('INT', $seen, true)) { $types[$h] = 'INT'; continue; }
    $types[$h] = $seen[0];
  }
  return $types;
}

$imported = [];
$missing = [];

foreach ($config['csv_files'] as $file) {
  $path = $dataDir . DIRECTORY_SEPARATOR . $file;
  if (!is_file($path)) {
    $missing[] = $file;
    continue;
  }

  $fh = fopen($path, 'r');
  if (!$fh) { continue; }
  $headers = fgetcsv($fh);
  if (!$headers) { fclose($fh); continue; }

  $cols = array_map('mysqlColName', $headers);

  $sampleRows = [];
  for ($i = 0; $i < 1000 && ($row = fgetcsv($fh)) !== false; $i++) {
    $assoc = [];
    foreach ($cols as $i2 => $name) {
      $assoc[$name] = $row[$i2] ?? null;
    }
    $sampleRows[] = $assoc;
  }
  fclose($fh);

  $types = $sampleRows ? inferTypes($sampleRows) : array_fill_keys($cols, 'VARCHAR(255)');

  $table = tableNameFromFile($file);
  $colsDef = [];
  foreach ($cols as $c) {
    $t = $types[$c] ?? 'VARCHAR(255)';
    $colsDef[] = "`$c` $t NULL";
  }
  $sql = "CREATE TABLE IF NOT EXISTS `$table` (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    " . implode(",
    ", $colsDef) . ",
    PRIMARY KEY (id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

  $pdo->exec("DROP TABLE IF EXISTS `$table`");
  $pdo->exec($sql);

  $fh = fopen($path, 'r');
  fgetcsv($fh); // skip header
  $placeholders = "(" . implode(",", array_fill(0, count($cols), "?")) . ")";
  $insert = $pdo->prepare("INSERT INTO `$table` (`" . implode("`,`", $cols) . "`) VALUES $placeholders");

  $count = 0;
  while (($row = fgetcsv($fh)) !== false) {
    $values = [];
    foreach ($cols as $i2 => $name) {
      $values[] = $row[$i2] ?? null;
    }
    $insert->execute($values);
    $count++;
  }
  fclose($fh);

  $imported[] = "$file → `$table` ($count rows)";
}

echo "<pre>";
if ($imported) {
  echo "Imported:\n" . implode("\n", $imported) . "\n\n";
}
if ($missing) {
  echo "Missing (not found in data_dir):\n" . implode("\n", $missing) . "\n";
}
echo "</pre>";
