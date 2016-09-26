<?php
namespace Runalyze\Bundle\PlaygroundBundle\Controller;

use Runalyze\Bundle\CoreBundle\Component\Account\Registration;
use Runalyze\Bundle\CoreBundle\Form\RegistrationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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

    /**
     * @Route("/", name="playground-index")
     */
     public function indexAction() {
     	return $this->render('legacy_end.html.twig');
     }

