<?php
require 'config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$orderID = $input['orderID'] ?? null;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$paypal_api_base/v1/oauth2/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $paypal_client_id . ":" . $paypal_secret_key);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

$response = curl_exec($ch);
$data = json_decode($response, true);
$access_token = $data['access_token'] ?? null;

curl_setopt($ch, CURLOPT_URL, "$paypal_api_base/v2/checkout/orders/$orderID/capture");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, "");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Content-Type: application/json",
  "Authorization: Bearer $access_token"
]);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>