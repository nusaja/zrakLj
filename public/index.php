
<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../src/model.php';

$hourly_data = get_current_air_quality();

// ==========================================
// IZBRANO OBMOČJE
// ==========================================

$area = $_GET['area'] ?? 'bezigrad';

if (!is_string($area) || !isset(AREA_TO_STATION[$area])) {
    $area = 'bezigrad';
}

if (
    isset($_GET['lat'], $_GET['lon']) &&
    is_scalar($_GET['lat']) &&
    is_scalar($_GET['lon']) &&
    is_numeric($_GET['lat']) &&
    is_numeric($_GET['lon'])
) {
    $lat = (float) $_GET['lat'];
    $lon = (float) $_GET['lon'];

    if (
        is_finite($lat) &&
        is_finite($lon) &&
        abs($lat) <= 90 &&
        abs($lon) <= 180
    ) {
        $nearest = get_closest_station_name($lat, $lon);

        $nearestArea = array_search(
            $nearest,
            AREA_TO_STATION,
            true
        );

        if ($nearestArea !== false) {
            $area = $nearestArea;
        }
    }
}

$selected_station_name = AREA_TO_STATION[$area];

// ==========================================
// ČAS ZADNJIH URNIH MERITEV
// ==========================================

$measurementFrom = null;
$measurementTo = null;
$measurementPeriods = [];
$allStationsHavePeriod = true;

foreach (AREA_TO_STATION as $stationName) {
    $station = $hourly_data[$stationName] ?? null;

    if (!is_array($station)) {
        $allStationsHavePeriod = false;
        break;
    }

    $from = $station['date_from'] ?? null;
    $to = $station['date_to'] ?? null;

    if (
        !is_scalar($from) ||
        !is_scalar($to) ||
        trim((string) $from) === '' ||
        trim((string) $to) === ''
    ) {
        $allStationsHavePeriod = false;
        break;
    }

    $fromTimestamp = strtotime((string) $from);
    $toTimestamp = strtotime((string) $to);

    if (
        $fromTimestamp === false ||
        $toTimestamp === false ||
        $toTimestamp <= $fromTimestamp
    ) {
        $allStationsHavePeriod = false;
        break;
    }

    $measurementPeriods[] = [
        'from' => $fromTimestamp,
        'to' => $toTimestamp
    ];
}

if (
    $allStationsHavePeriod &&
    count($measurementPeriods) === count(AREA_TO_STATION)
) {
    $firstPeriod = $measurementPeriods[0];
    $samePeriod = true;

    foreach ($measurementPeriods as $period) {
        if (
            $period['from'] !== $firstPeriod['from'] ||
            $period['to'] !== $firstPeriod['to']
        ) {
            $samePeriod = false;
            break;
        }
    }

    if ($samePeriod) {
        $measurementFrom = date('H:i', $firstPeriod['from']);
        $measurementTo = date('H:i', $firstPeriod['to']);
    }
}

// ==========================================
// PODATKI ZA GRAF
// ==========================================

$dailyHistory = get_daily_history(360);
$hourlyHistory = get_hourly_history();

/**
 * Varno kodiranje podatkov za JSON script elemente.
 * Enake nastavitve uporabimo za vse podatkovne bloke.
 */
function encode_page_json(array $data): string
{
    $json = json_encode(
        $data,
        JSON_UNESCAPED_UNICODE |
        JSON_HEX_TAG |
        JSON_HEX_AMP |
        JSON_HEX_APOS |
        JSON_HEX_QUOT |
        JSON_INVALID_UTF8_SUBSTITUTE
    );

    return $json === false ? '{}' : $json;
}

$dailyJson = encode_page_json($dailyHistory);
$hourlyJson = encode_page_json($hourlyHistory);

/**
 * Konfiguracija referenčnih območij grafa.
 *
 * Številčne vrednosti izvirajo izključno
 * iz src/config.php.
 *
 * JavaScript ne določa svojih pragov.
 */
$chartReferences = [
    'eea' => [
        'thresholds' => EU_AQI_THRESHOLDS,
        'categories' => EU_AQI_CATEGORIES
    ],
    'who' => [
        'guidelines2021' => WHO_AQG_2021,
    ],
    'pollutants' => HISTORY_CHART_POLLUTANTS
];

$chartReferencesJson = encode_page_json($chartReferences);

?>
<!doctype html>
<html lang="sl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta
        name="description"
        content="Preveri kakovost zraka v Ljubljani. Trenutne meritve PM2.5, PM10, NO₂ in O₃, zgodovina onesnaženosti ter primerjava z mejnimi vrednostmi EU in smernicami WHO."    >

    <title>zrakLJ: Kakovost zraka v Ljubljani</title>

    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">

    <!-- SEO -->
    <link rel="canonical" href="https://zraklj.si/">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="sl_SI">
    <meta property="og:site_name" content="zrakLJ">
    <meta property="og:title" content="zrakLJ: Kakovost zraka v Ljubljani">
    <meta property="og:description"
        content="Kako kakovosten je zrak v Ljubljani? Preveri trenutne meritve, zgodovino onesnaženosti in dolgoročne trende.">
    <meta property="og:url" content="https://zraklj.si/">
    <meta property="og:image" content="https://zraklj.si/assets/og-image.png">
    <meta property="og:image:width" content="1201">
    <meta property="og:image:height" content="625">
    <meta property="og:image:alt" content="zrakLJ.si - Kakovost zraka v Ljubljani.">

    <!-- Social media -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="https://zraklj.si/assets/og-image.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="/assets/style.css">

    <!-- Matomo -->
    <script>
    var _paq = window._paq = window._paq || [];
    /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
    _paq.push(['trackPageView']);
    _paq.push(['enableLinkTracking']);
    (function() {
        var u="//statistika.andree.si/";
        _paq.push(['setTrackerUrl', u+'matomo.php']);
        _paq.push(['setSiteId', '8']);
        var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
        g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
    })();
    </script>
    <!-- End Matomo Code -->

</head>

<body>
<div class="page">

    <header class="site-header">
        <a class="logo" href="/">zrakLJ</a>

        <span class="header-caption">
            LJUBLJANA / KAKOVOST ZRAKA
        </span>
    </header>

    <main>

        <!-- HERO -->

        <section
            class="hero"
            aria-labelledby="main-title"
        >
            <span class="eyebrow">
                NEODVISEN PREGLED KAKOVOSTI ZRAKA
            </span>

            <h1 id="main-title">
                JE LJUBLJANA<br>BOLANA?
            </h1>
            
        </section>

        <!-- NAVIGACIJA -->

        <nav
            id="section-navigation"
            class="section-nav"
            aria-label="Vsebina"
        >
            <a href="#trenutno">
                01 / TRENUTNO STANJE
            </a>

            <a href="#grafi">
                02 / ARHIV MERITEV
            </a>

            <a href="#dolgorocno">
                03 / DOLGOROČNO STANJE
            </a>

            <a href="#izboljsave">
                04 / IZBOLJŠAVE
            </a>
        </nav>

        <!-- TRENUTNE MERITVE -->

        <section
            id="trenutno"
            class="content-section"
            aria-labelledby="trenutno-title"
        >
            <div class="section-title current-section-title">
                <h2 id="trenutno-title">ZADNJE URNE MERITVE</h2>

                <div class="current-measurement-toolbar">
                    <?php if (
                        $measurementFrom !== null &&
                        $measurementTo !== null
                    ): ?>

                       <div class="measurement-period">

                            <span class="measurement-time">
                                <span class="measurement-period-label">OD</span>
                                <strong>
                                    <?= htmlspecialchars(
                                        $measurementFrom,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </span>

                            <span class="measurement-time">
                                <span class="measurement-period-label">DO</span>
                                <strong>
                                    <?= htmlspecialchars(
                                        $measurementTo,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>
                            </span>

                        </div>

                    <?php endif; ?>

                    <span
                        id="geo-selected-label"
                        class="geo-selected-label"
                        hidden
                    >
                        NAJBLIŽJA
                    </span>

                    <button
                        id="geolocate"
                        class="geo-icon-button"
                        type="button"
                        aria-label="Vam najbližja merilna postaja"
                        title="Vam najbližja merilna postaja"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            width="18"
                            height="18"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                            <circle cx="12" cy="10" r="2.5"/>
                        </svg>
                    </button>
                </div>

            </div>

            <div class="stations-grid">

                <?php
                $currentAirPath =
                    __DIR__ . '/../src/sections/currentAir.php';

                if (is_file($currentAirPath)) {
                    require $currentAirPath;
                } else {
                    echo '<p>Prikaz merilnih postaj trenutno ni na voljo.</p>';
                }
                ?>

            </div>

            <p class="source-note">
                ● Označeno izmerjeno onesnaževalo ima na tej merilni postaji najslabši rezultat
                in določa prikazano kategorijo evropskega indeksa kakovosti zraka (EEA), legenda prikazana spodaj.
                <?php if (!empty($showEstimatedNote)): ?>
                <br>
                <span class="note-marker">*</span>Kategorijo EEA z zvezdico je določil informativni približek. 
                Kadar meritev posameznega onesnaževala manjka, 
                uporabimo razpoložljive meritve drugih ljubljanskih postaj oziroma njihovo povprečje. 
                Dejanske koncentracije se lahko razlikujejo.<strong> Informativni približki so pomembni, 
                saj to, da se posameznega onesnaževala ne meri, ne pomeni, da tega onesnaževala ni. 
                Bolj verjetno je, da je le-to vsaj okvirno primerljivo z vrednostmi drugih merilnih postaj v Ljubljani,
                kot pa da bi bilo odsotno.</strong>
                <?php endif; ?>
                <br>
                "n.p." pomeni, da ARSO meritve ni objavil ali pa onesnaževala sploh ne meri.
                <br>
                VIR MERITEV: ARSO · µg/m³. Meritve niso prikaz vsesplošne kvalitete zraka v Ljubljani. 
            </p>

            <?php
            // Barve prevzamemo iz iste EEA konfiguracije kot graf.
            $eeaLegendCategories = array_values(EU_AQI_CATEGORIES);
            $eeaLegendRows = [
                [0, 'DOBRO', 'Nizka onesnaženost zraka.'],
                [1, 'ZADOVOLJIVO', 'Sprejemljiva onesnaženost zraka.'],
                [2, 'ZMERNO', 'Povečana onesnaženost zraka.'],
                [3, 'SLABO / ZELO SLABO / IZREDNO SLABO', 'Visoka do izredno visoka onesnaženost zraka.'],
            ];
            ?>

 
<div class="current-air-info">

    <div class="current-air-info-column">

        <div
            class="current-air-legend chart-reference-legend"
            aria-label="Legenda kategorij kakovosti zraka"
        >
            <?php foreach ($eeaLegendRows as [$categoryIndex, $categoryName, $categoryDescription]): ?>
                <?php
                $categoryData = $eeaLegendCategories[$categoryIndex] ?? [];
                $categoryColor = is_array($categoryData)
                    ? ($categoryData['color'] ?? '')
                    : '';

                // Če konfiguracija ne vsebuje barve, ne izmišljamo nove.
                $categoryColor = is_string($categoryColor) &&
                    preg_match('/^#[0-9a-fA-F]{3,8}$/', $categoryColor)
                    ? $categoryColor
                    : '';
                ?>

                <div class="chart-reference-legend-row">
                    <span
                        class="chart-reference-legend-color"
                        aria-hidden="true"
                        <?= $categoryColor !== ''
                            ? 'style="background-color: ' .
                              htmlspecialchars($categoryColor, ENT_QUOTES, 'UTF-8') .
                              '"'
                            : '' ?>
                    ></span>

                    <div class="chart-reference-legend-content">
                        <span class="chart-reference-legend-name">
                            <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <span class="chart-reference-legend-description">
                            <?= htmlspecialchars($categoryDescription, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>

    </div>

    <div class="current-air-info-column">

        <div class="pollutant-info-list">

            <div class="pollutant-info-row">
                <strong>PM2.5</strong>
                <span>Zelo drobni delci iz prometa, kurjenja in drugih virov, ki prodrejo globoko v pljuča, lahko prehajajo v krvni obtok ter prispevajo k vnetjem in boleznim srca in ožilja.</span>
            </div>

            <div class="pollutant-info-row">
                <strong>PM10</strong>
                <span>Večji delci iz cestnega prahu, prometa in kurjenja, ki dražijo dihala, povzročajo vnetne odzive in poslabšujejo bolezni dihal.</span>
            </div>

            <div class="pollutant-info-row">
                <strong>O₃</strong>
                <span>Prizemni ozon, ki nastaja ob sončni svetlobi iz drugih onesnaževal ter povzroča vnetje dihalnih poti, otežuje dihanje in poslabšuje astmo.</span>
            </div>

            <div class="pollutant-info-row">
                <strong>NO₂</strong>
                <span>Plin predvsem iz prometa in drugih procesov zgorevanja, ki povzroča vnetje dihalnih poti, poslabšuje astmo in prispeva k nastajanju prizemnega ozona.</span>
            </div>

        </div>

    </div>

</div>
 
<?php /*

            <p class="health-disclaimer">
                Čim manj ste izpostavljeni onesnaženemu zraku, tem bolje za zdravje. 
                Zato kategorij ne interpretirajte kot strogo ločnico med »zdravim« in »nezdravim« zrakom. 
                Tudi nižje koncentracije niso zdravju koristne, 
                dolgoročni učinki pa se lahko razvijajo brez očitnih simptomov.
                    <br>
                Še posebej za športne aktivnosti je smiselno izbrati območja s čistejšim zrakom, tudi zunaj mesta, 
                ali notranje prostore s filtriranim zrakom.
                    <br>
                Če je zunaj trenutno visoka koncentracija PM2.5, je bolje počakati 
                s prezračevanjem notranjih prostorov. 
            </p>
*/ ?>


</section>


        <!-- ARHIV MERITEV -->

        <section
            id="grafi"
            class="content-section"
            aria-labelledby="grafi-title"
        >
            <div class="section-title">

                <h2 id="grafi-title">
                    POGLED NAZAJ
                </h2>

            </div>

            <div class="chart-controls">

                <label for="pollutant">
                    ONESNAŽEVALO

                    <select id="pollutant">
                        <?php foreach (
                            HISTORY_CHART_POLLUTANTS as
                            $pollutantKey => $pollutantConfig
                        ): ?>
                            <option
                                value="<?= htmlspecialchars(
                                    $pollutantKey,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                                <?= htmlspecialchars(
                                    $pollutantConfig['label'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div
                    class="range-buttons"
                    role="group"
                    aria-label="Časovno obdobje"
                >

                    <button
                        type="button"
                        data-days="24h"
                        class="active"
                        aria-pressed="true"
                    >
                        24 UR
                    </button>

                    <button
                        type="button"
                        data-days="7"
                        aria-pressed="false"
                    >
                        7 DNI
                    </button>

                    <button
                        type="button"
                        data-days="14"
                        aria-pressed="false"
                    >
                        14 DNI
                    </button>

                    <button
                        type="button"
                        data-days="30"
                        aria-pressed="false"
                    >
                        30 DNI
                    </button>

                    <button
                        type="button"
                        data-days="90"
                        aria-pressed="false"
                    >
                        90 DNI
                    </button>

                    <button
                        type="button"
                        data-days="180"
                        aria-pressed="false"
                    >
                        180 DNI
                    </button>

                    <button
                        type="button"
                        data-days="360"
                        aria-pressed="false"
                    >
                        360 DNI
                    </button>

                </div>

            </div>

            <div class="chart-wrap">

                <canvas
                    id="historyChart"
                    role="img"
                    aria-label="Graf zgodovinskih meritev"
                ></canvas>

                <p id="chart-fallback" hidden>
                    Graf ni na voljo.
                    Preveri povezavo ali datoteko
                    dailyHistory.csv.
                </p>

            </div>

            <!-- REFERENČNA OZNAKA IN INFORMACIJE -->

            <div
                id="chart-reference"
                class="chart-reference"
                hidden
            >
                <div class="chart-reference-heading">

                    <span
                        id="chart-reference-symbol"
                        class="chart-reference-symbol"
                        aria-hidden="true"
                    ></span>

                    <span id="chart-reference-label"></span>

                    <button
                        id="chart-reference-info"
                        class="chart-reference-info-button"
                        type="button"
                        aria-label="Informacije o referenčnih vrednostih grafa"
                        aria-expanded="false"
                        aria-controls="chart-reference-popover"
                    >
                        ⓘ
                    </button>

                </div>

                <div
                    id="chart-reference-popover"
                    class="chart-reference-popover"
                    role="region"
                    aria-label="Pojasnilo referenčnih vrednosti"
                    hidden
                >
                    <button
                        id="chart-reference-close"
                        class="chart-reference-close"
                        type="button"
                        aria-label="Zapri pojasnilo"
                    >
                        ×
                    </button>

                    <div id="chart-reference-description"></div>
                </div>

            </div>

            <p
                class="source-note"
                id="chart-note"
            >
                VIR MERITEV: ARSO.
                Prikazana obdobja povprečenja so odvisna
                od onesnaževala.
            </p>

        </section>

        <!-- DOLGOROČNO STANJE -->

        <?php
        require __DIR__ . '/../src/sections/longTermAir.php';
        ?>

        <!-- IZBOLJŠAVE -->

        <section
            id="izboljsave"
            class="content-section"
            aria-labelledby="izboljsave-title"
        >
            <div class="section-title">

                <h2 id="izboljsave-title">
                    KAKO BI LJUBLJANA LAŽJE ZADIHALA
                </h2>

            </div>

            <?php
            $solutionsPath =
                __DIR__ . '/../src/sections/solutions.php';

            if (is_file($solutionsPath)) {
                require $solutionsPath;
            } else {
                echo '<p>Predlogi rešitev bodo dodani kmalu.</p>';
            }
            ?>

        </section>

    </main>

    
<footer class="site-footer">

    <strong>Avtorica: Nuša Jazbinšek</strong>

    <div class="footer-sources">
        VIRI:
        <a href="https://www.arso.gov.si/zrak/kakovost%20zraka/" target="_blank" rel="noopener noreferrer">ARSO</a>,
        <a href="https://airindex.eea.europa.eu/" target="_blank" rel="noopener noreferrer">EEA</a>,
        <a href="https://www.who.int/health-topics/air-pollution" target="_blank" rel="noopener noreferrer">WHO</a>
    </div>

    <span>
        © <?= date('Y') ?>
    </span>

    </footer>


</div>

<!-- PODATKI ZA JAVASCRIPT -->

<script
    id="daily-data"
    type="application/json"
><?= $dailyJson ?></script>

<script
    id="hourly-data"
    type="application/json"
><?= $hourlyJson ?></script>

<script
    id="chart-reference-data"
    type="application/json"
><?= $chartReferencesJson ?></script>

<script
    src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"
    defer
></script>

<script
    src="/assets/script.js"
    defer
></script>

</body>
</html>
