
<?php

// ==========================================
// 03 / DOLGOROČNO STANJE
// ==========================================

// Informativna letna povprečja za leto 2025.
// Izračunana iz mesečnih povprečij ARSO,
// uteženih s številom koledarskih dni.
//
// Vrednosti so zaokrožene na cela števila.
// Enota: µg/m³.
//
// PM2.5: Ljubljana Bežigrad.
// PM10: Ljubljana Bežigrad, Celovška, Vič.
// NO2 za zdaj ni vključen.
//
// EU 2025: letne mejne vrednosti, veljavne 2025.
// EU 2030: nove letne mejne vrednosti po
// Direktivi (EU) 2024/2881.
// WHO: zdravstvene smernice iz leta 2021.

$annualData = [
    'PM2.5' => [
        'label' => 'PM2.5',
        'stations' => [
            'BEŽIGRAD' => 12,
        ],
        'eu2025' => 25,
        'eu2030' => 10,
        'who' => 5,
    ],

    'PM10' => [
        'label' => 'PM10',
        'stations' => [
            'BEŽIGRAD' => 20,
            'CELOVŠKA' => 19,
            'VIČ' => 19,
        ],
        'eu2025' => 40,
        'eu2030' => 20,
        'who' => 15,
    ],
];

$annualJson = json_encode(
    $annualData,
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
);

if ($annualJson === false) {
    $annualJson = '{}';
}

?>

<section
    id="dolgorocno"
    class="content-section long-term-section"
    aria-labelledby="dolgorocno-title"
>
    <div class="section-title">
        <h2 id="dolgorocno-title">
            DOLGOROČNA IZPOSTAVLJENOST ZRAKU V LJUBLJANI
        </h2>
    </div>

    <p class="long-term-intro">
        Kakovost zraka ene ure, enega dne, enega krajšega obdobja je šele del celotne slike.
        Na zdravje v veliki meri vpliva tudi dolgotrajno vdihavanje
        onesnaženega zraka dan za dnem, mesece in leta.
        <br><br>
        Zato so zgovorna tudi letna povprečja koncentracij, 
        ki jih tukaj primerjamo z zakonskimi letnimi mejnimi vrednostmi EU, 
        veljavnimi tudi v Sloveniji, novimi strožjimi mejnimi vrednostmi, 
        ki bodo veljale od leta 2030, ter zdravstvenimi smernicami Svetovne zdravstvene organizacije (WHO).
    </p>

    <div class="chart-controls">
        <label for="annual-pollutant">
            ONESNAŽEVALO

            <select id="annual-pollutant">
                <?php foreach ($annualData as $key => $data): ?>
                    <option
                        value="<?= htmlspecialchars(
                            $key,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        <?= $key === 'PM2.5' ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars(
                            $data['label'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <p class="long-term-period">
        LETNA POVPREČJA 2025 NAPRAM MEJNIM VREDNOSTIM
    </p>

    <div
        class="chart-wrap long-term-chart-wrap"
        style="height: 360px;"
    >
        <canvas
            id="annualChart"
            role="img"
            aria-label="Vodoravni graf primerjave letnih koncentracij PM2.5 in PM10 z mejnimi vrednostmi EU 2025 in 2030 ter smernico WHO"
        ></canvas>
    </div>

    <p
        id="annual-data-status"
        class="source-note"
        aria-live="polite"
    ></p>

    <p class="source-note">
        Prikazana letna povprečja so informativni
        izračuni na podlagi mesečnih povprečnih
        koncentracij ARSO za leto 2025, uteženih s številom
        koledarskih dni. Zaradi zaokroževanja in
        morebitnih manjkajočih meritev se lahko
        razlikujejo od uradnih letnih povprečij.
        <br>
        VIR MERITEV: ARSO
    </p>

    <script
        id="annual-limit-data"
        type="application/json"
    ><?= $annualJson ?></script>

</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const canvas =
        document.getElementById('annualChart');

    const pollutantSelect =
        document.getElementById('annual-pollutant');

    const dataElement =
        document.getElementById('annual-limit-data');

    const statusElement =
        document.getElementById('annual-data-status');

    if (
        !canvas ||
        !pollutantSelect ||
        !dataElement ||
        typeof Chart === 'undefined'
    ) {
        return;
    }

    let annualData;

    try {
        annualData = JSON.parse(dataElement.textContent);
    } catch (error) {
        console.error(
            'Napaka pri branju letnih podatkov:',
            error
        );
        return;
    }

    const getChartData = pollutant => {
        const selected = annualData[pollutant];

        if (!selected) {
            return null;
        }

        const stationEntries =
            Object.entries(selected.stations);

        return {
                    labels: window.innerWidth <= 600
            ? [
                ...stationEntries.map(([name]) => ({
                    'BEŽIGRAD': 'BEŽ.',
                    'CELOVŠKA': 'CEL.',
                    'VIČ': 'VIČ'
                })[name] || name),
                'EU 25',
                'EU 30',
                'WHO'
            ]
            : [
                ...stationEntries.map(([name]) => name),
                'EU/SI 2025',
                'EU/SI 2030',
                'WHO'
            ],

            values: [
                ...stationEntries.map(([, value]) => value),
                selected.eu2025,
                selected.eu2030,
                selected.who
            ],

            colors: [
                ...stationEntries.map(() => '#F1CE75'),
                '#292929',
                '#888888',
                '#8CCDA8'
            ],

            stationCount: stationEntries.length
        };
    };

    const initial =
        getChartData(pollutantSelect.value);

    if (!initial) {
        return;
    }

    const chart = new Chart(canvas, {
        type: 'bar',

        data: {
            labels: initial.labels,

            datasets: [{
                data: initial.values,
                backgroundColor: initial.colors,
                borderWidth: 0,
                borderRadius: 0,
                maxBarThickness: 36,
                barPercentage: 0.75,
                categoryPercentage: 0.8
            }]
        },

        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,

            animation: {
                duration: 250
            },

            plugins: {
                legend: {
                    display: false
                },

                tooltip: {
                    callbacks: {
                        label: context =>
                            context.parsed.x + ' µg/m³'
                    }
                }
            },

            scales: {
                x: {
                    beginAtZero: true,

                    title: {
                        display: true,
                        text: 'µg/m³'
                    },

                    grid: {
                        color: 'rgba(0, 0, 0, 0.08)'
                    },

                    ticks: {
                        color: '#555',
                        precision: 0
                    }
                },

                y: {
                    grid: {
                        display: false
                    },

                    ticks: {
                        color: '#171717',
                        font: {
                            size: 11,
                            weight: 'bold'
                        }
                    }
                }
            }
        }
    });

    const updateChart = () => {
        const selected =
            getChartData(pollutantSelect.value);

        if (!selected) {
            return;
        }

        chart.data.labels = selected.labels;
        chart.data.datasets[0].data = selected.values;
        chart.data.datasets[0].backgroundColor =
            selected.colors;

        chart.update();

    };

    pollutantSelect.addEventListener(
    'change',
    updateChart
    );

    window.addEventListener('resize', updateChart);

    window.annualChart = chart;

    updateChart();
    });
</script>
