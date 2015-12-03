<?php

$LOAD_JS = false;
$LOAD_HTML = false;
require_once '../../../bootstrap.php';

$PDO = DB::getInstance();

$where = isset($_GET['id']) ? 'WHERE `activityid`='.(int)$_GET['id'] : '';

$result = $PDO->query(
	'SELECT * FROM `'.PREFIX.'trackdata` '.$where.' ORDER BY `activityid` DESC LIMIT 1'
);

$data = $result->fetch(PDO::FETCH_ASSOC);
$data['cadence'] = explode('|', $data['cadence']);
$data['distance'] = explode('|', $data['distance']);
$data['time'] = explode('|', $data['time']);
$data['groundcontact'] = explode('|', $data['groundcontact']);
$data['groundcontact_balance'] = explode('|', $data['groundcontact_balance']);
$data['vertical_oscillation'] = explode('|', $data['vertical_oscillation']);
$data['temperature'] = explode('|', $data['temperature']);
$data['heartrate'] = explode('|', $data['heartrate']);
$data['power'] = explode('|', $data['power']);

//print_r($data);
echo json_encode($data);
