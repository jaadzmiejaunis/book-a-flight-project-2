<?php
if (!isset($_GET['q'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing search query"]);
    exit;
}

$q = urlencode($_GET['q']);
$url = "https://nominatim.openstreetmap.org/search?format=jsonv2&q={$q}&limit=1";

// Request via cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "SierraFlight/1.0 (akasukma0@gmail.com)");
$response = curl_exec($ch);
curl_close($ch);

header('Content-Type: application/json');
echo $response;
?>