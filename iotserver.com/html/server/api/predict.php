<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$log_file = $_SERVER['DOCUMENT_ROOT'] . '/logs/predict-debug.log';

file_put_contents($log_file, "== predict.php STARTED ==\n", FILE_APPEND);

try {
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/server/models/predict_model.php';

$raw = file_get_contents("php://input");
file_put_contents($log_file, "Raw input: $raw\n", FILE_APPEND);
file_get_contents("http://iotserver.com/server/api/record_request.php", false, stream_context_create([
'http' => 
[
'method'  => 'POST',
'header'  => 'Content-type: application/json',
'content' => $raw
]
]));

$data = json_decode($raw, true);
file_put_contents($log_file, "Decoded JSON: " . json_encode($data) . "\n", FILE_APPEND);


if (!is_array($data)) 
{
throw new Exception("Invalid JSON input or empty body.");
}

if (!isset($data['day'], $data['month'], $data['site_id'])) {
throw new Exception("Missing required fields: " . json_encode($data));
}

$day = (int)$data['day'];
$month = (int)$data['month'];
$site_id = (int)$data['site_id'];

file_put_contents($log_file, "Calling predictWeather($day, $month, $site_id)\n", FILE_APPEND);

$result = predictWeather($day, $month, $site_id);

file_put_contents($log_file, "Prediction: " . json_encode($result) . "\n", FILE_APPEND);

header('Content-Type: application/json');
echo json_encode($result);

} catch (Exception $e) 
{
file_put_contents($log_file, "Exception: " . $e->getMessage() . "\n", FILE_APPEND);
http_response_code(500);
echo json_encode(["error" => "Server error"]);
}
