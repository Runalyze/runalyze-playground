<?php

namespace Runalyze\Bundle\PlaygroundBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('PlaygroundBundle::overview.html.twig');
    }
}
