<?php
require_once 'lib/db.php';
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Finanz-Übersicht</title>
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
        <li class="nav-item"><a class="nav-link" href="admin_assets.php">Assets verwalten</a></li>
        <li class="nav-item"><a class="nav-link" href="assets.php">Meine Assets</a></li>
      </ul>
    </div>
  </div>
</nav>

<main class="container">
  <div class="row g-4">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Watchlist</h5>
          <p class="card-text">Symbole aus <code>watchlist.txt</code> verwalten und mit Kurs-Tabelle synchronisieren.</p>
          <a href="admin_watchlist.php" class="btn btn-primary">Zur Watchlist</a>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Assets</h5>
          <p class="card-text">Käufe/Verkäufe erfassen und Performance anzeigen.</p>
          <a href="admin_assets.php" class="btn btn-primary">Assets verwalten</a>
          <a href="assets.php" class="btn btn-outline-primary ms-2">Meine Assets</a>
        </div>
      </div>
    </div>
  </div>

  <hr class="my-4">

  <div class="card">
    <div class="card-body">
      <h6>Cron / Abruf</h6>
      <p>Das Script <code>fetch_daily.php</code> holt Tageskurse (Aktien via Yahoo, FX via Frankfurter.app). Richte einen Cronjob ein (z. B. 18:30 CET).</p>
    </div>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
