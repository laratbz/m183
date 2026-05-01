<?php
require_once 'lib/db.php';
require_once 'lib/stocks.php';

$watchfile = __DIR__ . '/watchlist.txt';
$pdo = getPDO();
$message = '';

function normalizeSymbol(string $s): string {
    $s = trim($s);
    $s = strtoupper($s);
    // allow dot suffixes like .SW or full Yahoo style
    return preg_replace('/\s+/', '', $s);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_symbol'])) {
        $sym = normalizeSymbol($_POST['symbol'] ?? '');
        if ($sym !== '') {
            $lines = file($watchfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!in_array($sym, $lines, true)) {
                file_put_contents($watchfile, $sym . PHP_EOL, FILE_APPEND | LOCK_EX);
                $message = "Symbol {$sym} hinzugefügt.";
            } else {
                $message = "Symbol bereits in der Watchlist.";
            }
        }
    } elseif (isset($_POST['remove_symbol'])) {
        $sym = normalizeSymbol($_POST['remove_symbol']);
        $lines = file($watchfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_filter($lines, fn($l) => strtoupper(trim($l)) !== $sym);
        file_put_contents($watchfile, admin_watchlist . phpimplode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX);
        $message = "Symbol {$sym} entfernt.";
    } elseif (isset($_POST['sync_db'])) {
        // Sync: ensure columns exist in t_tagesschlusskurse
        $lines = file($watchfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $sym) {
            $sym = normalizeSymbol($sym);
            // whitelist check
            if (!preg_match('/^[A-Z0-9\.\-]{1,16}$/', $sym)) continue;
            $col = $pdo->prepare("SHOW COLUMNS FROM t_tagesschlusskurse LIKE ?");
            $col->execute([$sym]);
            if (!$col->fetch()) {
                $pdo->exec("ALTER TABLE t_tagesschlusskurse ADD COLUMN `{$sym}` FLOAT NULL");
            }
        }
        $message = "Sync abgeschlossen.";
    }
}

// read watchlist
$symbols = file($watchfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Watchlist verwalten</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">FinApp</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">Watchlist</li>
        <li class="nav-item"><a class="nav-link" href="admin_assets.php">Assets verwalten</a></li>
        <li class="nav-item"><a class="nav-link" href="assets.php">Meine Assets</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container">
  <?php if ($message): ?>
    <div class="alert alert-info"><?=htmlspecialchars($message)?></div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title">Watchlist</h5>
      <form method="post" class="row g-2 align-items-center">
        <div class="col-auto">
          <input name="symbol" class="form-control" placeholder="Symbol (z. B. NESN.SW, AAPL)">
        </div>
        <div class="col-auto">
          <button name="add_symbol" class="btn btn-success">Hinzufügen</button>
        </div>
        <div class="col-auto">
          <button name="sync_db" class="btn btn-secondary">Mit DB synchronisieren</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h6>Aktuelle Watchlist</h6>
      <table class="table table-sm">
        <thead><tr><th>Symbol</th><th>Aktion</th></tr></thead>
        <tbody>
        <?php foreach ($symbols as $s): ?>
          <tr>
            <td><?=htmlspecialchars($s)?></td>
            <td>
              <form method="post" class="d-inline">
                <button name="remove_symbol" value="<?=htmlspecialchars($s)?>" class="btn btn-sm btn-danger">Entfernen</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($symbols)): ?>
          <tr><td colspan="2" class="text-muted">Keine Symbole in watchlist.txt</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
