<?php
/**
 * Cron: shrani zadnje dnevne podatke ARSO v dailyHistory.csv.
 * CSV format: datum, postaja, [prazno zgodovinsko JA/NE], PM2.5, PM10, OZON, SO2, NO2.
 * Tretji stolpec ostane prazen zaradi združljivosti z get_daily_history().
 */
require_once __DIR__ . '/../model.php';

$xml = get_arso_data(ARSO_DAILY_URL);
if ($xml === null) {
    error_log('ARSO: dnevni XML ni na voljo.');
    exit(1);
}
$stations = read_air_stations($xml, 'daily');
$path = __DIR__ . '/dailyHistory.csv';
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
    $rawDate = trim((string) ($station['date'] ?? ''));
    if ($rawDate === '') {
        $rawDate = trim((string) ($station['date_to'] ?? ''));
    }
    if ($rawDate === '') {
        continue;
    }
    // Datum je ključ dnevnega zapisa; čas ni del dnevnega CSV.
    $date = substr($rawDate, 0, 10);
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
        error_log('Neveljaven datum ARSO: ' . $rawDate);
        continue;
    }
    $key = $date . '|' . $name;
    if (isset($seen[$key])) {
        continue;
    }

    $row = [$date, $name, ''];
    foreach (array_keys(AIR_FIELDS_DAILY) as $pollutant) {
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
echo "Dodanih dnevnih vrstic: {$added}\n";
exit($failed ? 1 : 0);
