<?php
$LOAD_JS = false;

require_once '../../bootstrap.php';

use Runalyze\Util\LocalTime;

$year = isset($_GET['year']) ? (int)$_GET['year'] : 'all';

$Statement = DB::getInstance()->query(
    'SELECT
        `time`,
        `s`,
        `distance`,
        `trimp`
	FROM `'.PREFIX.'training`
	WHERE
	    TIME(FROM_UNIXTIME(`time`)) != "00:00:00"
	    '.($year != 'all' ? 'AND YEAR(FROM_UNIXTIME(`time`)) = '.(int)$_GET['year'] : '')
);

$Time = array_fill(0, 7*24, 0);
$Distance = array_fill(0, 7*24, 0);
$Trimp = array_fill(0, 7*24, 0);

while ($data = $Statement->fetch()) {
    $time = (new LocalTime($data['time']))->toServerTimestamp();
    $startTime = getdate($time);
    $endTime = getdate($time + $data['s']);

    $day = $startTime['wday'];
    $hour = $startTime['hours'];
    $minutes = 60 - $startTime['minutes'];
    $needToBreak = false;

    while (!$needToBreak) {
        if ($day == $endTime['wday'] && $hour == $endTime['hours']) {
            $minutes -= (60 - $endTime['minutes']);
            $needToBreak = true;
        }

        $factor = $minutes / ((float)$data['s']/60);
        $index = (($day+6)%7)*24 + $hour;

        $Time[$index] += $minutes;
        $Distance[$index] += $factor * (float)$data['distance'];
        $Trimp[$index] += $factor * (float)$data['trimp'];

        $minutes = 60;
        $hour = ($hour + 1)%24;
        $day = ($hour == 0) ? ($day+1)%7 : $day;
    }
}
?>

<script src="http://d3js.org/d3.v2.min.js?2.10.0"></script>

<style>
    text {-webkit-touch-callout: none; -webkit-user-select: none; -khtml-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none;}

    svg text {font-family: helvetica, sans-serif; fill: #555;}
    #status {text-anchor: middle;}
    #status g.first text.time {font-size: 14px;}
    #status g.first text.value {font-size: 44px; font-weight: bold;}
    #status g.first text.units {font-size: 12px;}

    #status g.second text.time, #status g.third text.time {font-size: 12px;}
    #status g.second text.value, #status g.third text.value {font-size: 30px; fill: #555;}
    #status g.second text.units, #status g.third text.units {font-size: 10px;}

    svg text.day.label {font-size: 14px; fill: #bbb; pointer-events: none;}
    svg text.time.label {font-size: 12px; #bbb; pointer-events: none;}
</style>

<div style="background:#fff; text-align: center;">
    <svg width="600" height="600" style="margin:auto;" xmlns:xlink="http://www.w3.org/1999/xlink">
        <g id="status">
            <g class="first">
                <text class="time" x="300" y="300"></text><!-- 237 -->
                <text class="value" x="300" y="256">-</text><!-- 276 -->
                <text class="units" x="300" y="270">hours</text><!-- 290 -->
            </g>
            <g class="second">
                <text class="time" x="255" y="320"></text>
                <text class="value" x="255" y="347">-</text>
                <text class="units" x="255" y="360">trimp</text>
            </g>
            <g class="third">
                <text class="time" x="345" y="320"></text>
                <text class="value" x="345" y="347">-</text>
                <text class="units" x="345" y="360">km</text>
            </g>
        </g>
    </svg>
</div>

<script type="text/javascript">
    var dataTime = <?php echo json_encode($Time); ?>;
    var dataTrimp = <?php echo json_encode($Trimp); ?>;
    var dataDistance = <?php echo json_encode($Distance); ?>;

    var g = d3.select("svg").append("g").attr("id", "chart");

    var initial_rad = 100;
    var rad_offset = 25;
    ir = function(d, i) {return initial_rad+Math.floor(i/24)*rad_offset;}
    or = function(d, i) {return initial_rad+rad_offset+Math.floor(i/24)*rad_offset;}
    sa = function(d, i) {return (i*2*Math.PI)/24;}
    ea = function(d, i) {return ((i+1)*2*Math.PI)/24;}

    //Draw the chart
    var color = d3.scale.linear().domain([0, <?php echo max($Time); ?>]).range(["white", "red"]);
    d3.select('#chart').selectAll('path').data(dataTime)
        .enter().append('svg:path')
        .attr('d', d3.svg.arc().innerRadius(ir).outerRadius(or).startAngle(sa).endAngle(ea))
        .attr('transform', 'translate(300, 300)')
        .attr('fill', color)
        .attr("stroke", "gray")
        .attr("stroke-width", "0.3px")
        .on('mouseover', setInfo)
        .on('mouseout', resetInfo);

    //Labels
    var day_labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    var label_rad = 106;
    for (var i = 0; i < 7; i++) {
        label = day_labels[i];
        label_angle = 4.73;
        d3.select("svg").append("def")
            .append("path")
            .attr("id", "day_path"+i)
            .attr("d", "M300 300 m"+label_rad*Math.cos(label_angle)+" "+label_rad*Math.sin(label_angle)+" A"+label_rad+" "+label_rad+" 90 0 1 "+(300+label_rad)+" 300");
        d3.select("svg").append("text")
            .attr("class", "day label")
            .append("textPath")
            .attr("xlink:href", location.href+"#day_path"+i)
            .text(label);
        label_rad += rad_offset;
    }

    label_rad = 280;
    d3.select("svg").append("def")
        .append("path")
        .attr("id", "time_path")
        .attr("d", "M300 "+(300-label_rad)+" a"+label_rad+" "+label_rad+" 0 1 1 -1 0");

    for (var i = 0; i < 24; i++) {
        label_angle = (i-6)*(2*Math.PI/24);
        large_arc = i<6 || i> 18? 0 : 1;
        d3.select("svg").append("text")
            .attr("class", "time label")
            .append("textPath")
            .attr("xlink:href", location.href+"#time_path")
            .attr("startOffset", i*100/24+"%")
            .text(convertToAmPm(i));
    }

    resetInfo();

    function setInfo(d, i) {
        var days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        var day = Math.floor(i/24);
        var h = i%24;

        //Update times
        d3.select('#status g.first text.time').text(days[day] + ', ' + convertToAmPm(h) + ' - ' + convertToAmPm(parseInt(h) + 1));
        d3.select('#status g.first text.value').text((new Number(d/60)).toFixed(1));
        d3.select('#status g.second text.value').text((new Number(dataTrimp[i])).toFixed(0));
        d3.select('#status g.third text.value').text((new Number(dataDistance[i])).toFixed(0));
    }

    function resetInfo() {
        d3.select('#status g.first text.time').text('');
        d3.select('#status g.first text.value').text('-');
        d3.select('#status g.second text.value').text('-');
        d3.select('#status g.third text.value').text('-');
    }

    function convertToAmPm(h) {
        if (h == '0' || h == '24') {
            return 'Midnight';
        }

        var suffix = 'am';

        if (h > 11) {
            suffix = 'pm';
        }

        if (h > 12) {
            return (h - 12) + suffix;
        }

        return h+suffix;
    }
</script>