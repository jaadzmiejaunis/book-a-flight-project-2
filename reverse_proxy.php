<?php
if (!isset($_GET['lat']) || !isset($_GET['lon'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing parameters"]);
    exit;
}

$lat = $_GET['lat'];
$lon = $_GET['lon'];

// build Nominatim URL
$url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=" . urlencode($lat) . "&lon=" . urlencode($lon) . "&addressdetails=1";

// use cURL to fetch (server-side, no CORS issue)
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "SierraFlight/1.0 (akasukma0@gmail.com)"); // Nominatim requires UA
$response = curl_exec($ch);
curl_close($ch);

// return result as JSON
header('Content-Type: application/json');
echo $response;
?>