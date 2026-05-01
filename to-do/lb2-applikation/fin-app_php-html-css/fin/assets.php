<?php
require_once 'lib/db.php';
require_once 'lib/fx.php';
$pdo = getPDO();

// get holdings: sum of KaufVerkauf per symbol (only positive holdings)
$stmt = $pdo->query("SELECT Aktiensymbol, SUM(KaufVerkauf) AS Menge, Kaufwaehrung FROM t_myassets GROUP BY Aktiensymbol HAVING Menge > 0");
$holdings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// get available dates (most recent first)
$dates = $pdo->query("SELECT DATUM FROM t_tagesschlusskurse ORDER BY DATUM DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($dates)) {
    $dates = [date('Y-m-d')];
}
$today = $dates[0];
$yesterday = $dates[1] ?? null;

// helper: safely read a column value (symbol is a column name)
function getClose(PDO $pdo, string $symbol, string $date): ?float {
    // whitelist column name characters
    if (!preg_match('/^[A-Z0-9\.\-]{1,32}$/', $symbol)) return null;
    $q = $pdo->prepare("SELECT `{$symbol}` FROM t_tagesschlusskurse WHERE DATUM = ?");
    $q->execute([$date]);
    $r = $q->fetchColumn();
    return ($r === null || $r === '') ? null : (float)$r;
}

function perfPercent(?float $start, ?float $end): ?float {
    if ($start === null || $start == 0 || $end === null) return null;
    return (($end - $start) / $start) * 100.0;
}

// prepare data for each holding: last N closes (use up to 90 days if available)
$maxPoints = 90;
$availableDates = array_slice($dates, 0, $maxPoints); // most recent first
$labels = array_reverse($availableDates); // oldest -> newest for charts

$sparkData = []; // symbol => [values oldest->newest]
$summary = [];   // per-row summary values used in table

foreach ($holdings as $h) {
    $sym = $h['Aktiensymbol'];
    $qty = (float)$h['Menge'];
	$wrg = $h['Kaufwaehrung'];

    // collect closes for availableDates (reverse to oldest->newest)
    $values = [];
    foreach (array_reverse($availableDates) as $d) {
        $c = getClose($pdo, $sym, $d);
        $values[] = $c === null ? null : (float)$c;
    }

    // compute key closes for performance windows using nearest non-null values
    // helper to find most recent non-null value at or before index from end
    $findRecent = function(array $vals, int $offsetFromEnd) {
        // offsetFromEnd: 0 => most recent (last element), 1 => 1 before last, etc.
        $idx = count($vals) - 1 - $offsetFromEnd;
        for ($i = $idx; $i >= 0; $i--) {
            if ($vals[$i] !== null) return $vals[$i];
        }
        return null;
    };

    $closeToday = $findRecent($values, 0);
    $closeYesterday = $findRecent($values, 1);

    // approximate windows: 1d (yesterday), 3d (2 before), 7d (6 before), 30d (29 before), 90d (89 before)
    $c1 = $findRecent($values, 0);   // today
    $c3 = $findRecent($values, 2);
    $c7 = $findRecent($values, 6);
    $c30 = $findRecent($values, 29);
    $c90 = $findRecent($values, 89);

    $delta = ($closeToday !== null && $closeYesterday !== null) ? $closeToday - $closeYesterday : null;

    $summary[$sym] = [
        'symbol' => $sym,
        'qty' => $qty,
		'wrg' => $wrg,
        'closeToday' => $closeToday,
        'closeYesterday' => $closeYesterday,
        'delta' => $delta,
        'perf1' => ($c1 !== null && $closeToday !== null) ? perfPercent($c1, $closeToday) : null,
        'perf3' => ($c3 !== null && $closeToday !== null) ? perfPercent($c3, $closeToday) : null,
        'perf7' => ($c7 !== null && $closeToday !== null) ? perfPercent($c7, $closeToday) : null,
        'perf30' => ($c30 !== null && $closeToday !== null) ? perfPercent($c30, $closeToday) : null,
        'perf90' => ($c90 !== null && $closeToday !== null) ? perfPercent($c90, $closeToday) : null,
    ];

    // convert nulls to null (JSON will have null)
    $sparkData[$sym] = $values;
}

// JSON-encode labels and sparkData for client-side charts
$jsLabels = json_encode($labels);
$jsSparkData = json_encode($sparkData);
$sum = 0;
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Meine Assets</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">FinApp</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="admin_watchlist.php">Watchlist</a></li>
        <li class="nav-item"><a class="nav-link" href="admin_assets.php">Assets verwalten</a></li>
        <li class="nav-item">Meine Assets</li>
      </ul>
    </div>
  </div>
</nav>

<main class="container">
  <h4 class="mb-3">Meine Assets</h4>

  <div class="table-responsive">
    <table class="table table-striped align-middle meine-assets">
      <thead>
        <tr>
          <th>Symbol</th>
          <th>Wert heute</th>
          <th>Menge</th>
          <th><?=htmlspecialchars($today)?></th>
          <th><?=htmlspecialchars($yesterday ?? '—')?></th>
          <th>Δ</th>
          <th>Perf 1d</th>
          <th>Perf 3d</th>
          <th>Perf 7d</th>
          <th>Perf 30d</th>
          <th>Perf 90d</th>
          <th>letzte <?=count($labels)?> Tage</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($holdings)): ?>
        <tr><td colspan="11" class="text-muted">Keine positiven Bestände vorhanden</td></tr>
      <?php endif; ?>

      <?php foreach ($summary as $sym => $s): 
				$wert = $s['qty']*$s['closeToday'];
				$sum += $wert;
	  ?>
        <tr>
          <td><?=htmlspecialchars($s['symbol'])?></td>
		  <td><?=htmlspecialchars(number_format($wert, 0, '.', ''))?> <?= $s['wrg'] ?></td>
          <td><?=htmlspecialchars(number_format($s['qty'], 0, '.', ''))?></td>
		  <td><?= $s['closeToday'] !== null ? number_format($s['closeToday'], 4, '.', '') : '—' ?></td>
          <td><?= $s['closeYesterday'] !== null ? number_format($s['closeYesterday'], 4, '.', '') : '—' ?></td>
          <td><?= $s['delta'] !== null ? number_format($s['delta'], 2, '.', '') : '—' ?></td>
          <td><?= $s['perf1'] !== null ? number_format($s['perf1'], 1, '.', '') . '%' : '—' ?></td>
          <td><?= $s['perf3'] !== null ? number_format($s['perf3'], 1, '.', '') . '%' : '—' ?></td>
          <td><?= $s['perf7'] !== null ? number_format($s['perf7'], 1, '.', '') . '%' : '—' ?></td>
          <td><?= $s['perf30'] !== null ? number_format($s['perf30'], 1, '.', '') . '%' : '—' ?></td>
          <td><?= $s['perf90'] !== null ? number_format($s['perf90'], 1, '.', '') . '%' : '—' ?></td>
          <td style="width:140px; max-width:140px;">
            <canvas id="chart-<?=htmlspecialchars($s['symbol'])?>" height="60"></canvas>
          </td>
        </tr>
      <?php endforeach; ?>
        <tr>
          <td></td>
		  <td><b><?= htmlspecialchars(number_format($sum, 0, '.', ''))?> <?= $s['wrg'] ?></b></td>
          <td colspan="10"></td>
        </tr>
        <tr>
          <td></td>
		  <td><b><?= number_format($sum * getFxRateUSDinCHF(), 0, '.', '') ?> CHF</b></td>
          <td colspan="10"></td>
        </tr>
      </tbody>
    </table>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/charts.js"></script>
<script>
  // Labels (oldest -> newest)
  const labels = <?= $jsLabels ?>;
  // sparkData: { symbol: [val_oldest,...,val_newest] }
  const sparkData = <?= $jsSparkData ?>;

  // render each sparkline
  Object.keys(sparkData).forEach(sym => {
    const values = sparkData[sym].map(v => v === null ? null : Number(v));
    // replace leading/trailing nulls for chart display by carrying nearest value
    // create a cleaned array where nulls are interpolated linearly where possible, otherwise left null
    const cleaned = values.slice();
    // forward fill
    for (let i = 0; i < cleaned.length; i++) {
      if (cleaned[i] === null) {
        // find next non-null
        let j = i+1;
        while (j < cleaned.length && cleaned[j] === null) j++;
        const prev = i-1 >= 0 ? cleaned[i-1] : null;
        const next = j < cleaned.length ? cleaned[j] : null;
        if (prev !== null && next !== null) {
          // linear interpolate between prev and next
          const step = (next - prev) / (j - (i-1));
          for (let k = i; k < j; k++) {
            cleaned[k] = prev + step * (k - (i-1));
          }
          i = j;
        } else if (prev !== null) {
          // fill with prev
          for (let k = i; k < j; k++) cleaned[k] = prev;
          i = j;
        } else if (next !== null) {
          // fill with next
          for (let k = i; k < j; k++) cleaned[k] = next;
          i = j;
        } else {
          // all nulls, leave as zeros to show flat line
          for (let k = i; k < j; k++) cleaned[k] = 0;
          i = j;
        }
      }
    }

    // determine color by comparing first and last non-null
    const first = cleaned.find(v => v !== null);
    const last = [...cleaned].reverse().find(v => v !== null);
    const color = (first !== null && last !== null && last >= first) ? '#198754' : '#dc3545';

    // render
    const canvasId = 'chart-' + sym;
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          data: cleaned,
          borderColor: color,
          borderWidth: 1.5,
          pointRadius: 0,
          tension: 0.35,
          fill: false
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { display: false },
          y: { display: false }
        },
        elements: { line: { capBezierPoints: true } },
        interaction: { intersect: false }
      }
    });
  });
</script>
</body>
</html>