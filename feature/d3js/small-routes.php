<?php

use Runalyze\Model\Route;

$LOAD_JS = false;
require_once '../../bootstrap.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$size = isset($_GET['size']) ? (int)$_GET['size'] : 100;
$stepsize = isset($_GET['stepsize']) ? (int)$_GET['stepsize'] : 10;
?>

<script src="http://d3js.org/d3.v2.min.js?2.10.0"></script>

<style>
.route {
	display: inline-block;
	margin: 10px;
	width: <?php echo $size; ?>px;
	height: <?php echo $size; ?>px;
}
.line {
	fill: none;
	stroke: steelblue;
	stroke-width: 1.5px;
}
</style>

<script type="text/javascript">
var size = <?php echo $size; ?>;


var data = d3.range(20).map(function(i) {
  return {x: i / 19, y: (Math.sin(i / 3) + 2) / 4};
});

var x = d3.scale.linear().domain([0, 1]).range([0, size]);
var y = d3.scale.linear().domain([0, 1]).range([size, 0]);
var line = d3.svg.line().interpolate('monotone').x(function(d) { return x(d.x); }).y(function(d) { return y(d.y); });
</script>

<?php
$Statement = DB::getInstance()->query(
	'SELECT * FROM `'.PREFIX.'route`
	WHERE min_lat != 0 AND max_lat != 0 AND min_lng != 0 AND max_lng != 0
	ORDER BY `id` DESC LIMIT '.$limit
);

while ($Data = $Statement->fetch()) {
	$Route = new Route\Object($Data);
	$Loop = new Route\Loop($Route);
	$Loop->setStepSize($stepsize);
	$Path = array();

	while (!$Loop->isAtEnd()) {
		$Loop->nextStep();

		if ($Loop->latitude() != 0 && $Loop->longitude()) {
			$Path[] = array('y' => $Loop->latitude(), 'x' => $Loop->longitude());
		}
	}

	if (!empty($Path)) {
		echo '<div id="route-'.$Route->id().'" class="route" title="'.htmlspecialchars($Route->name()).'"></div>';
		echo '<script type="text/javascript">';
		echo 'var y = d3.scale.linear().domain(['.$Route->get(Route\Object::MIN_LATITUDE).', '.$Route->get(Route\Object::MAX_LATITUDE).']).range([size, 0]);';
		echo 'var x = d3.scale.linear().domain(['.$Route->get(Route\Object::MIN_LONGITUDE).', '.$Route->get(Route\Object::MAX_LONGITUDE).']).range([0, size]);';
		echo 'var line = d3.svg.line().interpolate(\'monotone\').x(function(d) { return x(d.x); }).y(function(d) { return y(d.y); });';
		echo 'var data = '.json_encode($Path).';';
		echo 'd3.select("#route-'.$Route->id().'").append("svg").datum(data).attr("width", size).attr("height", size).append("path").attr("class", "line").attr("d", line);';
		echo '</script>';
	}
}