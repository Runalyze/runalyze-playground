<?php

namespace Runalyze\Bundle\PlaygroundBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UiController extends Controller
{
    public function circleProgressAction()
    {
        return $this->render('PlaygroundBundle::circleProgress.html.twig');
    }
}
