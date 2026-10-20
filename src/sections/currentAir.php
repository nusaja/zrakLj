<?php
/** Kartice trenutnih urnih meritev — vse tri postaje so vedno vidne. */
$pollutantLabels = [
    'PM2.5' => 'PM2.5',
    'PM10' => 'PM10',
    'OZON' => 'O₃',
    'NO2' => 'NO₂',
];
$escapeAir = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

foreach ($AREA_TO_STATION as $areaKey => $stationName):
    $station = $hourly_data[$stationName] ?? null;
    $values = $station['values'] ?? [];
    $quality = $station['quality'] ?? getAirQualityCategory($values);
    $mainPollutant = $quality['pollutant'] ?? null;
    $mainLabel = $pollutantLabels[$mainPollutant] ?? $mainPollutant;
    $mainValue = $mainPollutant !== null ? ($values[$mainPollutant] ?? null) : null;
?>

<article
    data-area="<?= $escapeAir($areaKey) ?>"
    class="station-card"
    style="--quality-color: <?= $escapeAir($quality['color']) ?>"
    aria-label="<?= $escapeAir($stationName . ': ' . $quality['label']) ?>"
>

<div class="station-card-header">
    <h3><?= $escapeAir(str_replace('LJ ', '', $stationName)) ?></h3>

    <span class="nearest-marker" title="Najbližja merilna postaja" aria-label="Najbližja merilna postaja">
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

    <div class="station-quality"><?= $escapeAir($quality['label']) ?></div>

    
    <div class="station-values">
    <?php foreach ($pollutantLabels as $key => $label): ?>
        <?php
            $value = $values[$key] ?? null;
            $isMain = $value !== null && $mainPollutant === $key;
        ?>

        <div class="station-value<?= $isMain ? ' is-main' : '' ?>">
            <span>
                <?= $escapeAir($label) ?>

                <?php if ($isMain): ?>
                    <span class="pollutant-indicator" aria-label="Onesnaževalo, ki določa kakovost zraka">●</span>
                <?php endif; ?>
            </span>

            <strong>
                <?= $value === null
                    ? 'n.p.'
                    : $escapeAir(number_format((float) $value, 1, ',', '')) ?>
            </strong>
        </div>
    <?php endforeach; ?>
</div>
</article>
<?php endforeach; ?>
