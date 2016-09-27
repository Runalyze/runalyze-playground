<?php
namespace Runalyze\Bundle\PlaygroundBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Calculation;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Model;

/**
 * Class UiController
 * @package Runalyze\Bundle\PlaygroundBundle\Controller
 */
class UiController extends Controller
{
    public function circleProgressAction()
    {
        return $this->render('PlaygroundBundle::circleProgress.html.twig');
    }

}