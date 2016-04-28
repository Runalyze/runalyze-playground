<?php
require_once '../../bootstrap.php';
include 'VirtualLoop.php';
include 'IntervalPlot.php';

use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Model\Trackdata;
use Runalyze\Parameter\Application\PaceUnit;

$activityIDs = isset($_GET['activityIDs']) ? $_GET['activityIDs'] : "719,726";
$adjustDistance = isset($_GET['adjustDistance']) ? $_GET['adjustDistance'] == 'yes' : true;

$where = "`id` in ($activityIDs)";

/** @var \Runalyze\Model\Trackdata\Loop[] $Loop */
$Loop = [];
/** @var \Runalyze\Model\Trackdata\Entity[] $Trackdata */
$Trackdata = [];


$Statement = DB::getInstance()->query(
    'SELECT
		`act`.`id`,
		`act`.`time` as `timestamp`,
		`act`.`distance` as `km`,
		`act`.`s`,
		`act`.`pulse_avg`,
		`act`.`vdot`,
		`track`.`time`,
		`track`.`distance`,
		`track`.`heartrate`
	FROM `' . PREFIX . 'training` AS `act`
	LEFT JOIN `' . PREFIX . 'trackdata` AS `track` ON `track`.`activityid` = `act`.`id`
	WHERE ' . $where . ' AND `act`.`accountid` > -1 AND `track`.`heartrate` IS NOT NULL AND `track`.`heartrate` != ""
	ORDER BY FIELD(id,' . $activityIDs . ')
');

$i = 1;

while ($data = $Statement->fetch()) {
    $Trackdata[$i] = new Trackdata\Entity($data);
    $Loop[$i] = new Trackdata\Loop($Trackdata[$i]);
    $totalTime[$i] = $adjustDistance ? $data['s'] : $Trackdata[$i]->totalTime();
    $totalDistance[$i] = $adjustDistance ? $data['km'] : $Trackdata[$i]->totalDistance();
    $distCoef[$i] = $Trackdata[$i]->totalDistance() / $totalDistance[$i];
    $avgPace[$i] = (new Pace($totalTime[$i], $totalDistance[$i], PaceUnit::MIN_PER_KM))->value();
    echo '<strong>' . date('d.m.Y', $data['timestamp']) . ', ' . $totalDistance[$i] . 'k in ' . Duration::format($totalTime[$i]) . ', ' . $data['pulse_avg'] . 'bpm = VDOT ' . $data['vdot'] . ' avg pace ' . $avgPace[$i] . '/km</strong>';
    echo '<br/>';

    $i++;
};

$vRacerPace = isset($_GET['vRacerPace']) ? $_GET['vRacerPace'] : $avgPace[1];

$Loop[0] = new Trackdata\VirtualLoop($vRacerPace, $totalTime[1]);
$activityCount = sizeof($Trackdata);

while (!($Loop[0]->isAtEnd())) {
    $Loop[0]->moveTime(10);
    $distdiff[0][$Loop[0]->time() . '000'] = 0;

    for ($i = 1; $i <= $activityCount; $i++) {
        $Loop[$i]->moveTime(10);
        if (!$Loop[$i]->isAtEnd()) $distdiff[$i][$Loop[0]->time() . '000'] = 1000 * ($Loop[$i]->distance() / $distCoef[$i] - $Loop[0]->distance());
    }
}

for ($i = 0; $i <= $activityCount; $i++) {
    $Loop[$i]->reset();
}

$distance = 0;
while (!($Loop[1]->isAtEnd())) {
    $distance += 0.05;
    $Loop[0]->moveToDistance($distance);
    $timediff[0][number_format($Loop[0]->distance(), 1)] = '0';

    for ($i = 1; $i <= $activityCount; $i++) {
        $Loop[$i]->moveToDistance($distance * $distCoef[$i]);
        if (!$Loop[$i]->isAtEnd()) $timediff[$i][number_format($Loop[0]->distance(), 1)] = ($Loop[$i]->time() - $Loop[0]->time()) . '000';
    }
}
//var_dump($timediff);exit;

$Plot = new Plot('diff-' . $data['id'], 600, 190);
$Plot->Data[] = array('label' => 'Virtual racer @' . $vRacerPace, 'data' => $distdiff[0]);
for ($i = 1; $i <= $activityCount; $i++) {
    $Plot->Data[] = array('label' => 'Distance ' . $i . ' vs ' . $vRacerPace, 'data' => $distdiff[$i]);
}
//$Plot->showPoints();
$Plot->setXAxisAsTime();
$Plot->setXAxisTimeFormat("%h:%M:%S");
$Plot->Options['xaxis']['ticks'] = 5;
$Plot->smoothing(false);

$Plot->outputDiv();
$Plot->outputJavaScript();

$Plot = new IntervalPlot('diff-time-' . $data['id'], 600, 190);
$Plot->Data[] = array('label' => 'Virtual racer @' . $vRacerPace, 'data' => $timediff[0]);
for ($i = 1; $i <= $activityCount; $i++) {
    $Plot->Data[] = array('label' => 'Time ' . $i . ' vs ' . $vRacerPace, 'data' => $timediff[$i]);
}
//$Plot->showPoints();

//$Plot->setYAxisAsTime();
//$Plot->setYAxisTimeFormat("%h:%M:%S");
$Plot->setXUnit('km');
$Plot->setYAxisToInterval(1);
$Plot->Options['yaxis']['minTickSize'] = 60000;

$Plot->Options['xaxis']['ticks'] = 5;
$Plot->smoothing(false);

$Plot->outputDiv();
$Plot->outputJavaScript();


echo Ajax::wrapJSforDocumentReady('Runalyze.createFlot();');
