<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");   

$API_KEY = $_ENV["WEATHER_API_KEY"];

$city = $_GET['city'] ?? null;
$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;

if ($city) {
    $url = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$API_KEY}&units=metric";
} elseif ($lat && $lon) {
    $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$API_KEY}&units=metric";
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