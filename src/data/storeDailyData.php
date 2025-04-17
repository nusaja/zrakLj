<?php 

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../model.php');

$daily_url = 'http://www.arso.gov.si/xml/zrak/ones_zrak_dnevni_podatki_zadnji.xml';
$stations = get_all_ljubljana_stations();
$xml = get_arso_data($daily_url);

$csv_path = __DIR__ . '/dailyHistory.csv';

$first_datum = (string) $xml->postaja[0]->datum;

$existing_dates = [];
if (file_exists($csv_path)) {
    if (($handle = fopen($csv_path, 'r')) !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            $existing_dates[$row[0]] = true; 
        }
        fclose($handle);
    }
}

if (isset($existing_dates[$first_datum])) {
    exit("Daily data already stored for $first_datum\n");
}

$fp = fopen($csv_path, 'a');


foreach ($stations as $station_name => $station_data) {
    $values = $station_data['values'];
    $answer = $station_data['answer'];

    foreach ($xml->postaja as $entry) {
        if ((string) $entry->merilno_mesto === $station_name) {
            $datum = (string) $entry->datum;
            break;
        }
    }

    $line = [$datum, $station_name, $answer];

    $pollutants = ["PM2.5", "PM10", "OZON", "SO2", "NO2"];

    foreach ($pollutants as $key) {
        $line[] = isset($values[$key]) ? $values[$key] : null;
    }

    fputcsv($fp, $line);
}

fclose($fp);
