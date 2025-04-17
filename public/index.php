<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../src/model.php');
require_once(__DIR__ . '/../src/config.php');

$stations = get_all_ljubljana_stations();

// Geolocation coordinates handling
if (isset($_GET['lat']) && isset($_GET['lon'])) {
    $user_lat = floatval($_GET['lat']);
    $user_lon = floatval($_GET['lon']);
    $selected_station_name = get_closest_station_name($user_lat, $user_lon);
    $area = null;
} else {
    $area = $_GET['area'] ?? 'bezigrad';
    $selected_station_name = $area_to_station[$area] ?? 'LJ Bežigrad';
}

$selected_station = $stations[$selected_station_name];
$elements = $selected_station['elevated'];
$answer = $selected_station['answer'];

$hourly_data = get_current_air_quality();
$current_air = $hourly_data[$selected_station_name]['answer'];
$current_pollutants = $hourly_data[$selected_station_name]['elevated'];

$policies = getPolicy();

$history = [];
$csv_path = __DIR__ . '/../src/data/dailyHistory.csv';

if (($fp = fopen($csv_path, 'r')) !== false) {
    while (($row = fgetcsv($fp)) !== false) {
        $csv_date = $row[0];
        $csv_station = $row[1];
        $csv_answer = $row[2];

        if ($csv_station === $selected_station_name) {
            $history[] = ($csv_answer === "JA");
        }
    }
    fclose($fp);
}

$history = array_slice($history, -30);

?> 

<!doctype html>
<html lang="en">

<head>
    <link rel="stylesheet" href="/assets/style.css">  
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
    <title>Zrak v Ljubljani</title>
</head>

<body style='background:
              linear-gradient(rgba(255, 255, 255, 0),
              rgba(255, 255, 255, 0.45),
              rgba(255, 255, 255, 0.7),
              rgba(255, 255, 255, 0.9)), url("<?php echo $answer . ".jpg"; ?>");'>

    <div style="padding: 20px;">
        <button onclick="tryGeolocate()">📍 Uporabi mojo lokacijo za izbor najbližje merilne postaje</button>
    </div>

    <!-- AREA SELECTOR FORM -->
    <div style="padding: 20px;">
    <label for="area">Izberi področje, ki te zanima:</label>
    <select name="area" id="area">
        <option value="bezigrad" <?= $area == 'bezigrad' ? 'selected' : '' ?>>Bežigrad</option>
        <option value="sis" <?= $area == 'sis' ? 'selected' : '' ?>>Šiška</option>
        <option value="vic" <?= $area == 'vic' ? 'selected' : '' ?>>Vič</option>
    </select>
    </div>

    <section id="current-status" style="padding: 20px;">
        <h1>Kako varno je dihanje v Ljubljani? Preveri:</h1>
        <div class="answer"><?= renderAirStatus($current_air) ?></div>

        <?php if ($current_air === "JA"): ?>
            <h2>Trenutni zastrupljevalci:</h2>
            <ul>
                <?php foreach ($current_pollutants as $key => $value): ?>
                    <li><?= $key ?>: <?= $value ?> µg/m³</li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Vrednosti glavnih onesnaževalcev trenutno <strong>ne presegajo</strong> mejnih vrednosti WHO.</p>
        <?php endif; ?>
    </section>

    <?php require(__DIR__ . '/../src/sections/airOverview.php'); ?>
    <?php require(__DIR__ . '/../src/sections/solutions.php'); ?>

    <script src="/assets/script.js"></script>

</body>
</html>
