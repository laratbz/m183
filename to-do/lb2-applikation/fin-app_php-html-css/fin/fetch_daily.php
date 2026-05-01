<?php
// CLI or web-invokable script to fetch daily closes and FX rates
require_once 'lib/db.php';
require_once 'lib/fx.php';
require_once 'lib/stocks.php';

$pdo = getPDO();
$watchfile = __DIR__ . '/watchlist.txt';
$symbols = file($watchfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// date to fetch (default today)
$date = date('Y-m-d');

// ensure table exists (minimal)
$pdo->exec("
CREATE TABLE IF NOT EXISTS t_tagesschlusskurse (
  Tag DATE PRIMARY KEY,
  USDCHF FLOAT NULL,
  EURCHF FLOAT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

function ensureSymbolColumn(PDO $pdo, string $symbol): void {
    if (!preg_match('/^[A-Z0-9\.\-]{1,16}$/', $symbol)) {
        throw new InvalidArgumentException("Ungültiges Symbol: {$symbol}");
    }
    $col = $pdo->prepare("SHOW COLUMNS FROM t_tagesschlusskurse LIKE ?");
    $col->execute([$symbol]);
    if (!$col->fetch()) {
        $pdo->exec("ALTER TABLE t_tagesschlusskurse ADD COLUMN `{$symbol}` FLOAT NULL");
    }
}

// ensure columns for symbols
foreach ($symbols as $sym) {
    $sym = strtoupper(trim($sym));
    if ($sym === '') continue;
    ensureSymbolColumn($pdo, $sym);
}

// check if row exists
$check = $pdo->prepare("SELECT 1 FROM t_tagesschlusskurse WHERE DATUM = ?");
$check->execute([$date]);
$exists = (bool)$check->fetchColumn();

// fetch FX rates
$usdchf = fxRate('USD','CHF', $date);
$eurchf = fxRate('EUR','CHF', $date);

// prepare symbol closes
$symbolCloses = [];
foreach ($symbols as $sym) {
    $sym = strtoupper(trim($sym));
    // skip empty
    if ($sym === '') continue;
    // check if value already present for this date
    $q = $pdo->prepare("SELECT `{$sym}` FROM t_tagesschlusskurse WHERE DATUM = ?");
    $q->execute([$date]);
    $val = $q->fetchColumn();
    if ($val !== null) {
        $symbolCloses[$sym] = (float)$val;
        continue;
    }
    // fetch via Yahoo (in lib/stocks.php)
    $close = lastClose($sym);
    $symbolCloses[$sym] = $close;
    // small sleep to be polite
    usleep(200000);
}

// insert or update
if (!$exists) {
    $cols = ['DATUM','USDCHF','EURCHF'];
    $placeholders = [':tag', ':usd', ':eur'];
    $params = [':tag'=>$date, ':usd'=>$usdchf, ':eur'=>$eurchf];
    foreach ($symbolCloses as $sym => $c) {
        $cols[] = "`{$sym}`";
        $placeholders[] = ":{$sym}";
        $params[":{$sym}"] = $c;
    }
    $sql = "INSERT INTO t_tagesschlusskurse (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo "Inserted row for {$date}\n";
} else {
    // update only NULL columns
    foreach ($symbolCloses as $sym => $c) {
        $sql = "UPDATE t_tagesschlusskurse SET `{$sym}` = :c WHERE DATUM = :tag AND (`{$sym}` IS NULL OR `{$sym}` = '')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':c' => $c, ':tag' => $date]);
    }
    if ($usdchf !== null) {
        $pdo->prepare("UPDATE t_tagesschlusskurse SET USDCHF = :r WHERE DATUM = :tag AND (USDCHF IS NULL OR USDCHF = '')")
            ->execute([':r'=>$usdchf, ':tag'=>$date]);
    }
    if ($eurchf !== null) {
        $pdo->prepare("UPDATE t_tagesschlusskurse SET EURCHF = :r WHERE DATUM = :tag AND (EURCHF IS NULL OR EURCHF = '')")
            ->execute([':r'=>$eurchf, ':tag'=>$date]);
    }
    echo "Updated row for {$date}\n";
}
