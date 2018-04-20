<?php

namespace Runalyze\Bundle\PlaygroundBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Runalyze\Calculation;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Model;

class VO2maxController extends Controller
{
    /**
     * @Security("has_role('ROLE_USER')")
     * @ParamConverter("activity", class="CoreBundle:Training")
     */
    public function newVO2maxActivityAction(Training $activity, Account $account, Request $request)
    {
        $conf = $this->get('app.configuration_manager')->getList();
        $activityContext = $this->get('app.activity_context.factory')->getContext($activity);
        $correctionFactor = (float)$request->query->get('cf', $conf->getVO2maxCorrectionFactor());
        $conf->set('vo2max.VO2MAX_MANUAL_CORRECTOR', $correctionFactor);
        $butterworthFF = (float)$request->query->get('ff', 0.033);
        $butterworthFilter = new \Runalyze\Mathematics\Filter\Butterworth\ButterworthFilter(new \Runalyze\Mathematics\Filter\Butterworth\Lowpass2ndOrderCoefficients($butterworthFF));
        $skipFirstSeconds = $request->query->getInt('skip', 360);
        $skipAfterSeconds = $request->query->getInt('end', 1200);
        $delta = $request->query->getInt('delta', 40);
        $deltaPre = $request->query->getInt('deltaPre', 20);
        $deltaAfter = $request->query->getInt('deltaAfter', 0);
        $hrDelta = $request->query->getInt('hrDelta', 4);

        if (!$activityContext->hasTrackdata() || !$activityContext->getTrackdata()->hasTime() || !$activityContext->getTrackdata()->hasDistance() || !$activityContext->getTrackdata()->hasHeartrate() || !$activityContext->getSport()->getInternalSport()->isRunning()) {
            return $this->render('PlaygroundBundle::error.html.twig', [
                'message' => 'This activity is not feasible for VO2max calculation.'
            ]);
        }

        $legacyTrackdata = $activityContext->getTrackdata()->getLegacyModel();
        $legacyRoute = new \Runalyze\Model\Route\Entity(!$activityContext->hasRoute() ? [] : [
            \Runalyze\Model\Route\Entity::ELEVATIONS_CORRECTED => $activityContext->getRoute()->getElevationsCorrected(),
            \Runalyze\Model\Route\Entity::ELEVATIONS_ORIGINAL => $activityContext->getRoute()->getElevationsOriginal()
        ]);

        $pace = $legacyTrackdata->get(\Runalyze\Model\Trackdata\Entity::PACE);
        $dist = $activityContext->getTrackdata()->getDistance();
        $hr = $activityContext->getTrackdata()->getHeartrate();
        $time = $activityContext->getTrackdata()->getTime();
        $trackdataNum = count($time);
        $elev = $activityContext->hasRoute() && $activityContext->getRoute()->hasElevations() ? $activityContext->getRoute()->getElevations() : array_fill(0, $trackdataNum, 0);
        $hrMax = \Runalyze\Configuration::Data()->HRmax();

        $timeFactor = [];
        $gap = $pace;
        $timeFactor = array_fill(0, $trackdataNum, 1.0);
        $algorithm = new \Runalyze\Sports\Running\GradeAdjustedPace\Algorithm\Minetti();

        if ($legacyRoute->hasElevations()) {
            $gradientCalc = new \Runalyze\Calculation\Route\Gradient();
            $gradientCalc->setDataFrom($legacyRoute, $legacyTrackdata);
            $gradientCalc->setMovingAverageKernel(new \Runalyze\Calculation\Math\MovingAverage\Kernel\Uniform(20));
            $gradientCalc->calculate();
            $gradient = $gradientCalc->getSeries();

            if ($butterworthFF <= 0.25) {
                $gradient = $butterworthFilter->filterFilter($gradient);
            }

            foreach (array_keys($gap) as $i) {
                $timeFactor[$i] = $algorithm->getTimeFactor($gradient[$i] / 100.0);
                $gap[$i] *= $timeFactor[$i];
            }
        } else {
            $gradient = array_fill(0, $trackdata->num(), 0.0);
        }

        $finder = new \Runalyze\Mathematics\DataAnalysis\ConstantSegmentFinder($hr, $time);
        $finder->setMinimumIndexDiff($delta + $deltaPre + $deltaAfter);
        $finder->setMaximumIndexDiff($delta + $deltaPre + $deltaAfter);
        $finder->setConstantDelta($hrDelta);
        $segments = $finder->findConstantSegments();

        $allEstimates = [];
        $avgEstimates = [];
        $gapEstimates = [];
        $avgGapEstimates = [];
        $avgGradientEstimates = [];
        $vo2maxEstimate = new \Runalyze\Calculation\JD\LegacyEffectiveVO2max();
        //$vo2maxEstimate->setCorrector(new \Runalyze\Calculation\JD\LegacyEffectiveVO2maxCorrector());

        foreach ($segments as $i => $segment) {
            if ($deltaPre > 0) {
                $pre_i = 1;

                while ($time[$segment[0] + $pre_i] - $time[$segment[0]] < $deltaPre) {
                    ++$pre_i;
                }

                $segment[0] += $pre_i;
            }

            if ($deltaAfter > 0) {
                $after_i = 1;

                while ($time[$segment[1]] - $time[$segment[1] - $after_i] < $deltaAfter) {
                    ++$after_i;
                }

                $segment[1] -= $after_i;
            }

            if ($segment[0] >= $segment[1]) {
                continue;
            }

            $hrAvg = array_sum(array_slice($hr, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);
            $distDelta = $dist[$segment[1]] - $dist[$segment[0]];
            $timeDelta = $time[$segment[1]] - $time[$segment[0]];
            $elevDelta = $elev[$segment[1]] - $elev[$segment[0]];

            $vo2maxEstimate->fromPaceAndHR($distDelta, $timeDelta, $hrAvg / $hrMax);
            $estimate = $vo2maxEstimate->value();

            // TODO: For std/abs. error/mad, there's a difference between applying $correctionFactor before or after!
            $allEstimates[] = $estimate;

            if ($time[$segment[1]] < $skipAfterSeconds) {
                $lastValidSegmentIndex = $i;
            }

            $avgPace = array_sum(array_slice($pace, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);
            $avgGap = array_sum(array_slice($gap, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);
            $avgGradient = array_sum(array_slice($gradient, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);
            $avgGapFactor = array_sum(array_slice($timeFactor, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);

            $vo2maxEstimate->fromPaceAndHR(1.0, $avgPace, $hrAvg / $hrMax);
            $avgEstimate = $vo2maxEstimate->value();
            $avgEstimates[] = $avgEstimate;

            $vo2maxEstimate->fromPaceAndHR($distDelta, $timeDelta * $algorithm->getTimeFactor($distDelta > 0 ? $elevDelta / 1000 / $distDelta : 0.0), $hrAvg / $hrMax);
            $gapEstimate = $vo2maxEstimate->value();
            $gapEstimates[] = $gapEstimate;

            //$vo2maxEstimate->fromPaceAndHR(1.0, $avgGap, $hrAvg / $hrMax);
            $vo2maxEstimate->fromPaceAndHR($distDelta, $timeDelta * $avgGapFactor, $hrAvg / $hrMax);
            $avgGapEstimate = $vo2maxEstimate->value();
            $avgGapEstimates[] = $avgGapEstimate;

            $vo2maxEstimate->fromPaceAndHR($distDelta, $timeDelta * $algorithm->getTimeFactor($avgGradient / 100.0), $hrAvg / $hrMax);
            $avgGradientEstimate = $vo2maxEstimate->value();
            $avgGradientEstimates[] = $avgGradientEstimate;
        }

        return $this->render('PlaygroundBundle::new-vo2max.html.twig', [
            'conf' => $conf,
            'context' => $activityContext,
            'segments' => $segments,
            'estimates' => [
                'totalPace' => $allEstimates,
                'avgPace' => $avgEstimates,
                'gap' => $gapEstimates,
                'avgGap' => $avgGapEstimates,
                'avgGradient' => $avgGradientEstimates
            ],
            'settings' => [
                'delta' => $delta,
                'deltaPre' => $deltaPre,
                'deltaAfter' => $deltaAfter,
                'hrDelta' => $hrDelta,
                'skipBefore' => $skipFirstSeconds,
                'skipAfter' => $skipAfterSeconds,
                'butterworthFF' => $butterworthFF
            ],
            'athlete' => [
                'hrMax' => $hrMax,
                'correctionFactor' => $correctionFactor
            ],
            'stream' => [
                'time' => $time,
                'dist' => $dist,
                'elev' => $elev,
                'elevButterworth' => $butterworthFF <= 0.25 ? $butterworthFilter->filterFilter($elev) : $elev,
                'hr' => $hr,
                'pace' => $pace,
                'gap' => $gap,
                'gapFactor' => $timeFactor,
                'gradient' => $gradient
            ]
        ]);
    }

    /**
     * @Security("has_role('ROLE_USER')")
     */
    public function newVO2maxTableAction(Account $account, Request $request)
    {
        $prefix = $this->getParameter('database_prefix');
        $conf = $this->get('app.configuration_manager')->getList();
        $correctionFactor = (float)$request->query->get('cf', $conf->getVO2maxCorrectionFactor());
        $conf->set('vo2max.VO2MAX_MANUAL_CORRECTOR', $correctionFactor);
        $jsonData = [];
        $data = [];

        $sql = 'SELECT
                `t`.`heartrate` as `tr_heartrate`,
                `t`.`time` as `tr_time`,
                `t`.`distance` as `tr_distance`,
                `a`.`id`,
                `a`.`time`,
                `a`.`s`,
                `a`.`distance`,
                `a`.`vo2max_with_elevation`,
                `a`.`accountid`,
                `r`.`elevations_corrected`,
                `r`.`elevations_original`
            FROM `'.$prefix.'training` AS `a`
            JOIN `'.$prefix.'trackdata` AS `t` ON `t`.`activityid` = `a`.`id`
            JOIN `'.$prefix.'route` AS `r` ON `a`.`routeid` = `r`.`id`
            WHERE  `a`.`accountid` = '.$account->getId().' AND
                `a`.`sportid` = '.\Runalyze\Configuration::General()->runningSport().' AND
                `t`.`time` IS NOT NULL AND
                `t`.`distance` IS NOT NULL AND
                `t`.`heartrate` IS NOT NULL AND
                `a`.`use_vo2max` = 1 AND
                `a`.`s` > '.$request->query->getInt('minTime', 720).'
            ORDER BY `a`.`time` DESC LIMIT '.$request->query->getInt('limit', 100);

        $useMad = $request->query->get('error', 'mad') == 'mad';
        $skipFirstSeconds = $request->query->getInt('skip', 360);
        $skipAfterSeconds = $request->query->getInt('end', 1200);
        $delta = $request->query->getInt('delta', 40);
        $deltaPre = $request->query->getInt('deltaPre', 20);
        $deltaAfter = $request->query->getInt('deltaAfter', 0);
        $hrDelta = $request->query->getInt('hrDelta', 4);
        $butterworthFF = (float)$request->query->get('ff', 0.033);
        $butterworthFilter = new \Runalyze\Mathematics\Filter\Butterworth\ButterworthFilter(new \Runalyze\Mathematics\Filter\Butterworth\Lowpass2ndOrderCoefficients($butterworthFF));

        $stmt = $this->getDoctrine()->getManager()->getConnection()->prepare($sql);
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            $trackdata = new \Runalyze\Model\Trackdata\Entity([
                \Runalyze\Model\Trackdata\Entity::TIME => $row['tr_time'],
                \Runalyze\Model\Trackdata\Entity::DISTANCE => $row['tr_distance'],
                \Runalyze\Model\Trackdata\Entity::HEARTRATE => $row['tr_heartrate'],
            ]);
            $route = new \Runalyze\Model\Route\Entity([
                \Runalyze\Model\Route\Entity::ELEVATIONS_CORRECTED => $row['elevations_corrected'],
                \Runalyze\Model\Route\Entity::ELEVATIONS_ORIGINAL => $row['elevations_original']
            ]);

            $pace = $trackdata->get(\Runalyze\Model\Trackdata\Entity::PACE);
            $dist = $trackdata->get(\Runalyze\Model\Trackdata\Entity::DISTANCE);
            $hr = $trackdata->get(\Runalyze\Model\Trackdata\Entity::HEARTRATE);
            $time = $trackdata->get(\Runalyze\Model\Trackdata\Entity::TIME);
            $elev = $route->hasElevations() ? $route->elevations() : array_fill(0, $trackdata->num(), 0);
            $hrMax = \Runalyze\Configuration::Data()->HRmax();

            if (empty($hr)) {
                continue;
            }

            $finder = new \Runalyze\Mathematics\DataAnalysis\ConstantSegmentFinder($hr, $time);
            $finder->setMinimumIndexDiff($delta + $deltaPre + $deltaAfter);
            $finder->setMaximumIndexDiff($delta + $deltaPre + $deltaAfter);
            $finder->setConstantDelta($hrDelta);
            $segments = $finder->findConstantSegments();
            $firstValidSegmentIndex = 0;
            $lastValidSegmentIndex = -1;
            $allEstimates = [];
            $avgEstimates = [];
            $gapEstimates = [];
            $avgGapEstimates = [];
            $avgGradientEstimates = [];

            $gap = $pace;
            $timeFactor = array_fill(0, $trackdata->num(), 1.0);
            $algorithm = new \Runalyze\Sports\Running\GradeAdjustedPace\Algorithm\Minetti();

            if ($route->hasElevations()) {
                $gradientCalc = new \Runalyze\Calculation\Route\Gradient();
                $gradientCalc->setDataFrom($route, $trackdata);
                $gradientCalc->setMovingAverageKernel(new \Runalyze\Calculation\Math\MovingAverage\Kernel\Uniform(20));
                $gradientCalc->calculate();
                $gradient = $gradientCalc->getSeries();

                if ($butterworthFF <= 0.25) {
                    $gradient = $butterworthFilter->filterFilter($gradient);
                }

                foreach (array_keys($gap) as $i) {
                    $timeFactor[$i] = $algorithm->getTimeFactor($gradient[$i] / 100.0);
                    $gap[$i] *= $timeFactor[$i];
                }
            } else {
                $gradient = array_fill(0, $trackdata->num(), 0.0);
            }

            $vo2maxEstimate = new \Runalyze\Calculation\JD\LegacyEffectiveVO2max();
            //$vo2maxEstimate->setCorrector(new \Runalyze\Calculation\JD\LegacyEffectiveVO2maxCorrector());

            foreach ($segments as $i => $segment) {
                if ($deltaPre > 0) {
                    $pre_i = 1;
    
                    while ($time[$segment[0] + $pre_i] - $time[$segment[0]] < $deltaPre) {
                        ++$pre_i;
                    }
    
                    $segment[0] += $pre_i;
                }

                if ($deltaAfter > 0) {
                    $after_i = 1;
    
                    while ($time[$segment[1]] - $time[$segment[1] - $after_i] < $deltaAfter) {
                        ++$after_i;
                    }
    
                    $segment[1] -= $after_i;
                }

                if ($segment[0] >= $segment[1]) {
                    continue;
                }

                $hrAvg = array_sum(array_slice($hr, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);
                $distDelta = $dist[$segment[1]] - $dist[$segment[0]];
                $timeDelta = $time[$segment[1]] - $time[$segment[0]];
                $elevDelta = $elev[$segment[1]] - $elev[$segment[0]];

                $vo2maxEstimate->fromPaceAndHR($distDelta, $timeDelta, $hrAvg / $hrMax);
                $estimate = $vo2maxEstimate->value();

                if ($time[$segment[0]] <= $skipFirstSeconds) {
                    $firstValidSegmentIndex = $i + 1;
                }

                $allEstimates[] = $estimate;

                if ($time[$segment[1]] < $skipAfterSeconds) {
                    $lastValidSegmentIndex = $i;
                }

                $avgPace = array_sum(array_slice($pace, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);
                $avgGap = array_sum(array_slice($gap, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);
                $avgGradient = array_sum(array_slice($gradient, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);
                $avgGapFactor = array_sum(array_slice($timeFactor, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);

                $vo2maxEstimate->fromPaceAndHR(1.0, $avgPace, $hrAvg / $hrMax);
                $avgEstimate = $vo2maxEstimate->value();
                $avgEstimates[] = $avgEstimate;

                $vo2maxEstimate->fromPaceAndHR($distDelta, $timeDelta * $algorithm->getTimeFactor($distDelta > 0.0 ? $elevDelta / 1000 / $distDelta : 0.0), $hrAvg / $hrMax);
                $gapEstimate = $vo2maxEstimate->value();
                $gapEstimates[] = $gapEstimate;

                //$vo2maxEstimate->fromPaceAndHR(1.0, $avgGap, $hrAvg / $hrMax);
                $vo2maxEstimate->fromPaceAndHR($distDelta, $timeDelta * $avgGapFactor, $hrAvg / $hrMax);
                $avgGapEstimate = $vo2maxEstimate->value();
                $avgGapEstimates[] = $avgGapEstimate;

                $vo2maxEstimate->fromPaceAndHR($distDelta, $timeDelta * $algorithm->getTimeFactor($avgGradient / 100.0), $hrAvg / $hrMax);
                $avgGradientEstimate = $vo2maxEstimate->value();
                $avgGradientEstimates[] = $avgGradientEstimate;
            }

            $estimates = array_slice($avgGapEstimates, $firstValidSegmentIndex, 1 + $lastValidSegmentIndex - $firstValidSegmentIndex);
            $estimatesNoGap = array_slice($allEstimates, $firstValidSegmentIndex, 1 + $lastValidSegmentIndex - $firstValidSegmentIndex);

            $numEstimates = count($estimates);

            if ($numEstimates > 0) {
                $mean = array_sum($estimates) / $numEstimates;

                if ($mean <= 10 || $mean >= 90) {
                    continue;
                }

                $middle_index = (int)floor($numEstimates / 2);
                sort($estimates, SORT_NUMERIC);
                $median = $estimates[$middle_index];
                if ($numEstimates % 2 == 0) {
                    $median = ($median + $estimates[$middle_index - 1]) / 2;
                }

                $std = $numEstimates == 1 ? 0 : sqrt(array_sum(array_map(function ($v) use ($mean) {
                        return pow($v - $mean, 2);
                    }, $estimates)) / ($numEstimates == 1 ? 1 : $numEstimates - 1));

                sort($estimatesNoGap, SORT_NUMERIC);
                $medianNoGap = $estimatesNoGap[$middle_index];
                if ($numEstimates % 2 == 0) {
                    $medianNoGap = ($medianNoGap + $estimatesNoGap[$middle_index - 1]) / 2;
                }

                $medianDev = array_map(function($v) use ($median) {
                    return abs($v - $median);
                }, $estimates);
                sort($medianDev, SORT_NUMERIC);
                $mad = $medianDev[$middle_index];
                if ($numEstimates % 2 == 0) {
                    $mad = ($mad + $medianDev[$middle_index - 1]) / 2;
                }

                $error = $std / sqrt($numEstimates);
                $errorMad = $mad / sqrt($numEstimates);

                $data[] = [
                    'row' => [
                        'id' => $row['id'],
                        'time' => $row['time'],
                        's' => $row['s'],
                        'distance' => $row['distance'],
                        'vo2max_with_elevation' => $row['vo2max_with_elevation']
                    ],
                    'median' => $median,
                    'mean' => $mean,
                    'medianNoGap' => $medianNoGap,
                    'error' => $error * $correctionFactor,
                    'mad' => $errorMad * $correctionFactor
                ];

                // Hint: The table applies $correctionFactor after all other calculations have been done
                $jsonData[] = [(string)$row['time'].'000', $correctionFactor * $row['vo2max_with_elevation'], $correctionFactor * $median, $correctionFactor * ($useMad ? $errorMad : $error)];
            }
        }

        return $this->render('PlaygroundBundle::new-vo2max-table.html.twig', [
            'conf' => $conf,
            'data' => $data,
            'settings' => [
                'delta' => $delta,
                'deltaPre' => $deltaPre,
                'deltaAfter' => $deltaAfter,
                'hrDelta' => $hrDelta,
                'skipBefore' => $skipFirstSeconds,
                'skipAfter' => $skipAfterSeconds,
                'butterworthFF' => $butterworthFF
            ],
            'athlete' => [
                'hrMax' => $hrMax,
                'correctionFactor' => $correctionFactor
            ],
            'jsonData' => json_encode($jsonData)
        ]);
    }
}
