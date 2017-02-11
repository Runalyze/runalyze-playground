<?php

use Runalyze\Calculation\Math\MovingAverage;
use Runalyze\Calculation\Math\MovingAverage\Kernel;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity\Context;
use Runalyze\View\Activity\Plot;

require_once '../../bootstrap.php';

class AdjustedPace extends Plot\Pace
{
    protected $idAppendix;

    public function __construct(Context $context, $idAppendix)
    {
        $this->idAppendix = $idAppendix;

        parent::__construct($context);
    }

    protected function setKey()
    {
        $this->key = 'pace_'.$this->idAppendix.'_';
    }

    public function plot()
    {
        return $this->Plot;
    }
}

$allKernels = Kernel\Kernels::getEnum();

$activityID = isset($_GET['activityID']) ? $_GET['activityID'] : 3686;
$accountID = isset($_GET['accountID']) ? $_GET['accountID'] : 0;
$smoothing = isset($_GET['smoothing']) ? ($_GET['smoothing'] == '1') : true;
$precision = isset($_GET['precision']) ? $_GET['precision'] : '1000points';
$widths = [10, 30, 60, 120, 300];
//$widths = [0.05, 0.1, 0.2, 0.35, 0.5];
$alphas = [0.99, 0.975, 0.95, 0.90, 0.75];
$ffs = [0.5, 0.3, 0.2, 0.1, 0.05];

Runalyze\Configuration::ActivityView()->plotPrecision()->set($precision);

if (null === $activityID) {
    die('You must specify a moving-average.php?activityID=...');
}

$Context = new Context($activityID, $accountID);
?>

<style>
body {
    background: #fff !important;
}
.panel {
    padding: 0;
    margin: 0;
    border: 0;
}
</style>

<table class="zebra-style">
	<thead>
		<tr>
			<th>type</th>
            <?php foreach ($widths as $width) echo '<th>'.$width.'s</th>'; ?>
		</tr>
	</thead>
	<tbody>
        <tr>
            <td>NORMAL</td>
            <td><?php
                $Plot = new AdjustedPace($Context, '');
                $Plot->plot()->smoothing($smoothing);
                $Plot->display();
            ?></td>
            <?php for ($i = 0; $i < count($widths)-1; ++$i) echo '<td></td>'; ?>
        </tr>
        <tr>
            <td>BUTTERWORTH<br>(order 2)<br>ff&nbsp;=&nbsp;...<br><?php echo implode('<br>', $ffs); ?></td>
            <?php
            foreach ($ffs as $j => $ff) {
                echo '<td>';

                $digital_ff = $ff / 2;
                $ita = 1 / tan(M_PI * $digital_ff);
                $q = sqrt(2.0);
                $b0 = 1 / (1 + $q * $ita + $ita * $ita);
                $b1 = 2 * $b0;
                $b2 = $b0;
                $a1 = - 2 * ($ita * $ita - 1) * $b0;
                $a2 = (1 - $q * $ita + $ita * $ita) * $b0;

                $data = array_merge($Context->trackdata()->pace(), [0, 0, 0, 0, 0, 0]);
                $num = count($data);
                $data_new = array_fill(0, $num, 0);
                $data_new[0] = $b0 * $data[0];
                $data_new[1] = $b0 * $data[1] + $b1 * $data[0] - $a1 * $data_new[0];

                for ($i = 2; $i < $num; ++$i) {
                    $data_new[$i] = $b0 * $data[$i] + $b1 * $data[$i-1] + $b2 * $data[$i-2] - $a1 * $data_new[$i-1] - $a2 * $data_new[$i-2];
                }

                $data = array_reverse($data_new);

                $data_new[0] = $b0 * $data[0];
                $data_new[1] = $b0 * $data[1] + $b1 * $data[0] - $a1 * $data_new[0];

                for ($i = 2; $i < $num; ++$i) {
                    $data_new[$i] = $b0 * $data[$i] + $b1 * $data[$i-1] + $b2 * $data[$i-2] - $a1 * $data_new[$i-1] - $a2 * $data_new[$i-2];
                }

                $data = array_reverse(array_slice($data_new, 6));

                $ContextCopy = clone $Context;
                $ContextCopy->trackdata()->set(Trackdata::PACE, $data);

                $Plot = new AdjustedPace($ContextCopy, 'butterworth_'.$j);
                $Plot->plot()->smoothing(false);
                $Plot->display();

                echo '</td>';
            }
            ?>
        </tr>
        <tr>
            <td>EXPONENTIAL<br>alpha&nbsp;=&nbsp;...<br><?php echo implode('<br>', $alphas); ?></td>
<?php
foreach ($alphas as $i => $alpha) {
    echo '<td>';

    $MovingAverage = new MovingAverage\Exponential($Context->trackdata()->pace(), $Context->trackdata()->distance());
    $MovingAverage->setAlpha($alpha);
    $MovingAverage->calculate();

    $ContextCopy = clone $Context;
    $ContextCopy->trackdata()->set(Trackdata::PACE, $MovingAverage->movingAverage());

    $Plot = new AdjustedPace($ContextCopy, 'exponential_'.$i);
    $Plot->plot()->smoothing($smoothing);
    $Plot->display();

    echo '</td>';
}
?>
        </tr>
<?php
$Context = new Context($activityID, $accountID);

foreach ($allKernels as $kernelName => $kernelType) {
    echo '<tr>';
    echo '<td>#'.$kernelType.'&nbsp;'.$kernelName.'</td>';

    foreach ($widths as $i => $width) {
        echo '<td>';

        $MovingAverage = new MovingAverage\WithKernel($Context->trackdata()->pace(), $Context->trackdata()->time());
        //$MovingAverage = new MovingAverage\WithKernel($Context->trackdata()->pace(), $Context->trackdata()->distance());
        //$MovingAverage = new MovingAverage\WithKernel($Context->trackdata()->pace());
        $MovingAverage->setKernel(Kernel\Kernels::get($kernelType, $width));
        $MovingAverage->calculate();

        $ContextCopy = clone $Context;
        $ContextCopy->trackdata()->set(Trackdata::PACE, $MovingAverage->movingAverage());

        $Plot = new AdjustedPace($ContextCopy, $kernelType.'_'.$i);
        $Plot->plot()->smoothing($smoothing);
        $Plot->display();

        echo '</td>';
    }

    echo '</tr>';
}
?>
	</tbody>
</table>

<?php
echo Ajax::wrapJSasFunction('Runalyze.createFlot();');
