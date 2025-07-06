<?php
use Phpml\Regression\SVR;
use Phpml\SupportVectorMachine\Kernel;

function predictWeather($day, $month, $site_id) 
{
$sites = [
1 => ['file' => 'wynyard_weather.csv', 'name' => 'Wynyard'],
2 => ['file' => 'launceston_weather.csv', 'name' => 'Launceston'],
3 => ['file' => 'smithton_weather.csv', 'name' => 'Smithton'],
4 => ['file' => 'hobart_weather.csv', 'name' => 'Hobart'],
5 => ['file' => 'campania_weather.csv', 'name' => 'Campania'],
];

$site = $sites[$site_id];
$csvFile = $_SERVER['DOCUMENT_ROOT'] . '/server/data/' . $site['file'];

if (!file_exists($csvFile)) 
{
throw new Exception("CSV file not found: $csvFile");
}

$lines = file($csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if (!$lines || count($lines) === 0) 
{
throw new Exception("CSV file is empty or unreadable: $csvFile");
}

$csv = array_map('str_getcsv', $lines);
$header = array_map('strtolower', str_replace(' ', '_', $csv[0])); 
$data = array_slice($csv, 1);

$datetimeIndex = array_search('date_time', $header);
$tempIndex = array_search('temperature', $header);
$humIndex = array_search('relative_humidity', $header);

if ($datetimeIndex === false || $tempIndex === false || $humIndex === false) 
{
throw new Exception("Required columns not found in CSV header.");
}

$X = [];
$y_temp = [];
$y_hum = [];

foreach ($data as $row) {
if (!isset($row[$datetimeIndex], $row[$tempIndex], $row[$humIndex])) 
{
continue;
}

$dt = strtotime($row[$datetimeIndex]);
if (!$dt) continue;

$d = getdate($dt);
if ($d['mday'] == $day && $d['mon'] == $month) {
$minutes = $d['hours'] * 60 + $d['minutes'];
$X[] = [$minutes];
$y_temp[] = floatval($row[$tempIndex]);
$y_hum[] = floatval($row[$humIndex]);
}
}

if (count($X) < 30) {
throw new Exception("Not enough data points for training");
}

$model_temp = new SVR(Kernel::LINEAR);
$model_temp->train($X, $y_temp);

$model_hum = new SVR(Kernel::LINEAR);
$model_hum->train($X, $y_hum);

$predicted_temps = [];
$predicted_hums = [];

for ($t = 0; $t <= 1430; $t += 30) 
{
$predicted_temps[] = $model_temp->predict([$t]);
$predicted_hums[] = $model_hum->predict([$t]);
}

return [
'location' => $site['name'],
'prediction' => [
'min_temp' => round(min($predicted_temps), 1),
'max_temp' => round(max($predicted_temps), 1),
'min_humidity' => round(min($predicted_hums), 1),
'max_humidity' => round(max($predicted_hums), 1),
]
];
}
