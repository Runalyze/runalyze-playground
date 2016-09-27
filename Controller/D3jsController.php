<?php
namespace Runalyze\Bundle\PlaygroundBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Calculation;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Model;

/**
 * Class D3jsController
 * @package Runalyze\Bundle\PlaygroundBundle\Controller
 */
class D3jsController extends Controller
{
    /**
     * @Security("has_role('ROLE_USER')")
     */
    public function calendarHeatmapAction()
    {
        return $this->render('PlaygroundBundle::calendarHeatmap.html.twig');
    }

    /**
     * @Security("has_role('ROLE_USER')")
     */
    public function calendarHeatmapDataAction(Account $account)
    {
        $prefix = $this->getParameter('database_prefix');
        $sql = 'SELECT FROM_UNIXTIME(time,\'%Y-%m-%d\') as date, COUNT(*) as activities, SUM(distance) as distance, SUM(s) as s FROM '.$prefix.'training WHERE accountid='.$account->getId().' GROUP BY date';
        $em = $this->getDoctrine()->getManager();
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();

        while ($result = $stmt->fetch()) {
            $Data[$result['date']] = $result;
        }
        return new JsonResponse($Data);
    }

}