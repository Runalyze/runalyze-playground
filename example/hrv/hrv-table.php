<?php

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Calculation;
use Runalyze\Model;

$LOAD_JS = false;
require_once '../../bootstrap.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
?>

<table class="zebra-style w100">
	<thead>
	<tr>
		<th>#</th>
		<th>sport</th>
		<th>date</th>
		<th>km</th>
		<th>duration</th>
		<th>lnRMSSD</th>
        <th>pNN50</th>
        <th>pNN20</th>
		<th>&Oslash; RR</th>
		<th>RMSSD</th>
		<th>SDSD</th>
		<th>SDNN</th>
		<th>5-min SDNN</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$Statement = DB::getInstance()->query(
		'SELECT
		`data`,
		`t`.`id`,
		`t`.`time`,
		`t`.`s`,
		`t`.`distance`,
		`s`.`img`,
		`t`.`accountid`
	FROM `'.PREFIX.'hrv`
	JOIN `'.PREFIX.'training` AS `t` ON `'.PREFIX.'hrv`.`activityid` = `t`.`id`
	JOIN `'.PREFIX.'sport` AS `s` ON `t`.`sportid` = `s`.`id`
	ORDER BY `activityid` DESC LIMIT '.$limit
	);

	while ($Row = $Statement->fetch()) {
        $Calculator = new Calculation\HRV\Calculator(new Model\HRV\Entity(array(
            Model\HRV\Entity::DATA => $Row['data']
        )));
        $Calculator->calculate();

		echo '<tr class="r">';
		echo '<td>#'.$Row['id'].'</td>';
		echo '<td><i class="'.$Row['img'].'">   </i></td>';
        echo '<td>'.date('Y-m-d', $Row['time']).'</td>';
        echo '<td>'.($Row['distance'] > 0 ? Distance::format($Row['distance']) : '-').'</td>';
        echo '<td>'.Duration::format($Row['s']).'</td>';
        echo '<td class="'.(log($Calculator->RMSSD()) < 4 ? 'minus' : '').'">'.number_format(log($Calculator->RMSSD()), 1).'<small></small></td>';
        echo '<td class="'.($Calculator->pNN50() < 0.01 ? 'minus' : '').'">'.number_format($Calculator->pNN50()*100, 1).'<small>&#37;</small></td>';
        echo '<td class="'.($Calculator->pNN50() < 0.01 ? 'minus' : '').'">'.number_format($Calculator->pNN20()*100, 1).'<small>&#37;</small></td>';
        echo '<td>'.round($Calculator->mean()).'<small>ms</small></td>';
        echo '<td>'.round($Calculator->RMSSD()).'<small>ms</small></td>';
        echo '<td>'.round($Calculator->SDSD()).'<small>ms</small></td>';
        echo '<td>'.round($Calculator->SDNN()).'<small>ms</small></td>';
        echo '<td>'.($Calculator->SDANN() > 0 ? round($Calculator->SDANN()).'<small>ms</small>' : '-').'</td>';
		echo '</tr>';
	}
	?>
	</tbody>
</table>