<?php

use Runalyze\Model\Trackdata;

$LOAD_JS = false;
require_once '../../bootstrap.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$width = isset($_GET['w']) ? (int)$_GET['w'] : 100;
$height = isset($_GET['h']) ? (int)$_GET['h'] : 25;
$points = isset($_GET['points']) ? (int)$_GET['points'] : 150;
?>

<script src="http://d3js.org/d3.v3.min.js"></script>

<style>
.sparkline {
  fill: none;
  stroke: #000;
  stroke-width: 0.5px;
}
.sparkcircle {
  fill: #f00;
  stroke: none;
}
</style>

<script type="text/javascript">
var width = <?php echo (int)$width; ?>;
var height = <?php echo (int)$height; ?>;

function sparkline(elemId, data, i, minY, maxY) {
	var max = d3.entries(data).sort(function(a,b){return d3.descending(a.value[i], b.value[i]);});
	var min = d3.entries(data).sort(function(a,b){return d3.ascending(a.value[i], b.value[i]);});

	maxY = maxY || max[0].value[i];
	minY = minY || max[data.length-1].value[i];

	var x = d3.scale.linear().domain([data[0][0], data[data.length-1][0]]).range([0, width-2]);
	var y = d3.scale.linear().domain([minY, maxY]).range([height-4, 0]);
	var line = d3.svg.line().interpolate('monotone').x(function(d) { return x(d[0]); }).y(function(d) { return y(d[i]); });

	var svg = d3.select(elemId)
				.append('svg')
				.attr('width', width)
				.attr('height', height)
				.append('g')
				.attr('transform', 'translate(0, 2)');
	svg.append('path')
	   .datum(data)
	   .attr('class', 'sparkline')
	   .attr('d', line);
	svg.append('circle')
	   .attr('class', 'sparkcircle')
	   .attr('cx', x(max[0].value[0]))
	   .attr('cy', y(max[0].value[i]))
	   .attr('r', 1.5);
}
</script>

<table class="zebra-style w100">
	<thead>
		<tr>
			<th>#</th>
			<th>pace</th>
			<th>bpm</th>
			<th>rpm</th>
			<th>ms</th>
			<th>cm</th>
		</tr>
	</thead>
	<tbody>
<?php
$Statement = DB::getInstance()->query(
	'SELECT
		`activityid`,
		`time`,
		`distance`,
		`heartrate`,
		`cadence`,
		`groundcontact`,
		`vertical_oscillation`
	FROM `'.PREFIX.'trackdata`
	WHERE
		`heartrate` IS NOT NULL AND
		`distance` IS NOT NULL AND `distance` != ""
	ORDER BY `activityid` DESC LIMIT '.$limit
);

while ($Row = $Statement->fetch()) {
	$Trackdata = new Trackdata\Object($Row);
	$Loop = new Trackdata\Loop($Trackdata);
	$Loop->setStepSize(round($Loop->num() / $points));
	$Data = array();

	while (!$Loop->isAtEnd()) {
		$Loop->nextStep();
		$Data[] = array(
			$Loop->time(),
			$Loop->current(Trackdata\Object::PACE),
			$Loop->current(Trackdata\Object::HEARTRATE),
			$Loop->current(Trackdata\Object::CADENCE),
			$Loop->current(Trackdata\Object::GROUNDCONTACT),
			$Loop->current(Trackdata\Object::VERTICAL_OSCILLATION)
		);
	}

	if (!empty($Data)) {
		$id = $Trackdata->activityID();

		echo '<tr class="c">';
		echo '<td>#'.$id.'</td>';
		echo '<td><div id="sparkline-pace-'.$id.'"></div></td>';
		echo '<td><div id="sparkline-hr-'.$id.'"></div></td>';
		echo '<td><div id="sparkline-rpm-'.$id.'"></div></td>';
		echo '<td><div id="sparkline-gc-'.$id.'"></div></td>';
		echo '<td><div id="sparkline-vo-'.$id.'"></div></td>';
		echo '</tr>';

		echo '<script type="text/javascript">';
		echo 'var data_'.$id.' = '.json_encode($Data, JSON_NUMERIC_CHECK).';';
		if ($Trackdata->has(Trackdata\Object::PACE)) echo 'sparkline("#sparkline-pace-'.$id.'", data_'.$id.', 1);';
		if ($Trackdata->has(Trackdata\Object::HEARTRATE)) echo 'sparkline("#sparkline-hr-'.$id.'", data_'.$id.', 2, 0, '.HF_MAX.');';
		if ($Trackdata->has(Trackdata\Object::CADENCE)) echo 'sparkline("#sparkline-rpm-'.$id.'", data_'.$id.', 3);';
		if ($Trackdata->has(Trackdata\Object::GROUNDCONTACT)) echo 'sparkline("#sparkline-gc-'.$id.'", data_'.$id.', 4);';
		if ($Trackdata->has(Trackdata\Object::VERTICAL_OSCILLATION)) echo 'sparkline("#sparkline-vo-'.$id.'", data_'.$id.', 5);';
		echo '</script>';
	}
}
?>
	</tbody>
</table>