<?php
require_once '../../bootstrap.php';
include 'VirtualLoop.php';
include 'IntervalPlot.php';

use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Model\Trackdata;
use Runalyze\Parameter\Application\PaceUnit;

$activityIDs = isset($_GET['activityIDs']) ? $_GET['activityIDs'] : "719,726";
$adjustDistance = isset($_GET['adjustDistance']) ? boolval($_GET['adjustDistance']) : true;

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
	ORDER BY `timestamp`
');

$i = 1;

while ($data = $Statement->fetch()) {
    echo '<strong>' . date('d.m.Y', $data['timestamp']) . ', ' . $data['km'] . 'k in ' . Duration::format($data['s']) . ' at &oslash; ' . $data['pulse_avg'] . 'bpm = VDOT ' . $data['vdot'] . '</strong>';
    echo '<br/>';

    $Trackdata[$i] = new Trackdata\Entity($data);
    $Loop[$i] = new Trackdata\Loop($Trackdata[$i]);
    $distCoef[$i] = $adjustDistance ? $Trackdata[$i]->totalDistance() / $data['km'] : 1;
    $i++;
};

$vRacerPace = isset($_GET['vRacerPace']) ? $_GET['vRacerPace'] : (new Pace($Trackdata[1]->totalTime(), $Trackdata[1]->totalDistance(), PaceUnit::MIN_PER_KM))->value();

$Loop[0] = new Trackdata\VirtualLoop($vRacerPace, $Trackdata[1]->totalTime());

while (!($Loop[0]->isAtEnd())) {
    $Loop[0]->moveTime(10);
    $Loop[1]->moveTime(10);
    $Loop[2]->moveTime(10);


    $distdiff0[$Loop[0]->time() . '000'] = 0;
    $distdiff1[$Loop[0]->time() . '000'] = 1000 * ($Loop[1]->distance() / $distCoef[1] - $Loop[0]->distance());
    $distdiff2[$Loop[0]->time() . '000'] = 1000 * ($Loop[2]->distance() / $distCoef[2] - $Loop[0]->distance());
}

$Loop[0]->reset();
$Loop[1]->reset();
$Loop[2]->reset();

$distance = 0;
while (!($Loop[1]->isAtEnd())) {
    $distance += 0.1;
    $Loop[0]->moveToDistance($distance);
    $Loop[1]->moveToDistance($distance * $distCoef[1]);
    $Loop[2]->moveToDistance($distance * $distCoef[2]);


    $timediff0[number_format($Loop[0]->distance(), 1)] = '0';
    $timediff1[number_format($Loop[0]->distance(), 1)] = ($Loop[1]->time() - $Loop[0]->time()) . '000';
    $timediff2[number_format($Loop[0]->distance(), 1)] = ($Loop[2]->time() - $Loop[0]->time()) . '000';
}
//var_dump($timediff);exit;

$Plot = new Plot('diff-' . $data['id'], 600, 190);
$Plot->Data[] = array('label' => 'Virtual racer @' . $vRacerPace, 'color' => '#009900', 'data' => $distdiff0);
$Plot->Data[] = array('label' => 'Distance 1 vs ' . $vRacerPace, 'color' => '#000099', 'data' => $distdiff1);
$Plot->Data[] = array('label' => 'Distance 2 vs ' . $vRacerPace, 'color' => '#aa0000', 'data' => $distdiff2);
//$Plot->showPoints();
$Plot->setXAxisAsTime();
$Plot->setXAxisTimeFormat("%h:%M:%S");
$Plot->Options['xaxis']['ticks'] = 5;
$Plot->smoothing(false);
//$Plot->addThreshold('y', $data['vdot']);

$Plot->outputDiv();
$Plot->outputJavaScript();

$Plot = new IntervalPlot('diff-time-' . $data['id'], 600, 190);
$Plot->Data[] = array('label' => 'Virtual racer @' . $vRacerPace, 'color' => '#009900', 'data' => $timediff0);
$Plot->Data[] = array('label' => 'Time 1 vs ' . $vRacerPace, 'color' => '#000099', 'data' => $timediff1);
$Plot->Data[] = array('label' => 'Time 2 vs ' . $vRacerPace, 'color' => '#aa0000', 'data' => $timediff2);
//$Plot->showPoints();

//$Plot->setYAxisAsTime();
//$Plot->setYAxisTimeFormat("%h:%M:%S");
$Plot->setXUnit('km');
$Plot->setYAxisToInterval(1);
$Plot->Options['yaxis']['minTickSize'] = 60000;

$Plot->Options['xaxis']['ticks'] = 5;
$Plot->smoothing(false);
//$Plot->addThreshold('y', $data['vdot']);

$Plot->outputDiv();
$Plot->outputJavaScript();
//$CombinedPlot = new PaceAndHeartrate(new \Runalyze\View\Activity\Context);


echo Ajax::wrapJSforDocumentReady('Runalyze.createFlot();');
