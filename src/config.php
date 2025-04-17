<?php

define("MAX_VALUES_WHO", [
    "PM2.5" => 15,
    "PM10" => 45,
    "OZON" => 100,
    "SO2" => 40,
    "NO2" => 200
]);

define("MAX_VALUES_SLO", [
    "PM2.5" => null,
    "PM10" => 50,
    "OZON" => 120,
    "SO2" => 125,
    "NO2" => 200
]);

define("DESCRIPTIONS", [
    "PM2.5" => "PM2.5 so fini delci v zraku, katerih premer je manjši od 2,5 mikrometra...",
    "PM10" => "PM10 so večji delci v zraku, ki prav tako vplivajo na zdravje dihal in okolje.",
    "OZON" => "Ozon (O₃) je plin, ki lahko povzroča draženje dihal in oči.",
    "SO2" => "Žveplov dioksid (SO₂) izvira predvsem iz kurjenja fosilnih goriv in industrije.",
    "NO2" => "Dušikov dioksid (NO₂) izvira predvsem iz prometa in vpliva na dihala."
]);

$AREA_TO_STATION = [
    'bezigrad' => 'LJ Bežigrad',
    'sis' => 'LJ Celovška',
    'vic' => 'LJ Vič'
];
