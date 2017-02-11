<?php

namespace Runalyze\Bundle\PlaygroundBundle\Controller;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\PlaygroundBundle\Component\MovingAverage\AdjustedPace;
use Runalyze\Calculation\Math\MovingAverage;
use Runalyze\Calculation\Math\MovingAverage\Kernel;
use Runalyze\Mathematics\Filter\Butterworth\ButterworthFilter;
use Runalyze\Mathematics\Filter\Butterworth\Lowpass2ndOrderCoefficients;
use Runalyze\Model\Trackdata\Entity as Trackdata;
use Runalyze\View\Activity\Context;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class MovingAverageController extends Controller
{
    /**
     * @Security("has_role('ROLE_USER')")
     */
    public function movingAverageAction(Account $account, Request $request)
    {
        $Frontend = new \Frontend(true, $this->get('security.token_storage'));

        $allKernels = Kernel\Kernels::getEnum();

        $activityID = $request->query->getInt('activityID', 3686);
        $smoothing = $request->query->getBoolean('smoothing', false);
        $precision = $request->query->get('precision', '1000points');
        $widths = [15, 25, 35, 45, 60]; // [0.05, 0.1, 0.2, 0.35, 0.5];
        $alphas = [0.99, 0.975, 0.95, 0.90, 0.75];
        $ffs = [0.25, 0.20, 0.15, 0.10, 0.05];

        \Runalyze\Configuration::ActivityView()->plotPrecision()->set($precision);

        $Context = new Context($activityID, $account->getId());

        if (!$Context->hasTrackdata() || !$Context->trackdata()->has(Trackdata::DISTANCE) || !$Context->trackdata()->has(Trackdata::TIME)) {
            return $this->json(['error' => 'Activity cannot be found or has no valid trackdata with distance and time data.']);
        }

        $pace = $Context->trackdata()->pace();
        $distance = $Context->trackdata()->distance();
        $time = $Context->trackdata()->time();

        $plotNormal = new AdjustedPace($Context, '');
        $plotNormal->plot()->smoothing($smoothing);

        $plotsButterworth = [];

        foreach ($ffs as $i => $ff) {
            $filter = new ButterworthFilter(new Lowpass2ndOrderCoefficients($ff));

            $ContextCopy = clone $Context;
            $ContextCopy->trackdata()->set(Trackdata::PACE, $filter->filterFilter($pace));

            $plot = new AdjustedPace($ContextCopy, 'butterworth_'.$i);
            $plot->plot()->smoothing(false);

            $plotsButterworth[] = $plot;
        }

        // Exponential does not smooth but calculate a 'global' average (up to each time point)
        /*$plotsExponential = [];

        foreach ($alphas as $i => $alpha) {
            $MovingAverage = new MovingAverage\Exponential($pace, $distance);
            $MovingAverage->setAlpha($alpha);
            $MovingAverage->calculate();

            $ContextCopy = clone $Context;
            $ContextCopy->trackdata()->set(Trackdata::PACE, $MovingAverage->movingAverage());

            $plot = new AdjustedPace($ContextCopy, 'exponential_'.$i);
            $plot->plot()->smoothing($smoothing);

            $plotsExponential[] = $plot;
        }*/

        $kernels = [];
        $plotsKernel = [];

        foreach ($allKernels as $kernelName => $kernelType) {
            $kernels[] = '#'.$kernelType.'&nbsp;'.$kernelName;
            $plots = [];

            foreach ($widths as $i => $width) {
                $MovingAverage = new MovingAverage\WithKernel($pace, $time);
                //$MovingAverage = new MovingAverage\WithKernel($pace, $distance);
                //$MovingAverage = new MovingAverage\WithKernel($pace);
                $MovingAverage->setKernel(Kernel\Kernels::get($kernelType, $width));
                $MovingAverage->calculate();

                $ContextCopy = clone $Context;
                $ContextCopy->trackdata()->set(Trackdata::PACE, $MovingAverage->movingAverage());

                $plot = new AdjustedPace($ContextCopy, $kernelType.'_'.$i);
                $plot->plot()->smoothing($smoothing);

                $plots[] = $plot;
            }

            $plotsKernel[] = $plots;
        }

        return $this->render('@Playground/moving-average.html.twig', [
            'widths' => $widths,
            'ffs' => $ffs,
            'alphas' => $alphas,
            'plotNormal' => $plotNormal,
            'plotsButterworth' => $plotsButterworth,
            //'plotsExponential' => $plotsExponential,
            'kernels' => $kernels,
            'plotsKernel' => $plotsKernel
        ]);
    }
}
