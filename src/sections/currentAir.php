<?php

/** @var array $hourly_data */
/** Kartice trenutnih urnih meritev — vse tri postaje so vedno vidne. */

$pollutantLabels = [
    'PM2.5' => 'PM2.5',
    'PM10' => 'PM10',
    'OZON' => 'O₃',
    'NO2' => 'NO₂',
];

$escapeAir = static fn ($value): string =>
    htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// Izvornih podatkov ARSO ne spreminjamo.
// Nadomestne vrednosti uporabimo samo za določanje kategorije.

// Kopijo ustvarimo šele po pripravi testnih podatkov.
$qualityData = $hourly_data;
$estimatedValues = [];

// Preveri, ali sta meritvi iz istega urnega intervala.
$sameInterval = static function (?array $a, ?array $b): bool {
    if ($a === null || $b === null) {
        return false;
    }

    $from = $a['date_from'] ?? '';
    $to = $a['date_to'] ?? '';

    return $from !== ''
        && $to !== ''
        && $from === ($b['date_from'] ?? '')
        && $to === ($b['date_to'] ?? '');
};

// Za vsako postajo preverimo manjkajoča onesnaževala.
foreach (AREA_TO_STATION as $stationName) {
    $target = $hourly_data[$stationName] ?? null;

    if ($target === null) {
        continue;
    }

    foreach ($pollutantLabels as $pollutant => $label) {

        // Če ima postaja svojo veljavno meritev, je ne nadomeščamo.
        if (parse_air_number($target['values'][$pollutant] ?? null) !== null) {
            continue;
        }

        $availableValues = [];

        // Poiščemo meritve istega onesnaževala na drugih postajah.
        foreach (AREA_TO_STATION as $otherStationName) {
            if ($otherStationName === $stationName) {
                continue;
            }

            $otherStation = $hourly_data[$otherStationName] ?? null;

            if (!$sameInterval($target, $otherStation)) {
                continue;
            }

            $value = parse_air_number(
                $otherStation['values'][$pollutant] ?? null
            );

            if ($value !== null) {
                $availableValues[] = $value;
            }
        }

        // Če ni nobene meritve, onesnaževalo ostane brez podatka.
        if (empty($availableValues)) {
            continue;
        }

        // Ena meritev = prevzamemo jo.
        // Dve meritvi = izračunamo povprečje.
        $qualityData[$stationName]['values'][$pollutant] =
            array_sum($availableValues) / count($availableValues);

        $estimatedValues[$stationName][$pollutant] = true;
    }
}

// Kategorije izračunamo iz dejanskih in nadomestnih vrednosti.
// Pri eni sami meritvi kategorijo prikažemo le,
// če je dejansko izmerjena vrednost Zmerno ali slabša.

foreach ($qualityData as $stationName => &$station) {

    $actualValues = $hourly_data[$stationName]['values'] ?? [];

    $actualCount = 0;

    foreach ($pollutantLabels as $pollutant => $label) {
        if (parse_air_number($actualValues[$pollutant] ?? null) !== null) {
            $actualCount++;
        }
    }

    $actualQuality = getAirQualityCategory($actualValues);

    if (
        $actualCount >= 2
        || (
            $actualCount === 1
            && ($actualQuality['level'] ?? -1) >= 2
        )
    ) {
        $station['quality'] = getAirQualityCategory(
            $station['values'] ?? []
        );
    } else {
        $station['quality'] = [
            'label' => 'Ni dovolj podatkov',
            'color' => EU_AQI_UNKNOWN['color'],
            'pollutant' => null,
            'level' => null,
            'count' => $actualCount,
            'complete' => false,
        ];
    }
}

unset($station);

foreach (AREA_TO_STATION as $areaKey => $stationName):

    // Dejanske vrednosti za prikaz.
    $values = $hourly_data[$stationName]['values'] ?? [];

    // Vrednosti za določanje kategorije.
    $qualityValues = $qualityData[$stationName]['values'] ?? [];

    $quality = $qualityData[$stationName]['quality']
        ?? getAirQualityCategory($values);

    $mainPollutant = $quality['pollutant'] ?? null;
    $worstLevel = $quality['level'] ?? null;

    // Preverimo, ali katera dejanska meritev dosega
    // enako najslabšo kategorijo.
    $measuredMainPollutant = null;
    $highestPosition = -1;

    if ($worstLevel !== null) {
        foreach ($pollutantLabels as $pollutant => $label) {
            $actualValue = parse_air_number(
                $values[$pollutant] ?? null
            );

            if ($actualValue === null) {
                continue;
            }

            $actualQuality = getAirQualityCategory([
                $pollutant => $actualValue
            ]);

            if (($actualQuality['level'] ?? null) !== $worstLevel) {
                continue;
            }

            $limits = EU_AQI_THRESHOLDS[$pollutant] ?? [];

            // Spodnja meja trenutne kategorije.
            $lower = $worstLevel > 0
                ? $limits[$worstLevel - 1]
                : 0;

            // Zgornja meja trenutne kategorije.
            $upper = $limits[$worstLevel] ?? null;

            // Relativni položaj znotraj kategorije.
            $position = $upper !== null && $upper > $lower
                ? ($actualValue - $lower) / ($upper - $lower)
                : 0;

            if ($position > $highestPosition) {
                $highestPosition = $position;
                $measuredMainPollutant = $pollutant;
            }
        }
    }

    // Dejanska meritev ima prednost pri izenačenju.
    if ($measuredMainPollutant !== null) {
        $mainPollutant = $measuredMainPollutant;
    }

    // Zvezdica samo, če kategorijo določa nadomestna vrednost.
    $isQualityEstimated = $mainPollutant !== null
        && isset($estimatedValues[$stationName][$mainPollutant]);

?>

<article
    data-area="<?= $escapeAir($areaKey) ?>"
    class="station-card"
    style="--quality-color: <?= $escapeAir($quality['color']) ?>"
    aria-label="<?= $escapeAir(
        $stationName . ': ' . $quality['label']
        . ($isQualityEstimated ? ', kategorija temelji na nadomestni vrednosti' : '')
    ) ?>"
>

    <div class="station-card-header">
        <h3><?= $escapeAir(str_replace('LJ ', '', $stationName)) ?></h3>

        <span
            class="nearest-marker"
            title="Najbližja merilna postaja"
            aria-label="Najbližja merilna postaja"
        >
            <svg xmlns="http://www.w3.org/2000/svg"
                 width="20" height="20"
                 viewBox="0 0 24 24"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="1.8"
                 stroke-linecap="round"
                 stroke-linejoin="round"
                 aria-hidden="true">
                <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/>
                <circle cx="12" cy="10" r="2.5"/>
            </svg>
        </span>
    </div>

    <div class="station-quality">
        <?= $escapeAir($quality['label']) ?>

        <?php if ($isQualityEstimated): ?>
            <span class="quality-estimated-marker">*</span>
        <?php endif; ?>
    </div>

    <div class="station-values">
        <?php foreach ($pollutantLabels as $key => $label): ?>
            <?php
                $value = $values[$key] ?? null;

                // Pikica samo pri dejanski meritvi,
                // ki določa prikazano kategorijo.
                $isMain = $value !== null
                    && $mainPollutant === $key
                    && !$isQualityEstimated;
            ?>

            <div class="station-value<?= $isMain ? ' is-main' : '' ?>">
                <span>
                    <?= $escapeAir($label) ?>

                    <?php if ($isMain): ?>
                        <span
                            class="pollutant-indicator"
                            aria-label="Onesnaževalo, ki določa kakovost zraka"
                        >●</span>
                    <?php endif; ?>
                </span>

                <strong>
                    <?php if ($value === null): ?>
                        n.p.
                    <?php elseif (is_string($value)): ?>
                        <?= $escapeAir($value) ?>
                    <?php else: ?>
                        <?= $escapeAir(number_format($value, 1, ',', '')) ?>
                    <?php endif; ?>
                </strong>
            </div>
        <?php endforeach; ?>
    </div>
</article>

<?php endforeach; ?>

<?php
$showEstimatedNote = false;

foreach (AREA_TO_STATION as $stationName) {
    $quality = $qualityData[$stationName]['quality'] ?? null;
    $mainPollutant = $quality['pollutant'] ?? null;

    if ($mainPollutant === null) {
        continue;
    }

    $actualQuality = getAirQualityCategory(
        $hourly_data[$stationName]['values'] ?? []
    );

    if (
        isset($estimatedValues[$stationName][$mainPollutant])
        && ($actualQuality['level'] ?? null) !== ($quality['level'] ?? null)
    ) {
        $showEstimatedNote = true;
        break;
    }
}
?>