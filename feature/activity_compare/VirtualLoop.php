<?php
/**
 * This file contains class::Loop
 * @package Runalyze\Model\Trackdata
 */

namespace Runalyze\Model\Trackdata;

use Runalyze\Activity\Duration;
use Runalyze\Configuration;

class VirtualLoop extends \Runalyze\Model\Trackdata\Loop
{

    var $pace;
    var $totalTime;
    var $currentDistance = 0;
    var $currentTime = 0;

    public function __construct($pace, $time)
    {
        $this->pace = (new Duration($pace))->seconds();
        $this->totalTime = (new Duration($time))->seconds();
    }

    public function reset()
    {
        $this->currentDistance = 0;
        $this->currentTime = 0;
    }

    public function isAtEnd()
    {
        return $this->currentTime >= $this->totalTime;
    }


    /**
     * Current time
     * @return int
     */
    public function time()
    {
        return $this->currentTime;
    }

    /**
     * Current distance
     * @return float
     */
    public function distance()
    {
        return $this->currentDistance;
    }

    /**
     * Move for time
     * @param int $seconds
     * @throws \RuntimeException for negative values or if time is empty
     */
    public function moveTime($seconds)
    {
        $this->currentTime += $seconds;
        if ($this->currentTime > $this->totalTime) $this->currentTime = $this->totalTime;
        $this->currentDistance = $this->currentTime / $this->pace;
    }

    /**
     * Move to time
     * @param int $seconds
     * @throws \RuntimeException for negative values or if time is empty
     */
    public function moveToTime($seconds)
    {
        $this->reset();
        $this->moveTime($seconds);
    }

    /**
     * Move for distance
     * @param float $kilometer
     * @throws \RuntimeException for negative values or if distance is empty
     */
    public function moveDistance($kilometer)
    {
        $this->currentDistance += $kilometer;
        $this->currentTime = (int)($this->currentDistance * $this->pace);
        if ($this->currentTime > $this->totalTime) $this->moveToTime($this->totalTime);
    }

    /**
     * Move to distance
     * @param float $kilometer
     * @throws \RuntimeException for negative values or if distance is empty
     */
    public function moveToDistance($kilometer)
    {
        $this->reset();
        $this->moveDistance($kilometer);
    }

}