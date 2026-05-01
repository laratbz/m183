<?php
function fxRate(string $from, string $to, ?string $date = null): ?float {
    $baseUrl = $date ? "https://api.frankfurter.app/{$date}" : "https://api.frankfurter.app/latest";
    $url = "{$baseUrl}?from={$from}&to={$to}";
    $json = @file_get_contents($url);
    if (!$json) return null;
    $data = json_decode($json, true);
    return $data['rates'][$to] ?? null;
}

function getFxRateUSDinCHF() {
	$url = "https://api.frankfurter.dev/v1/latest?base=USD&symbols=CHF";
	$data = json_decode(file_get_contents($url), true);
	$rate = $data['rates']['CHF'];
	return $rate;
}

function getFxRateEURinCHF() {
	$url = "https://api.frankfurter.dev/v1/latest?base=EUR&symbols=CHF";
	$data = json_decode(file_get_contents($url), true);
	$rate = $data['rates']['CHF'];
	return $rate;
}

?>