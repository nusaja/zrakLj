<?php 

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../model.php');

$hourly_url = 'http://www.arso.gov.si/xml/zrak/ones_zrak_urni_podatki_zadnji.xml';
$xml = get_arso_data($hourly_url);

$allowed_stations = ['LJ Bežigrad', 'LJ Celovška', 'LJ Vič'];
$pollutants = ['PM2.5' => 'pm2.5', 'PM10' => 'pm10', 'OZON' => 'o3', 'SO2' => 'so2', 'NO2' => 'no2'];

$csv_path = __DIR__ . '/hourlyHistory.csv';

$first_timestamp = null;

foreach ($xml->postaja as $entry) {
    if (in_array((string) $entry->merilno_mesto, $allowed_stations)) {
        $first_timestamp = (string) $entry->datum_od;
        break;
    }
}

$existing_timestamps = [];

if (file_exists($csv_path)) {
    if (($handle = fopen($csv_path, 'r')) !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            $existing_timestamps[$row[0]] = true;
        }
        fclose($handle);
    }
}

if (isset($existing_timestamps[$first_timestamp])) {
    exit("Hourly data already stored for $first_timestamp\n");
}

$fp = fopen($csv_path, 'a');

foreach ($xml->postaja as $station) {
    $station_name = (string) $station->merilno_mesto;

    if (!in_array($station_name, $allowed_stations)) {
        continue;
    }

    $timestamp = (string) $station->datum_od;

    $line = [$timestamp, $station_name];

    foreach ($pollutants as $label => $tag) {
        $line[] = isset($station->$tag) ? (string) $station->$tag : null;
    }

    fputcsv($fp, $line);
}

fclose($fp);
