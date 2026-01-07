<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

header("Access-Control-Allow-Origin: *");   

$API_KEY = $_ENV["WEATHER_API_KEY"];

$city = $_GET['city'] ?? null;
$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;
$type = $_GET['type'] ?? 'current';
$layer = $_GET['layer'] ?? 'precipitation_new';
$tm = $_GET['tm'] ?? null;

if ($type === 'tile') {
    $allowedLayers = ['clouds_new', 'precipitation_new'];
    if (!in_array($layer, $allowedLayers, true)) {
        http_response_code(400);
        echo 'Invalid layer';
        exit;
    }

    $z = (int)($_GET['z'] ?? 0);
    $x = (int)($_GET['x'] ?? 0);
    $y = (int)($_GET['y'] ?? 0);

    $tileUrl = "https://tile.openweathermap.org/map/{$layer}/{$z}/{$x}/{$y}.png?appid={$API_KEY}";
    if ($tm) {
        $tileUrl .= "&tm=" . urlencode($tm);
    }

    $tile = file_get_contents($tileUrl);
    if ($tile === false) {
        http_response_code(500);
        echo 'Error contacting weather API';
        exit;
    }

    header("Content-Type: image/png");
    echo $tile;
    exit;
}

if ($type === 'onecall') {
    if (!$lat || !$lon) {
        http_response_code(400);
        echo json_encode(["error" => "Latitude and longitude required"]);
        exit;
    }

    $url = "https://api.openweathermap.org/data/3.0/onecall?lat={$lat}&lon={$lon}&appid={$API_KEY}&units=metric&exclude=minutely,daily,alerts";
    $response = file_get_contents($url);
    if ($response === false) {
        http_response_code(500);
        echo json_encode(["error" => "Error contacting weather API"]);
        exit;
    }

    header("Content-Type: application/json");
    echo $response;
    exit;
}

header("Content-Type: application/json");
$endpoint = ($type === 'forecast') ? 'forecast' : 'weather';

if ($city) {
    $cityParam = urlencode($city);
    $url = "https://api.openweathermap.org/data/2.5/{$endpoint}?q={$cityParam}&appid={$API_KEY}&units=metric";
} elseif ($lat && $lon) {
    $url = "https://api.openweathermap.org/data/2.5/{$endpoint}?lat={$lat}&lon={$lon}&appid={$API_KEY}&units=metric";
} else {
    echo json_encode(["error" => "No city or location provided"]);
    http_response_code(400);
    exit;
}

$response = file_get_contents($url);
if ($response === false) {
    http_response_code(500);
    echo json_encode(["error" => "Error contacting weather API"]);
    exit;
}

echo $response;
?>
