<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/** Prenese in varno razčleni ARSO XML. */
function get_arso_data(string $url): ?SimpleXMLElement
{
    $context = stream_context_create([
        'http' => ['timeout' => 10, 'ignore_errors' => false, 'follow_location' => 1],
    ]);
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        error_log('ARSO: prenos ni uspel: ' . $url);
        return null;
    }

    $previous = libxml_use_internal_errors(true);
    try {
        $xml = simplexml_load_string($data, SimpleXMLElement::class, LIBXML_NONET);
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
    if ($xml === false) {
        error_log('ARSO: neveljaven XML: ' . $url);
        return null;
    }
    return $xml;
}

/** Vrne nenegativno številčno vrednost za izračune ali null. */
function parse_air_number(mixed $raw): ?float
{
    if ($raw === null) {
        return null;
    }

    $text = str_replace(',', '.', trim((string) $raw));

    if (preg_match('/^[<>]\s*(\d+(?:\.\d+)?)$/', $text, $matches)) {
        $text = $matches[1];
    }

    if ($text === '' || !is_numeric($text)) {
        return null;
    }

    $number = (float) $text;

    return is_finite($number) && $number >= 0 ? $number : null;
}

/** Vrne veljavno meritev z ohranjenim znakom < ali >. */
function parse_air_value(mixed $raw): float|string|null
{
    if ($raw === null) {
        return null;
    }

    $text = str_replace(',', '.', trim((string) $raw));

    if (preg_match('/^([<>])\s*(\d+(?:\.\d+)?)$/', $text, $matches)) {
        return $matches[1] . $matches[2];
    }

    return parse_air_number($text);
}

function get_xml_number(SimpleXMLElement $station, string $element): ?float
{
    return isset($station->{$element})
        ? parse_air_number($station->{$element})
        : null;
}

/** Preslikave so določene samo v config.php. */
function get_pollutant_fields(string $type = 'hourly'): array
{
    return $type === 'daily' ? AIR_FIELDS_DAILY : AIR_FIELDS_HOURLY;
}

/** Manjkajoče meritve ostanejo null, ne nič. */
function read_station_values(SimpleXMLElement $station, string $type = 'hourly'): array
{
    $values = [];
    foreach (get_pollutant_fields($type) as $name => $field) {
        $values[$name] = isset($station->{$field})
        ? parse_air_value($station->{$field})
        : null;
        }
    return $values;
}

/**
 * Poenostavljena kategorizacija po pragovih v config.php.
 * Najslabše IZMERJENO onesnaževalo določi kategorijo in barvo.
 * Zadostuje ena sama veljavna meritev. Brez meritev: siva kartica.
 *
 * Opozorilo: to ni nujno identično uradnemu indeksu ARSO/EEA,
 * saj lahko uradna metodologija uporablja drugačna obdobja povprečenja.
 */
function getAirQualityCategory(array $values): array
{
    $worstLevel = -1;
    $mainPollutant = null;
    $validCount = 0;

    foreach (EU_AQI_THRESHOLDS as $pollutant => $limits) {
        $value = parse_air_number($values[$pollutant] ?? null);
        if ($value === null) {
            continue;
        }
        $validCount++;
        $level = 0;
        foreach ($limits as $limit) {
            if ($value > $limit) {
                $level++;
            } else {
                break;
            }
        }
        if ($level > $worstLevel) {
            $worstLevel = $level;
            $mainPollutant = $pollutant;
        }
    }

    if ($validCount === 0) {
        return [
            'label' => EU_AQI_UNKNOWN['label'],
            'color' => EU_AQI_UNKNOWN['color'],
            'pollutant' => null,
            'level' => null,
            'count' => 0,
            'complete' => false,
        ];
    }

    $category = EU_AQI_CATEGORIES[$worstLevel];
    return [
        'label' => $category['label'],
        'color' => $category['color'],
        'pollutant' => $mainPollutant,
        'level' => $worstLevel,
        'count' => $validCount,
        'complete' => true,
    ];
}

/** Prebere ljubljanske postaje iz poljubnega ARSO XML vira. */
function read_air_stations(?SimpleXMLElement $xml, string $type = 'hourly'): array
{
    if ($xml === null) {
        return [];
    }
    $results = [];
    foreach ($xml->postaja as $station) {
        $name = trim((string) ($station->merilno_mesto ?? ''));
        if (!in_array($name, AIR_STATIONS, true)) {
            continue;
        }
        $values = read_station_values($station, $type);
        $quality = $type === 'hourly' ? getAirQualityCategory($values) : null;
        $results[$name] = [
            'name' => $name,
            'values' => $values,
            'quality' => $quality,
            'date' => trim((string) ($station->datum ?? '')),
            'date_from' => trim((string) ($station->datum_od ?? '')),
            'date_to' => trim((string) ($station->datum_do ?? '')),
        ];
    }
    return $results;
}

/** Trenutne urne meritve: za barve uporabljamo le ta vir. */
function get_current_air_quality(): array
{
    return read_air_stations(get_arso_data(ARSO_HOURLY_URL), 'hourly');
}

/**
 * Zadnji dnevni podatki; zaradi različnih časovnih obdobij
 * jih NE ocenjujemo z urnim indeksom.
 */
function get_all_ljubljana_stations(): array
{
    return read_air_stations(get_arso_data(ARSO_DAILY_URL), 'daily');
}

/** Oblikovanje časovnega podatka brez izmišljanja manjkajočih datumov. */
function format_air_date(?string $date): string
{
    $date = trim((string) $date);
    if ($date === '') {
        return 'Ni podatka';
    }
    try {
        $dt = new DateTimeImmutable($date, new DateTimeZone('Europe/Ljubljana'));
        return $dt->format('d. m. Y H:i');
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Zadnjih 24 urnih intervalov, zasidranih na zadnjo
 * shranjeno meritev. Manjkajoče meritve ostanejo null.
 */
function get_hourly_history(): array
{
    $path = __DIR__ . '/data/hourlyHistory.csv';

    if (!is_readable($path)) {
        error_log('Urna zgodovina: CSV ni dostopen: ' . $path);
        return [];
    }

    $fp = fopen($path, 'r');

    if ($fp === false) {
        return [];
    }

    $history = [];
    $latest = null;

    try {
        while (($row = fgetcsv($fp)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            $date = trim((string) ($row[0] ?? ''));
            $station = trim((string) ($row[1] ?? ''));

            if (
                !preg_match(
                    '/^\d{4}-\d{2}-\d{2} \d{2}:00$/',
                    $date
                ) ||
                !in_array($station, AIR_STATIONS, true)
            ) {
                continue;
            }

            $dt = DateTimeImmutable::createFromFormat(
                '!Y-m-d H:i',
                $date,
                new DateTimeZone('Europe/Ljubljana')
            );

            if (
                $dt === false ||
                $dt->format('Y-m-d H:i') !== $date
            ) {
                continue;
            }

            $history[$date][$station] = [
                'PM2.5' => parse_air_value($row[2] ?? null),
                'PM10' => parse_air_value($row[3] ?? null),
                'OZON' => parse_air_value($row[4] ?? null),
                'SO2' => parse_air_value($row[5] ?? null),
                'NO2' => parse_air_value($row[6] ?? null),
            ];

            if ($latest === null || $date > $latest) {
                $latest = $date;
            }
        }
    } finally {
        fclose($fp);
    }

    if ($latest === null) {
        return [];
    }

    $end = DateTimeImmutable::createFromFormat(
        '!Y-m-d H:i',
        $latest,
        new DateTimeZone('Europe/Ljubljana')
    );

    $first = $end->modify('-23 hours')->format('Y-m-d H:i');

    $history = array_filter(
        $history,
        static fn ($date) => $date >= $first && $date <= $latest,
        ARRAY_FILTER_USE_KEY
    );

    ksort($history);

    return $history;
}

/**
 * Iz urnega CSV izračuna dnevna povprečja NO2 za zaključene dneve.
 * Zahtevamo najmanj 75 % pričakovanih urnih meritev.
 * Lokalni CSV brez UTC odmika ne loči podvojene ure ob jesenskem premiku.
 */
function get_daily_no2_averages(int $days = 30): array
{
    $days = max(1, min(365, $days));
    $path = __DIR__ . '/data/hourlyHistory.csv';
    if (!is_readable($path)) {
        error_log('Urna zgodovina NO2: CSV ni dostopen: ' . $path);
        return [];
    }

    $fp = fopen($path, 'r');
    if ($fp === false) {
        return [];
    }

    $tz = new DateTimeZone('Europe/Ljubljana');
    $today = new DateTimeImmutable('today', $tz);
    $firstDay = $today->modify('-' . ($days - 1) . ' days')->format('Y-m-d');
    $lastDay = $today->modify('-1 day')->format('Y-m-d');
    $measurements = [];

    try {
        while (($row = fgetcsv($fp)) !== false) {
            if (count($row) < 7) {
                continue;
            }
            $timestamp = trim((string) ($row[0] ?? ''));
            $station = trim((string) ($row[1] ?? ''));
            if (!preg_match('/^\d{4}-\d{2}-\d{2} (?:[01]\d|2[0-3]):00$/', $timestamp)
                || !in_array($station, AIR_STATIONS, true)) {
                continue;
            }
            $day = substr($timestamp, 0, 10);
            if ($day < $firstDay || $day > $lastDay) {
                continue;
            }
            $value = parse_air_number($row[6]);
            if ($value === null) {
                continue;
            }
            // Po eno vrednost na lokalno uro; morebitne podvojitve ne napihnejo povprečja.
            $measurements[$day][$station][substr($timestamp, 11, 2)] = $value;
        }
    } finally {
        fclose($fp);
    }

    $averages = [];
    foreach ($measurements as $day => $stations) {
        $start = new DateTimeImmutable($day . ' 00:00:00', $tz);
        $end = $start->modify('+1 day');
        $expectedHours = (int) (($end->getTimestamp() - $start->getTimestamp()) / 3600);
        $minimum = (int) ceil($expectedHours * 0.75);
        foreach ($stations as $station => $hours) {
            if (count($hours) >= $minimum) {
                $averages[$day][$station] = array_sum($hours) / count($hours);
            }
        }
    }
    ksort($averages);
    return $averages;
}

/**
 * Dnevna zgodovina: CSV stolpci so datum, postaja, stari JA/NE,
 * PM2.5, PM10, OZON, SO2, NO2. Starega JA/NE ne uporabljamo.
 */
function get_daily_history(int $days = 30): array
{
    $days = max(1, min(365, $days));
    $path = __DIR__ . '/data/dailyHistory.csv';
    if (!is_readable($path)) {
        error_log('Zgodovina: CSV ni dostopen: ' . $path);
        return [];
    }
    $fp = fopen($path, 'r');
    if ($fp === false) {
        return [];
    }

    $tz = new DateTimeZone('Europe/Ljubljana');
    $today = new DateTimeImmutable('today', $tz);
    $firstDay = $today->modify('-' . ($days - 1) . ' days');
    $pollutants = array_keys(AIR_FIELDS_DAILY);
    $history = [];

    try {
        while (($row = fgetcsv($fp)) !== false) {
            if (count($row) < 3) {
                continue;
            }
            $station = trim((string) ($row[1] ?? ''));
            if (!in_array($station, AIR_STATIONS, true)) {
                continue;
            }
            $dateString = substr(trim((string) ($row[0] ?? '')), 0, 10);
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateString, $tz);
            if ($date === false || $date->format('Y-m-d') !== $dateString
                || $date < $firstDay || $date > $today) {
                continue;
            }
            $values = [];
            foreach ($pollutants as $i => $pollutant) {
                    $values[$pollutant] = parse_air_value($row[$i + 3] ?? null);
                }
            $history[$dateString][$station] = $values;
        }
    } finally {
        fclose($fp);
    }
    // NO2 v dnevnem ARSO CSV je najvišja urna vrednost, ne dnevno povprečje.
    // Za graf ga zato nadomestimo s povprečjem iz shranjenih urnih meritev.
    // Ob nezadostnih meritvah ostane null, nikoli stari urni maksimum.
    foreach ($history as &$stations) {
        foreach ($stations as &$values) {
            $values['NO2'] = null;
        }
        unset($values);
    }
    unset($stations);

    foreach (get_daily_no2_averages($days) as $day => $stations) {
        foreach ($stations as $station => $average) {
            if (!isset($history[$day][$station])) {
                continue;
            }
    
            $history[$day][$station]['NO2'] = $average;
        }
    }

    ksort($history);
    return $history;
}

/** Razdalja med koordinatama v kilometrih. */
function haversine($lat1, $lon1, $lat2, $lon2): float
{
    $earthRadius = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    $a = min(1.0, max(0.0, $a));
    return 2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a));
}

function get_ljubljana_station_coords(): array
{
    return [
        'LJ Bežigrad' => ['lat' => 46.0655449, 'lon' => 14.5127203],
        'LJ Celovška' => ['lat' => 46.0720916, 'lon' => 14.4871183],
        'LJ Vič' => ['lat' => 46.037487, 'lon' => 14.4892717],
    ];
}

function get_closest_station_name($user_lat, $user_lon): string
{
    $closest = 'LJ Bežigrad';
    $minDistance = INF;
    foreach (get_ljubljana_station_coords() as $name => $coords) {
        $distance = haversine($user_lat, $user_lon, $coords['lat'], $coords['lon']);
        if ($distance < $minDistance) {
            $minDistance = $distance;
            $closest = $name;
        }
    }
    return $closest;
}

/** Staro ime funkcije, če ga uporablja še kakšna predloga. */
function renderAirStatus($answer): string
{
    if ($answer === null || $answer === '' || $answer === 'NI_PODATKOV') {
        return 'NI PODATKOV';
    }
    return (string) $answer;
}