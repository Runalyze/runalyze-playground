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
    /** @var float */
    protected $ButterworthFilterFrequency;

    /** @var \Runalyze\Mathematics\Filter\Butterworth\ButterworthFilter */
    protected $ButterworthFilter;

    /** @var \Runalyze\Calculation\Math\MovingAverage\Kernel\Uniform */
    protected $MovingAverageForGradient;

    /** @var \Runalyze\Sports\Running\GradeAdjustedPace\Algorithm\Minetti */
    protected $GradeAdjustedPaceAlgorithm;

    /** @var float */
    protected $VO2maxCorrectionFactor;

    /** @var int [bpm] */
    protected $HeartRateMaximum;

    /** @var int [s] */
    protected $SegmentsDelta;

    /** @var int [s] */
    protected $SegmentsDeltaBefore;

    /** @var int [s] */
    protected $SegmentsSkipBefore;

    /** @var int [s] */
    protected $SegmentsSkipAfter;

    /** @var int [bpm] */
    protected $SegmentsHeartRateDelta;

    /** @var bool */
    protected $TableUseMad;

    /** @var int [s] */
    protected $TableMinTime;

    /** @var int */
    protected $TableLimit;

    /** @var string */
    protected $TableEndDateString;

    /** @var false|int */
    protected $TableEndDate;

    /** @var float */
    protected $EmaAlpha;

    /** @var float */
    protected $EmaWeightFactor;

    protected function setObjectsFromSettings(Request $request)
    {
        $this->ButterworthFilterFrequency = (float)$request->query->get('ff', 0.033);
        $this->ButterworthFilter = new \Runalyze\Mathematics\Filter\Butterworth\ButterworthFilter(
            new \Runalyze\Mathematics\Filter\Butterworth\Lowpass2ndOrderCoefficients($this->ButterworthFilterFrequency)
        );
        $this->MovingAverageForGradient = new \Runalyze\Calculation\Math\MovingAverage\Kernel\Uniform($request->query->getInt('gradientSmooth', 20));
        $this->GradeAdjustedPaceAlgorithm = new \Runalyze\Sports\Running\GradeAdjustedPace\Algorithm\Minetti();

        $this->SegmentsDelta = $request->query->getInt('delta', 30);
        $this->SegmentsDeltaBefore = $request->query->getInt('deltaBefore', 0);
        $this->SegmentsSkipBefore = $request->query->getInt('skip', 360);
        $this->SegmentsSkipAfter = $request->query->getInt('end', 1200);
        $this->SegmentsHeartRateDelta = $request->query->getInt('hrDelta', 2);
        $this->SegmentsHeartRateDeltaBefore = $request->query->getInt('hrDeltaBefore', $this->SegmentsHeartRateDelta);

        $this->TableUseMad = $request->query->get('error', 'mad') == 'mad';
        $this->TableMinTime = $request->query->getInt('minTime', $this->SegmentsSkipBefore);
        $this->TableLimit = $request->query->getInt('limit', 100);
        $this->TableEndDate = $request->query->get('date', false);
        $this->TableEndDateString = (string)$this->TableEndDate;

        if ($this->TableEndDate) {
            try {
                $dateTime = \Runalyze\Util\LocalTime::fromString($this->TableEndDate);
                $this->TableEndDate = $dateTime->setTime(23, 59)->getTimestamp();
            } catch (\Exception $e) {
                $this->TableEndDate = false;
            }
        }

        $this->EmaAlpha = $request->query->get('emaAlpha', 0.25);
        $this->EmaWeightFactor = $request->query->get('emaWeightFactor', 0.5);
    }

    protected function setAthleteSettings()
    {
        $this->VO2maxCorrectionFactor = $this->get('app.configuration_manager')->getList()->getVO2maxCorrectionFactor();
        $this->HeartRateMaximum = \Runalyze\Configuration::Data()->HRmax();
    }

    /**
     * @return array
     */
    protected function getSettingsForRendering(Request $request)
    {
        $yLim = explode(',', $request->query->get('ylim', ''));

        return [
            'delta' => $this->SegmentsDelta,
            'deltaBefore' => $this->SegmentsDeltaBefore,
            'hrDelta' => $this->SegmentsHeartRateDelta,
            'hrDeltaBefore' => $this->SegmentsHeartRateDeltaBefore,
            'skipBefore' => $this->SegmentsSkipBefore,
            'skipAfter' => $this->SegmentsSkipAfter,
            'gradientSmooth' => $request->query->getInt('gradientSmooth', 20),
            'butterworthFF' => $this->ButterworthFilterFrequency,
            'ylim' => count($yLim) == 2 ? array_map(function($v){return (int)$v;}, $yLim) : [],
            'yLim' => count($yLim) == 2 ? $yLim[0].','.$yLim[1] : '',
            'tableUseMad' => $this->TableUseMad,
            'tableMinTime' => $this->TableMinTime,
            'tableLimit' => $this->TableLimit,
            'tableEndDate' => $this->TableEndDateString,
            'emaAlpha' => $this->EmaAlpha,
            'emaWeightFactor' => $this->EmaWeightFactor
        ];
    }

    /**
     * @Security("has_role('ROLE_USER')")
     * @ParamConverter("activity", class="CoreBundle:Training")
     */
    public function newVO2maxActivityAction(Training $activity, Account $account, Request $request)
    {
        if ($activity->getAccount()->getId() != $account->getId()) {
            throw $this->createNotFoundException();
        }

        $this->setObjectsFromSettings($request);
        $this->setAthleteSettings();

        if (!$activity->hasTrackdata() || !$activity->getTrackdata()->hasTime() || !$activity->getTrackdata()->hasDistance() || !$activity->getTrackdata()->hasHeartrate() || !$activity->getSport()->getInternalSport()->isRunning()) {
            return $this->render('PlaygroundBundle::error.html.twig', [
                'message' => 'This activity is not feasible for VO2max calculation.'
            ]);
        }

        $legacyTrackdata = $activity->getTrackdata()->getLegacyModel();
        $legacyRoute = new \Runalyze\Model\Route\Entity(!$activity->hasRoute() ? [] : [
            \Runalyze\Model\Route\Entity::ELEVATIONS_CORRECTED => $activity->getRoute()->getElevationsCorrected(),
            \Runalyze\Model\Route\Entity::ELEVATIONS_ORIGINAL => $activity->getRoute()->getElevationsOriginal()
        ]);

        list($gap, $timeFactor, $gradient, $segments, $allEstimates, $avgEstimates, $gapEstimates, $avgGapEstimates, $avgGradientEstimates, $firstValidSegmentIndex, $lastValidSegmentIndex) = $this->getSegmentEstimates($legacyTrackdata, $legacyRoute);

        $time = $legacyTrackdata->get(\Runalyze\Model\Trackdata\Entity::TIME);
        $dist = $legacyTrackdata->get(\Runalyze\Model\Trackdata\Entity::DISTANCE);
        $pace = $legacyTrackdata->get(\Runalyze\Model\Trackdata\Entity::PACE);
        $hr = $legacyTrackdata->get(\Runalyze\Model\Trackdata\Entity::HEARTRATE);
        $elev = $legacyRoute->hasElevations() ? $legacyRoute->elevations() : array_fill(0, count($time), 0);

        $allEstimates = array_map(function($v){ return $this->VO2maxCorrectionFactor * $v; }, $allEstimates);
        $avgEstimates = array_map(function($v){ return $this->VO2maxCorrectionFactor * $v; }, $avgEstimates);
        $gapEstimates = array_map(function($v){ return $this->VO2maxCorrectionFactor * $v; }, $gapEstimates);
        $avgGapEstimates = array_map(function($v){ return $this->VO2maxCorrectionFactor * $v; }, $avgGapEstimates);
        $avgGradientEstimates = array_map(function($v){ return $this->VO2maxCorrectionFactor * $v; }, $avgGradientEstimates);

        return $this->render('PlaygroundBundle::new-vo2max.html.twig', [
            'activity' => $activity,
            'segments' => $segments,
            'estimates' => [
                'totalPace' => $allEstimates,
                'avgPace' => $avgEstimates,
                'gap' => $gapEstimates,
                'avgGap' => $avgGapEstimates,
                'avgGradient' => $avgGradientEstimates
            ],
            'settings' => $this->getSettingsForRendering($request),
            'athlete' => ['hrMax' => $this->HeartRateMaximum],
            'stream' => [
                'time' => $time,
                'dist' => $dist,
                'elev' => $elev,
                'elevButterworth' => $this->ButterworthFilterFrequency <= 0.25 ? $this->ButterworthFilter->filterFilter($elev) : $elev,
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
        $this->setObjectsFromSettings($request);
        $this->setAthleteSettings();

        $prefix = $this->getParameter('database_prefix');
        $runningId = \Runalyze\Configuration::General()->runningSport();
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
                `a`.`pulse_avg`,
                `a`.`vo2max_with_elevation`,
                `a`.`accountid`,
                `r`.`elevations_corrected`,
                `r`.`elevations_original`
            FROM `'.$prefix.'training` AS `a`
            JOIN `'.$prefix.'trackdata` AS `t` ON `t`.`activityid` = `a`.`id`
            JOIN `'.$prefix.'route` AS `r` ON `a`.`routeid` = `r`.`id`
            WHERE  `a`.`accountid` = '.$account->getId().' AND
                `a`.`sportid` = '.$runningId.' AND
                `t`.`time` IS NOT NULL AND
                `t`.`distance` IS NOT NULL AND
                `t`.`heartrate` IS NOT NULL AND
                `a`.`use_vo2max` = 1 AND
                `a`.`s` > '.$this->TableMinTime.'
                '.($this->TableEndDate > 0 ? 'AND `a`.`time` <= '.(int)$this->TableEndDate : '').'
            ORDER BY `a`.`time` DESC LIMIT '.$this->TableLimit;

        $stmt = $this->getDoctrine()->getManager()->getConnection()->prepare($sql);
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            $legacyTrackdata = new \Runalyze\Model\Trackdata\Entity([
                \Runalyze\Model\Trackdata\Entity::TIME => $row['tr_time'],
                \Runalyze\Model\Trackdata\Entity::DISTANCE => $row['tr_distance'],
                \Runalyze\Model\Trackdata\Entity::HEARTRATE => $row['tr_heartrate'],
            ]);
            $legacyRoute = new \Runalyze\Model\Route\Entity([
                \Runalyze\Model\Route\Entity::ELEVATIONS_CORRECTED => $row['elevations_corrected'],
                \Runalyze\Model\Route\Entity::ELEVATIONS_ORIGINAL => $row['elevations_original']
            ]);

            if (!$legacyTrackdata->has(\Runalyze\Model\Trackdata\Entity::HEARTRATE)) {
                continue;
            }

            list($gap, $timeFactor, $gradient, $segments, $allEstimates, $avgEstimates, $gapEstimates, $avgGapEstimates, $avgGradientEstimates, $firstValidSegmentIndex, $lastValidSegmentIndex) = $this->getSegmentEstimates($legacyTrackdata, $legacyRoute);

            $estimates = array_slice($avgGapEstimates, $firstValidSegmentIndex, 1 + $lastValidSegmentIndex - $firstValidSegmentIndex);
            $estimatesNoGap = array_slice($allEstimates, $firstValidSegmentIndex, 1 + $lastValidSegmentIndex - $firstValidSegmentIndex);

            if (!empty($estimates)) {
                list($median, $mean, $medianNoGap, $error, $errorMad) = $this->getStatisticsForEstimates($estimates, $estimatesNoGap);

                if ($mean <= 10 || $mean >= 90) {
                    continue;
                }

                $data[] = [
                    'row' => [
                        'id' => $row['id'],
                        'time' => $row['time'],
                        's' => $row['s'],
                        'distance' => $row['distance'],
                        'hr' => $row['pulse_avg'],
                        'vo2max_with_elevation' => $row['vo2max_with_elevation']
                    ],
                    'median' => $median,
                    'mean' => $mean,
                    'medianNoGap' => $medianNoGap,
                    'error' => $error,
                    'mad' => $errorMad
                ];

                $jsonData[] = [(string)$row['time'].'000', $this->VO2maxCorrectionFactor * $row['vo2max_with_elevation'], $this->VO2maxCorrectionFactor * $median, $this->VO2maxCorrectionFactor * ($this->TableUseMad ? $errorMad : $error)];
            }
        }

        return $this->render('PlaygroundBundle::new-vo2max-table.html.twig', [
            'settings' => $this->getSettingsForRendering($request),
            'data' => $data,
            'jsonData' => json_encode($jsonData)
        ]);
    }

    /**
     * @param \Runalyze\Model\Trackdata\Entity $legacyTrackdata
     * @param \Runalyze\Model\Route\Entity $legacyRoute
     * @return array [gap, timeFactor, gradient, segments, allEstimates, avgEstimates, gapEstimates, avgGapEstimates, avgGradientEstimates, firstValidSegmentIndex, lastValidSegmentIndex]
     */
    protected function getSegmentEstimates(\Runalyze\Model\Trackdata\Entity $legacyTrackdata, \Runalyze\Model\Route\Entity $legacyRoute)
    {
        $delta = $this->SegmentsDelta;
        $hrDelta = $this->SegmentsHeartRateDelta;
        $skipFirstSeconds = $this->SegmentsSkipBefore;
        $skipAfterSeconds = $this->SegmentsSkipAfter;

        $pace = $legacyTrackdata->get(\Runalyze\Model\Trackdata\Entity::PACE);
        $dist = $legacyTrackdata->get(\Runalyze\Model\Trackdata\Entity::DISTANCE);
        $hr = $legacyTrackdata->get(\Runalyze\Model\Trackdata\Entity::HEARTRATE);
        $time = $legacyTrackdata->get(\Runalyze\Model\Trackdata\Entity::TIME);
        $trackdataNum = count($time);
        $elev = $legacyRoute->hasElevations() ? $legacyRoute->elevations() : array_fill(0, $trackdataNum, 0);

        $gap = $pace;
        $timeFactor = array_fill(0, $trackdataNum, 1.0);

        if ($legacyRoute->hasElevations()) {
            $gradientCalc = new \Runalyze\Calculation\Route\Gradient();
            $gradientCalc->setDataFrom($legacyRoute, $legacyTrackdata);
            $gradientCalc->setMovingAverageKernel($this->MovingAverageForGradient);
            $gradientCalc->calculate();
            $gradient = $gradientCalc->getSeries();

            if ($this->ButterworthFilterFrequency <= 0.25) {
                $gradient = $this->ButterworthFilter->filterFilter($gradient);
            }

            foreach (array_keys($gap) as $i) {
                $timeFactor[$i] = $this->GradeAdjustedPaceAlgorithm->getTimeFactor($gradient[$i] / 100.0);
                $gap[$i] *= $timeFactor[$i];
            }
        } else {
            $gradient = array_fill(0, $trackdataNum, 0.0);
        }

        $finder = new \Runalyze\Mathematics\DataAnalysis\ConstantSegmentFinder($hr, $time);
        $finder->setMinimumIndexDiff($delta);
        $finder->setMaximumIndexDiff($delta);
        $finder->setConstantDelta($hrDelta);
        $segments = $finder->findConstantSegments();

        $firstValidSegmentIndex = 0;
        $lastValidSegmentIndex = -1;
        $allEstimates = [];
        $avgEstimates = [];
        $gapEstimates = [];
        $avgGapEstimates = [];
        $avgGradientEstimates = [];
        $vo2maxEstimate = new \Runalyze\Calculation\JD\LegacyEffectiveVO2max();

        if ($this->SegmentsDeltaBefore > 0) {
            foreach ($segments as $i => $segment) {
                $hrSegment = array_slice($hr, $segment[0], $segment[1] - $segment[0]);
                $hrSegmentMax = max($hrSegment);
                $hrSegmentMin = min($hrSegment);
                $beforeI = $segment[0];

                while ($beforeI > 0 && $time[$segment[0]] - $time[$beforeI] < $this->SegmentsDeltaBefore) {
                    --$beforeI;

                    if ($hr[$beforeI] < $hrSegmentMax - $this->SegmentsHeartRateDeltaBefore || $hr[$beforeI] > $hrSegmentMin + $this->SegmentsHeartRateDeltaBefore) {
                        unset($segments[$i]);

                        break;
                    }
                }
            }

            $segments = array_values($segments);
        }

        foreach ($segments as $i => $segment) {
            $hrAvg = array_sum(array_slice($hr, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);
            $distDelta = $dist[$segment[1]] - $dist[$segment[0]];
            $timeDelta = $time[$segment[1]] - $time[$segment[0]];
            $elevDelta = $elev[$segment[1]] - $elev[$segment[0]];

            $vo2maxEstimate->fromPaceAndHR($distDelta, $timeDelta, $hrAvg / $this->HeartRateMaximum);
            $estimate = $vo2maxEstimate->value();

            if ($time[$segment[0]] <= $skipFirstSeconds) {
                $firstValidSegmentIndex = $i + 1;
            }

            // TODO: For std/abs. error/mad, there's a difference between applying $this->VO2maxCorrectionFactor before or after!
            $allEstimates[] = $estimate;

            if ($time[$segment[1]] < $skipAfterSeconds) {
                $lastValidSegmentIndex = $i;
            }

            $avgPace = array_sum(array_slice($pace, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);
            $avgGap = array_sum(array_slice($gap, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);
            $avgGradient = array_sum(array_slice($gradient, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);
            $avgGapFactor = array_sum(array_slice($timeFactor, $segment[0], $segment[1] - $segment[0])) / ($segment[1] - $segment[0]);

            $vo2maxEstimate->fromPaceAndHR(1.0, $avgPace, $hrAvg / $this->HeartRateMaximum);
            $avgEstimate = $vo2maxEstimate->value();
            $avgEstimates[] = $avgEstimate;

            $vo2maxEstimate->fromPaceAndHR($distDelta, $timeDelta * $this->GradeAdjustedPaceAlgorithm->getTimeFactor($distDelta > 0 ? $elevDelta / 1000 / $distDelta : 0.0), $hrAvg / $this->HeartRateMaximum);
            $gapEstimate = $vo2maxEstimate->value();
            $gapEstimates[] = $gapEstimate;

            $vo2maxEstimate->fromPaceAndHR($distDelta, $timeDelta * $avgGapFactor, $hrAvg / $this->HeartRateMaximum);
            $avgGapEstimate = $vo2maxEstimate->value();
            $avgGapEstimates[] = $avgGapEstimate;

            $vo2maxEstimate->fromPaceAndHR($distDelta, $timeDelta * $this->GradeAdjustedPaceAlgorithm->getTimeFactor($avgGradient / 100.0), $hrAvg / $this->HeartRateMaximum);
            $avgGradientEstimate = $vo2maxEstimate->value();
            $avgGradientEstimates[] = $avgGradientEstimate;
        }

        return [$gap, $timeFactor, $gradient, $segments, $allEstimates, $avgEstimates, $gapEstimates, $avgGapEstimates, $avgGradientEstimates, $firstValidSegmentIndex, $lastValidSegmentIndex];
    }

    /**
     * @param array $estimates
     * @param array $estimatesNoGap
     * @return array [median, mean, medianNoGap, error, errorMad]
     */
    protected function getStatisticsForEstimates(array $estimates, array $estimatesNoGap)
    {
        $numEstimates = count($estimates);
        $mean = array_sum($estimates) / $numEstimates;

        $middle_index = (int)floor($numEstimates / 2);
        sort($estimates, SORT_NUMERIC);
        $median = $estimates[$middle_index];
        if ($numEstimates % 2 == 0) {
            $median = ($median + $estimates[$middle_index - 1]) / 2;
        }

        $std = $numEstimates == 1 ? 0 : sqrt(array_sum(array_map(function ($v) use ($mean) {
                return pow($v - $mean, 2);
            }, $estimates)) / ($numEstimates - 1));

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

        return [$median, $mean, $medianNoGap, $error, $errorMad];
    }
}
