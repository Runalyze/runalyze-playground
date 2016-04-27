<?php

class IntervalPlot extends Plot
{
    public function setYAxisToInterval($i)
    {
        $this->Options['yaxes'][$i - 1]['tickFormatter'] = 'function (v) { var vabs=Math.abs(v); return (v>0?"":"-")+("0"+parseInt((vabs/1000)/3600)).substr(-2,2)+":"+("0"+parseInt((vabs/1000)%3600/60)).substr(-2,2)+":"+("0"+(vabs/1000)%60).substr(-2,2); }';
    }
}