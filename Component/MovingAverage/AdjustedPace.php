<?php

namespace Runalyze\Bundle\PlaygroundBundle\Component\MovingAverage;

use Runalyze\View\Activity\Plot\Pace;

class AdjustedPace extends Pace
{
    /** @var string */
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
