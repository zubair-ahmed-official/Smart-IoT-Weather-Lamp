<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/server/models/predict_model.php';

// Load last request from XML
$xmlFile = $_SERVER['DOCUMENT_ROOT'] . '/server/xml/last_request.xml';
if (!file_exists($xmlFile)) die("No request data found.");

$xml = simplexml_load_file($xmlFile);
$day = (int)$xml->day;
$month = (int)$xml->month;
$site_id = (int)$xml->site_id;

// Get prediction and raw data
$result = predictWeather($day, $month, $site_id);
$siteName = $result['location'];
$prediction = $result['prediction'];

// CSV path
$siteFiles = [
    1 => 'wynyard_weather.csv',
    2 => 'launceston_weather.csv',
    3 => 'smithton_weather.csv',
    4 => 'hobart_weather.csv',
    5 => 'campania_weather.csv',
];

$csvFile = $_SERVER['DOCUMENT_ROOT'] . "/server/data/" . $siteFiles[$site_id];
$lines = file($csvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$csv = array_map('str_getcsv', $lines);
$header = array_map('strtolower', str_replace(' ', '_', $csv[0]));
$data = array_slice($csv, 1);

$dtIndex = array_search('date_time', $header);
$tempIndex = array_search('temperature', $header);
$humIndex = array_search('relative_humidity', $header);

// Aggregate data
$slots = [];

foreach ($data as $row) {
    $dt = strtotime($row[$dtIndex]);
    $d = getdate($dt);
    if ($d['mday'] == $day && $d['mon'] == $month) {
        $slot = sprintf("%02d:%02d", $d['hours'], $d['minutes']);
        if (!isset($slots[$slot])) {
            $slots[$slot] = ['temps' => [], 'hums' => []];
        }
        $slots[$slot]['temps'][] = floatval($row[$tempIndex]);
        $slots[$slot]['hums'][] = floatval($row[$humIndex]);
    }
}

$temperatureData = [];
$humidityData = [];
ksort($slots); // sort by time

foreach ($slots as $time => $values) {
    $avgTemp = array_sum($values['temps']) / count($values['temps']);
    $avgHum = array_sum($values['hums']) / count($values['hums']);
    $temperatureData[] = ['label' => $time, 'y' => round($avgTemp, 1)];
    $humidityData[] = ['label' => $time, 'y' => round($avgHum, 1)];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Prediction - <?= htmlspecialchars($siteName) ?></title>
    <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
</head>
<body style="font-family: Arial">

<h2>Weather Prediction for <?= htmlspecialchars($siteName) ?> (<?= $day ?>/<?= $month ?>/2022)</h2>

<div id="tempChart" style="height: 370px; width: 100%; margin-bottom: 30px;"></div>
<p><strong>Predicted Temperature:</strong> <?= $prediction['min_temp'] ?>°C - <?= $prediction['max_temp'] ?>°C</p>

<div id="humChart" style="height: 370px; width: 100%;"></div>
<p><strong>Predicted Humidity:</strong> <?= $prediction['min_humidity'] ?>% - <?= $prediction['max_humidity'] ?>%</p>

<script>
window.onload = function () {
    const tempChart = new CanvasJS.Chart("tempChart", {
        animationEnabled: true,
        theme: "light2",
        title: {
            text: "Average Temperature - <?= htmlspecialchars($siteName) ?>"
        },
        axisY: {
            title: "Temperature (°C)"
        },
        data: [{
            type: "line",
            dataPoints: <?= json_encode($temperatureData, JSON_NUMERIC_CHECK) ?>
        }]
    });
    tempChart.render();

    const humChart = new CanvasJS.Chart("humChart", {
        animationEnabled: true,
        theme: "light2",
        title: {
            text: "Average Humidity - <?= htmlspecialchars($siteName) ?>"
        },
        axisY: {
            title: "Humidity (%)"
        },
        data: [{
            type: "line",
            dataPoints: <?= json_encode($humidityData, JSON_NUMERIC_CHECK) ?>
        }]
    });
    humChart.render();
}
</script>

</body>
</html>
