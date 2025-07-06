<?php
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) 
{
echo json_encode(["error" => "Invalid JSON input"]);
exit;
}

$day = $data["day"];
$month = $data["month"];
$site_id = $data["site_id"];

$xml = new SimpleXMLElement('<request></request>');
$xml->addChild('day', $day);
$xml->addChild('month', $month);
$xml->addChild('site_id', $site_id);
$xml->addChild('timestamp', date('c'));

$xmlPath = __DIR__ . '/../xml/last_request.xml';

if ($xml->asXML($xmlPath)) 
{
echo json_encode(["status" => "saved"]);
} 
else 
{
echo json_encode(["error" => "Failed to save XML"]);
}
?>
