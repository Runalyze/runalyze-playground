<?php

$LOAD_JS = false;
$LOAD_HTML = false;
require_once '../../../bootstrap.php';

$PDO = DB::getInstance();

$where = isset($_GET['id']) ? 'WHERE `accountid`='.(int)$_GET['id'] : '';

$Statement = $PDO->query(
	'SELECT FROM_UNIXTIME(time,\'%Y-%m-%d\') as date, COUNT(*) as activities, SUM(distance) as distance, SUM(s) as s FROM runalyze_training '.$where.' GROUP BY date'
);
//$Data = $Statement->fetchAll();
while ($result = $Statement->fetch()) {
	$Data[$result['date']] = $result;
}


echo json_encode($Data);
