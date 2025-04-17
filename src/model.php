<?php

require_once(__DIR__ . '/config.php');

function get_arso_data($url) {
    $data = file_get_contents($url);
    return new SimpleXMLElement($data);
}

function get_all_ljubljana_stations() {
    
    $xml = get_arso_data('http://www.arso.gov.si/xml/zrak/ones_zrak_dnevni_podatki_zadnji.xml');
    
    $allowed_stations = ["LJ Bežigrad", "LJ Celovška", "LJ Vič"];
    
    $stations = [];
    
        foreach ($xml->postaja as $station) {
            $name = (string) $station->merilno_mesto;
    
            if (!in_array($name, $allowed_stations)) {
                continue;
            }
    
            $values = [];
    
            if (isset($station->xpath("pm2.5_dnevna")[0])) {
                $values["PM2.5"] = (string) $station->xpath("pm2.5_dnevna")[0];
            }
    
            if (isset($station->pm10_dnevna)) {
                $values["PM10"] = (string) $station->pm10_dnevna;
            }
    
            if (isset($station->o3_max_8urna)) {
                $values["OZON"] = (string) $station->o3_max_8urna;
            }
    
            if (isset($station->so2_dnevna)) {
                $values["SO2"] = (string) $station->so2_dnevna;
            }
    
            if (isset($station->no2_max_urna)) {
                $values["NO2"] = (string) $station->no2_max_urna;
            }
    
            list($answer, $elevated_values) = yes_or_no($values);
    
            $pollutants = [];
    
            foreach ($elevated_values as $key => $value) {
                $slo_limit = MAX_VALUES_SLO[$key];
                $who_limit = MAX_VALUES_WHO[$key];
    
                $exceeds_slo = $slo_limit !== null && floatval($value) >= $slo_limit;
    
                $pollutants[] = [
                    "label" => $key,
                    "value" => $value,
                    "who" => $who_limit,
                    "slo" => $slo_limit,
                    "exceeds_slo" => $exceeds_slo,
                    "description" => DESCRIPTIONS[$key] ?? ""
                ];
            }
    
            $stations[$name] = [
                "name" => $name,
                "values" => $values,
                "elevated" => $pollutants,
                "answer" => $answer
            ];
        }
    
    return $stations;
}

function get_current_air_quality() {

    $xml = get_arso_data('http://www.arso.gov.si/xml/zrak/ones_zrak_urni_podatki_zadnji.xml');

    $allowed_stations = ["LJ Bežigrad", "LJ Celovška", "LJ Vič"];

    $results = [];

    foreach ($xml->postaja as $station) {
        $name = (string) $station->merilno_mesto;

        if (!in_array($name, $allowed_stations)) {
            continue;
        }

        $values = [];

        if (isset($station->xpath("pm2.5_urni")[0])) {
            $values["PM2.5"] = (string) $station->xpath("pm2.5_urni")[0];
        }

        if (isset($station->pm10_urni)) {
            $values["PM10"] = (string) $station->pm10_urni;
        }

        if (isset($station->o3_urni)) {
            $values["OZON"] = (string) $station->o3_urni;
        }

        if (isset($station->so2_urni)) {
            $values["SO2"] = (string) $station->so2_urni;
        }

        if (isset($station->no2_urni)) {
            $values["NO2"] = (string) $station->no2_urni;
        }

        list($answer, $elevated_values) = yes_or_no($values);

        $results[$name] = [
            "name" => $name,
            "values" => $values,
            "answer" => $answer,
            "elevated" => $elevated_values
        ];
    }

    return $results;
}

function yes_or_no($values) {
    $elevated_values = [];
    $answer = "NE";

    foreach ($values as $key => $value) {
        if ($value !== null && $value !== '' && floatval($value) >= MAX_VALUES_WHO[$key]) {
            $answer = "JA";
            $elevated_values[$key] = $value;
        }
    }

    return [$answer, $elevated_values];
}

function getPolicy() {
    $db = new PDO('sqlite:' . __DIR__ . '/data/ljubljana.db') or die("cannot open the database");
    $query =  "SELECT * FROM policy_ideas";
    $policies = [];

    foreach ($db->query($query) as $row) {
        $policies[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'icon' => $row['icon']
        ];
    }

    return $policies;
}

function haversine($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earth_radius * $c;
}

function get_ljubljana_station_coords() {
    return [
        'LJ Bežigrad' => ['lat' => 46.0655449, 'lon' => 14.5127203],
        'LJ Celovška' => ['lat' => 46.0720916, 'lon' => 14.4871183],
        'LJ Vič'      => ['lat' => 46.037487,  'lon' => 14.4892717]
    ];
}

function get_closest_station_name($user_lat, $user_lon) {
    $stations = get_ljubljana_station_coords();
    $min_distance = INF;
    $closest = null;

    foreach ($stations as $name => $coords) {
        $distance = haversine($user_lat, $user_lon, $coords['lat'], $coords['lon']);
        if ($distance < $min_distance) {
            $min_distance = $distance;
            $closest = $name;
        }
    }

    return $closest ?? 'LJ Bežigrad';
}

function renderAirStatus($answer) {
    return $answer === "JA" ? "NEZDRAVO" : "ZDRAVO";
}

getPolicy();