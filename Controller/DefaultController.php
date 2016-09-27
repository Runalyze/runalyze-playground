<?php
namespace Runalyze\Bundle\PlaygroundBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultController
 * @package Runalyze\Bundle\PlaygroundBundle\Controller
 */
class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('PlaygroundBundle::overview.html.twig');
    }

    public function testAction()
    {
        return $this->render('legacy_end.html.twig');
    }

}