<?php
require_once 'lib/db.php';

$pdo = getPDO();
$message = '';

// load watchlist for select
$watchfile = __DIR__ . '/watchlist.txt';
$symbols = file($watchfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_asset'])) {
    $tag = $_POST['tag'] ?? date('Y-m-d');
    $symbol = strtoupper(trim($_POST['symbol'] ?? ''));
    $qty = floatval($_POST['qty'] ?? 0);
    $kurs = floatval($_POST['kurs'] ?? 0);
    $w = strtoupper(substr($_POST['währung'] ?? 'CHF', 0, 3));

    if ($symbol === '' || $qty == 0) {
        $message = 'Symbol und Menge erforderlich.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO t_myassets (Tag, Aktiensymbol, KaufVerkauf, Tageskurs, Kaufwaehrung) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$tag, $symbol, $qty, $kurs, $w]);
        $message = 'Eintrag gespeichert.';
    }
}

// load existing assets
$assets = $pdo->query("SELECT * FROM t_myassets ORDER BY Tag DESC")->fetchAll();
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Assets verwalten</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">FinApp</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="admin_watchlist.php">Watchlist</a></li>
        <li class="nav-item">Assets verwalten</li>
        <li class="nav-item"><a class="nav-link" href="assets.php">Meine Assets</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container">
  <?php if ($message): ?>
    <div class="alert alert-success"><?=htmlspecialchars($message)?></div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-body">
      <h5>Neuer Kauf / Verkauf</h5>
      <form method="post" class="row g-3">
        <div class="col-md-2">
          <label class="form-label">Tag</label>
          <input type="date" name="tag" class="form-control" value="<?=date('Y-m-d')?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Symbol</label>
          <select name="symbol" class="form-select" required>
            <option value="">-- wählen --</option>
            <?php foreach ($symbols as $s): ?>
              <option value="<?=htmlspecialchars($s)?>"><?=htmlspecialchars($s)?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Menge (positiv/negativ)</label>
          <input type="number" step="any" name="qty" class="form-control" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Tageskurs</label>
          <input type="number" step="any" name="kurs" class="form-control">
        </div>
        <div class="col-md-2">
          <label class="form-label">Währung</label>
          <select name="währung" class="form-select">
            <option>CHF</option>
            <option>USD</option>
            <option>EUR</option>
          </select>
        </div>
        <div class="col-12">
          <button name="save_asset" class="btn btn-primary">Speichern</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h6>Letzte Buchungen</h6>
      <table class="table table-sm">
        <thead><tr><th>Tag</th><th>Symbol</th><th>Menge</th><th>Kurs</th><th>Währung</th></tr></thead>
        <tbody>
        <?php foreach ($assets as $a): ?>
          <tr>
            <td><?=htmlspecialchars($a['Tag'])?></td>
            <td><?=htmlspecialchars($a['Aktiensymbol'])?></td>
            <td><?=htmlspecialchars($a['KaufVerkauf'])?></td>
            <td><?=htmlspecialchars($a['Tageskurs'])?></td>
            <td><?=htmlspecialchars($a['Kaufwaehrung'])?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($assets)): ?>
          <tr><td colspan="5" class="text-muted">Keine Buchungen vorhanden</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
