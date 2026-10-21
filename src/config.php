<?php

/**
 * zrakLJ — konfiguracija aplikacije
 *
 * Vse koncentracije so v µg/m³.
 *
 * EEA: kratkoročne kategorije kakovosti zraka.
 * WHO: zdravstvene smernice po obdobjih povprečenja.
 *
 * Pragovi in smernice niso zakonske mejne vrednosti.
 */

// ==========================================
// 1. ARSO — VIRI PODATKOV
// ==========================================

const ARSO_HOURLY_URL =
    'https://www.arso.gov.si/xml/zrak/ones_zrak_urni_podatki_zadnji.xml';

const ARSO_DAILY_URL =
    'https://www.arso.gov.si/xml/zrak/ones_zrak_dnevni_podatki_zadnji.xml';


// ==========================================
// 2. MERILNE POSTAJE
// ==========================================

const AIR_STATIONS = [
    'LJ Bežigrad',
    'LJ Celovška',
    'LJ Vič'
];

const LJUBLJANA_STATIONS = AIR_STATIONS;

const AREA_TO_STATION = [
    'bezigrad' => 'LJ Bežigrad',
    'sis'      => 'LJ Celovška',
    'vic'      => 'LJ Vič'
];


// ==========================================
// 3. ARSO — XML POLJA
// ==========================================

const AIR_FIELDS_HOURLY = [
    'PM2.5' => 'pm2.5',
    'PM10'  => 'pm10',
    'OZON'  => 'o3',
    'SO2'   => 'so2',
    'NO2'   => 'no2'
];

/**
 * Dnevna statistika ni pri vseh onesnaževalih
 * dnevno povprečje.
 *
 * NO2 je v izvornem ARSO dnevnem XML
 * še vedno najvišja urna koncentracija.
 * Za zgodovinski graf dnevno povprečje NO2
 * posebej izračunamo iz urnega CSV v model.php.
 */
const AIR_FIELDS_DAILY = [
    'PM2.5' => 'pm2.5_dnevna',
    'PM10'  => 'pm10_dnevna',
    'OZON'  => 'o3_max_8urna',
    'SO2'   => 'so2_dnevna',
    'NO2'   => 'no2_max_urna'
];

const ARSO_HOURLY_FIELDS = AIR_FIELDS_HOURLY;
const ARSO_DAILY_FIELDS = AIR_FIELDS_DAILY;


// ==========================================
// 4. EEA — EVROPSKI INDEKS
// ==========================================

/**
 * Pet zgornjih mej za šest kategorij.
 *
 * Vrstni red:
 * 0 Dobro
 * 1 Zadovoljivo
 * 2 Zmerno
 * 3 Slabo
 * 4 Zelo slabo
 * 5 Izredno slabo
 *
 * Pri grafu se te vrednosti uporabljajo
 * samo za urne podatke.
 */
const EU_AQI_THRESHOLDS = [
    'PM2.5' => [5, 15, 50, 90, 140],
    'PM10'  => [15, 45, 120, 195, 270],
    'NO2'   => [10, 25, 60, 100, 150],
    'OZON'  => [60, 100, 120, 160, 180],
    'SO2'   => [20, 40, 125, 190, 275]
];

const EU_AQI_CATEGORIES = [
    [
        'label' => 'Dobro',
        'color' => '#8CCDA8'
    ],
    [
        'label' => 'Zadovoljivo',
        'color' => '#ADADAD'
    ],
    [
        'label' => 'Zmerno',
        'color' => '#F1CE75'
    ],
    [
        'label' => 'Slabo',
        'color' => '#E99679'
    ],
    [
        'label' => 'Zelo slabo',
        'color' => '#E99679'
    ],
    [
        'label' => 'Izredno slabo',
        'color' => '#E99679'
    ]
];

const EU_AQI_UNKNOWN = [
    'label' => 'Ni podatkov',
    'color' => '#D8D6CE'
];


// ==========================================
// 5. WHO — ZDRAVSTVENE SMERNICE
// ==========================================

/**
 * WHO Air Quality Guidelines 2021.
 *
 * null pomeni, da WHO 2021 ne določa
 * nove smernice za navedeno obdobje.
 */
const WHO_AQG_2021 = [
    'PM2.5' => [
        'annual' => 5,
        '24h' => 15
    ],

    'PM10' => [
        'annual' => 15,
        '24h' => 45
    ],

    'NO2' => [
        'annual' => 10,
        '24h' => 25,
        '1h' => null
    ],

    'OZON' => [
        '8h' => 100,
        'peak_season' => 60
    ],

    'SO2' => [
        '24h' => 40,
        '1h' => null
    ]
];

/**
 * Združljivost z obstoječo kodo.
 * Ne uporabljamo za ocenjevanje urnega zraka.
 */
const MAX_VALUES_WHO = [
    'PM2.5' => 15,
    'PM10'  => 45,
    'OZON'  => 100,
    'SO2'   => 40,
    'NO2'   => 25
];


// ==========================================
// 6. NASTAVITVE ZGODOVINSKEGA GRAFA
// ==========================================

/**
 * Samo onesnaževala, prikazana v arhivu.
 * SO2 ostane v podatkovnem modelu,
 * vendar ga graf ne prikazuje.
 *
 * 'daily_statistic' določa vrsto podatka
 * v dnevnem grafu.
 *
 * PM2.5 in PM10: dnevno povprečje ARSO.
 * OZON: največje 8-urno povprečje ARSO.
 * NO2: dnevno povprečje iz urnega CSV.
 *
 * 'who_period' določa primerljivo smernico.
 * 'who_source' označuje izdajo smernic WHO.
 */
const HISTORY_CHART_POLLUTANTS = [
    'PM2.5' => [
        'label' => 'PM2.5',
        'daily_statistic' => '24h',
        'who_period' => '24h',
        'who_source' => '2021'
    ],

    'PM10' => [
        'label' => 'PM10',
        'daily_statistic' => '24h',
        'who_period' => '24h',
        'who_source' => '2021'
    ],

    'OZON' => [
        'label' => 'O₃',
        'daily_statistic' => 'max_8h',
        'who_period' => '8h',
        'who_source' => '2021'
    ],

    'NO2' => [
        'label' => 'NO₂',
        'daily_statistic' => '24h',
        'who_period' => '24h',
        'who_source' => '2021'
    ]
];


// ==========================================
// 7. OPISI ONESNAŽEVAL
// ==========================================

const DESCRIPTIONS = [
    'PM2.5' =>
        'Drobni delci s premerom do 2,5 mikrometra. ' .
        'Zaradi majhnosti lahko prodrejo globoko ' .
        'v dihala in vplivajo na zdravje.',

    'PM10' =>
        'Delci s premerom do 10 mikrometrov. ' .
        'Povezani so s prometom, kurjenjem, ' .
        'industrijo in dvigovanjem prahu.',

    'OZON' =>
        'Prizemni ozon (O3) nastaja pri ' .
        'fotokemičnih reakcijah ob sončni svetlobi. ' .
        'Povišane koncentracije lahko dražijo dihala.',

    'SO2' =>
        'Žveplov dioksid (SO2) nastaja predvsem ' .
        'pri zgorevanju goriv, ki vsebujejo žveplo.',

    'NO2' =>
        'Dušikov dioksid (NO2) nastaja predvsem ' .
        'pri prometu in drugih procesih zgorevanja.'
];