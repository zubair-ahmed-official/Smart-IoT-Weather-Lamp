<?php

	header("Content-Type: application/json");


	$raw_xml = simplexml_load_file("tempData.xml");
	//var_dump($raw_xml);

	$new_json = json_encode($raw_xml);
	echo $new_json;

?>
