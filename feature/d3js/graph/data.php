<?php
$db = new mysqli('localhost', 'root', '', 'runalyze');

if($db->connect_errno > 0){
    die('Unable to connect to database [' . $db->connect_error . ']');
}

$result = $db->query('SELECT * FROM runalyze_training tr LEFT JOIN runalyze_trackdata ta ON tr.id=ta.activityid Limit 1');
$data = $result->fetch_assoc();
$data['cadence'] = explode('|', $data['cadence']);
$data['distance'] = explode('|', $data['distance']);
$data['time'] = explode('|', $data['time']);
$data['groundcontact'] = explode('|', $data['groundcontact']);
$data['groundcontact_balance'] = explode('|', $data['groundcontact_balance']);
$data['vertical_oscillation'] = explode('|', $data['vertical_oscillation']);
$data['vertical_ratio'] = explode('|', $data['vertical_ratio']);
$data['temperature'] = explode('|', $data['temperature']);
$data['heartrate'] = explode('|', $data['heartrate']);

//print_r($data);
echo json_encode($data);
