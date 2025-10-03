<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (isset($_GET['lat']) && isset($_GET['lon'])) {
    $lat = floatval($_GET['lat']);
    $lon = floatval($_GET['lon']);
    
    $url = "https://nominatim.openstreetmap.org/reverse?lat={$lat}&lon={$lon}&format=json";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SierraFlight/1.0');
    curl_setopt($ch, CURLOPT_REFERER, 'http://localhost');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        echo $response;
    } else {
        echo json_encode(['error' => 'Failed to fetch location data']);
    }
} else {
    echo json_encode(['error' => 'Missing lat/lon parameters']);
}
?>