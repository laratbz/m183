<?php
function lastClose(string $symbol): ?float {
    $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?interval=1d&range=5d";
    $json = @file_get_contents($url);
    if (!$json) return null;
    $data = json_decode($json, true);
    $chart = $data['chart']['result'][0] ?? null;
    if (!$chart) return null;
    $closes = $chart['indicators']['quote'][0]['close'] ?? [];
    for ($i = count($closes)-1; $i >= 0; $i--) {
        if ($closes[$i] !== null) return (float)$closes[$i];
    }
    return null;
}
?>
