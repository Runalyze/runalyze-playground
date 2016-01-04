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

$activityID = isset($_GET['activityID']) ? $_GET['activityID'] : null;
$accountID = isset($_GET['accountID']) ? $_GET['accountID'] : 0;
$smoothing = isset($_GET['smoothing']) ? ($_GET['smoothing'] == '1') : true;
$widths = [0.05, 0.1, 0.2, 0.5, 1.0, 2.0];

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
            <?php foreach ($widths as $width) echo '<th>'.$width.'</th>'; ?>
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
<?php
$Context = new Context($activityID, $accountID);

foreach ($allKernels as $kernelName => $kernelType) {
    echo '<tr>';
    echo '<td>'.$kernelName.' (id = '.$kernelType.')</td>';

    foreach ($widths as $i => $width) {
        echo '<td>';

        $MovingAverage = new MovingAverage\WithKernel($Context->trackdata()->pace(), $Context->trackdata()->distance());
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