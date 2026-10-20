<?php
/**
 * Cron: shrani zadnje urne meritve ARSO v hourlyHistory.csv.
 * CSV format: datum/čas, postaja, [prazno zgodovinsko JA/NE], PM2.5, PM10, OZON, SO2, NO2.
 * Ne ocenjujemo WHO in ne računamo JA/NE.
 */
require_once __DIR__ . '/../model.php';

$xml = get_arso_data(ARSO_HOURLY_URL);
if ($xml === null) {
    error_log('ARSO: urni XML ni na voljo.');
    exit(1);
}
$stations = read_air_stations($xml, 'hourly');
$path = __DIR__ . '/hourlyHistory.csv';
$fp = @fopen($path, 'c+');
if ($fp === false) {
    error_log('Ni mogoče odpreti CSV: ' . $path);
    exit(1);
}
if (!flock($fp, LOCK_EX)) {
    fclose($fp);
    exit(1);
}

$seen = [];
rewind($fp);
while (($row = fgetcsv($fp)) !== false) {
    if (count($row) >= 2) {
        $seen[trim((string) $row[0]) . '|' . trim((string) $row[1])] = true;
    }
}

fseek($fp, 0, SEEK_END);
$added = 0;
$failed = false;
foreach ($stations as $name => $station) {
    $timestamp = trim((string) ($station['date_from'] ?? ''));
    if ($timestamp === '') {
        $timestamp = trim((string) ($station['date_to'] ?? ''));
    }
    if ($timestamp === '') {
        continue;
    }
    $key = $timestamp . '|' . $name;
    if (isset($seen[$key])) {
        continue;
    }

    $row = [$timestamp, $name, ''];
    foreach (array_keys(AIR_FIELDS_HOURLY) as $pollutant) {
        $row[] = $station['values'][$pollutant] ?? '';
    }
    if (fputcsv($fp, $row) === false) {
        error_log('Napaka pri zapisovanju: ' . $path);
        $failed = true;
        break;
    }
    $seen[$key] = true;
    $added++;
}
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);
echo "Dodanih urnih vrstic: {$added}\n";
exit($failed ? 1 : 0);
