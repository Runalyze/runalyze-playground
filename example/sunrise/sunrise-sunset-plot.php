<?php
$LOAD_FRONTEND = false;
$LOAD_HTML = false;
$LOAD_CSS = false;
$LOAD_JS = false;

require_once '../../bootstrap.php';

date_default_timezone_set('Europe/Berlin');

$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 49.440066;
$lng = isset($_GET['lng']) ? (float)$_GET['lng'] : 7.749126;
$zenith = 90 + 5/6;

function data(callable $creator) {
	$Date = new DateTime('NOW', new DateTimeZone('Europe/Berlin'));
	$OneDay = new DateInterval('P1D');
	$singleData = array();

	for ($i = 0; $i < 365; ++$i) {
		$singleData[] = "'date':new Date('".$Date->format('Y-m-d')."'),'value':".$creator($Date);
		$Date->add($OneDay);
	}

	echo '[{'.implode('},{', $singleData).'}]';
}
?>
<html>
	<head>
    	<script src='https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js'></script>
    	<script src='https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.0/d3.min.js' charset='utf-8'></script>
    	<script src='https://cdnjs.cloudflare.com/ajax/libs/metrics-graphics/2.7.0/metricsgraphics.min.js'></script>
    
    	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/metrics-graphics/2.7.0/metricsgraphics.min.css">
	</head>
	<body>
		<div id="sunsets"></div>
		<div id="sunsets-legend"></div>
		<script>
		var fDay = function (v) { var f = d3.time.format('%d.%m.%Y'); return f(new Date(v)); };
		var fTime = function (v) { var f = d3.time.format('%H:%M'); return f(new Date(v*60*60*1000-3600000)); };
		MG.data_graphic({
		    width: 760,
		    height: 300,
		    right: 60,
		    bottom: 40,
		    target: '#sunsets',
		    title: "Sunrise/Sunset",
		    data: [
		    	<?php data(function(DateTime $date) use ($lat, $lng, $zenith) { return round(date_sunrise($date->getTimestamp(), SUNFUNCS_RET_DOUBLE, $lat, $lng, $zenith, $date->getOffset()/3600), 2); }); ?>,
		    	<?php data(function(DateTime $date) use ($lat, $lng, $zenith) { return round(date_sunset($date->getTimestamp(), SUNFUNCS_RET_DOUBLE, $lat, $lng, $zenith, $date->getOffset()/3600), 2); }); ?>
		    	],
		    x_accessor: 'date',
		    y_accessor: 'value',
		    colors: ['#FDB813', '#F68B1F'],
		    legend: ['Sunrise', 'Sunset'],
		    //legend_target: '#sunsets-legend',
		    markers: [{
		        'date': new Date('2016-03-26T12:00:00.000Z'),
		        'label': 'Start summertime'
		    }, {
		        'date': new Date('2016-10-29T12:00:00.000Z'),
		        'label': 'End summertime'
		    }],
		    aggregate_rollover: true,
	        mouseover: function(d, i) {
	            d3.select('#sunsets svg .mg-active-datapoint')
	                .text(fDay(d.key) + ': ' + fTime(d.values[0].value) + ' / ' + fTime(d.values[1].value));
	        },
		    inflator: 1,
		    min_y: 4,
		    max_y: 22,
		    yax_count: 7,
		    yax_format: fTime,
		    xax_count: 12,
		    xax_format: d3.time.format('%b')
		})
		</script>
	</body>
</html>