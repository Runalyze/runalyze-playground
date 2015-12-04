<?php
require_once '../../bootstrap.php';

use Runalyze\Model\Trackdata;
use Runalyze\Activity\Duration;
use Runalyze\View\Activity\Plot\PaceAndHeartrate;
use Runalyze\Calculation\JD\VDOT;

$stepDistance = isset($_GET['stepdistance']) ? (int)$_GET['stepdistance'] : 0.1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$where = isset($_GET['id']) ? '`id`="'.(int)$_GET['id'].'"' : '`s` > 1800 AND `sportid`='.Runalyze\Configuration::General()->runningSport();

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
	FROM `'.PREFIX.'training` AS `act`
	LEFT JOIN `'.PREFIX.'trackdata` AS `track` ON `track`.`activityid` = `act`.`id`
	WHERE '.$where.' AND `act`.`accountid` > -1 AND `track`.`heartrate` IS NOT NULL AND `track`.`heartrate` != ""
	ORDER BY `timestamp` DESC
	LIMIT '.$limit
);

while ($data = $Statement->fetch()) {
	echo '<strong>'.date('d.m.Y', $data['timestamp']).', '.$data['km'].'k in '.Duration::format($data['s']).' at &oslash; '.$data['pulse_avg'].'bpm = VDOT '.$data['vdot'].'</strong>';


	$Trackdata = new Trackdata\Object($data);
	$Loop = new Trackdata\Loop($Trackdata);
	$VDOTs = array();
	$VDOT = new VDOT();

	while (!$Loop->isAtEnd()) {
		$Loop->moveDistance($stepDistance);

		$VDOT->fromPaceAndHR(
			$Loop->difference(Trackdata\Object::DISTANCE),
			$Loop->difference(Trackdata\Object::TIME),
			$Loop->average(Trackdata\Object::HEARTRATE)/HF_MAX
		);

		$VDOTs[$Loop->time().'000'] = $VDOT->value();
	}

	$Plot = new Plot('vdot-'.$data['id'], 600, 190);
	$Plot->Data[] = array('label' => __('VDOT'), 'color' => '#000000', 'data' => $VDOTs);
	$Plot->showPoints();
	$Plot->setXAxisAsTime();
	$Plot->setXAxisTimeFormat("%h:%M:%S");
	$Plot->Options['xaxis']['ticks'] = 5;
	$Plot->smoothing(false);
	$Plot->addThreshold('y', $data['vdot']);

	$Plot->outputDiv();
	$Plot->outputJavaScript();

	//$CombinedPlot = new PaceAndHeartrate(new \Runalyze\View\Activity\Context);
}

echo Ajax::wrapJSforDocumentReady('Runalyze.createFlot();');
