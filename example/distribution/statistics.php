<?php
require_once '../../bootstrap.php';

use Runalyze\Model\Activity;
use Runalyze\Model\Trackdata;
use Runalyze\Calculation\Distribution\TimeSeries;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Distance;

$Trackdata = new Trackdata\Entity(
	DB::getInstance()->query(
		'SELECT * FROM `'.PREFIX.'trackdata` '.(isset($_GET['id']) ? 'WHERE `activityid`="'.(int)$_GET['id'].'"' : 'ORDER BY `activityid` DESC').' LIMIT 1'
	)->fetch()
);
$Activity = new Activity\Entity(
	DB::getInstance()->query(
		'SELECT * FROM `'.PREFIX.'training` WHERE `id`="'.$Trackdata->activityID().'" LIMIT 1'
	)->fetch()
);

echo '<h1>Statistics for #'.$Activity->id().'</h1>';

echo '<pre>'.date('d.m.Y', $Activity->timestamp()).', '.Distance::format($Activity->distance()).', '.Duration::format($Activity->duration()).'</pre>&nbsp;';

foreach ($Trackdata->properties() as $key) {
	if ($Trackdata->isArray($key) && $Trackdata->has($key) && $key != Trackdata\Entity::TIME) {
		$TimeSeries = new TimeSeries($Trackdata->get($key), $Trackdata->time());
		$TimeSeries->calculateStatistic();

		echo '<pre>'.$key.':'.PHP_EOL;
		echo 'min:    '.sprintf("%12.3f", $TimeSeries->min()).PHP_EOL;
		echo 'mean:   '.sprintf("%12.3f", $TimeSeries->mean()).PHP_EOL;
		echo 'median: '.sprintf("%12.3f", $TimeSeries->median()).PHP_EOL;
		echo 'max:    '.sprintf("%12.3f", $TimeSeries->max()).PHP_EOL;
		echo 'mode:   '.sprintf("%12.3f", $TimeSeries->mode()).PHP_EOL;
		echo 'var:    '.sprintf("%12.3f", $TimeSeries->variance()).PHP_EOL;
		echo 'std:    '.sprintf("%12.3f", $TimeSeries->stdDev()).PHP_EOL;
		echo '</pre>';
		echo '&nbsp;';
	}
}
