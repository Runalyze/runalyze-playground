<?php

namespace Runalyze\Bundle\PlaygroundBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Runalyze\Bundle\CoreBundle\Entity\Account;

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
        $stmt = $this->getDoctrine()->getManager()->getConnection()->prepare($sql);
        $stmt->execute();

        while ($result = $stmt->fetch()) {
            $data[$result['date']] = $result;
        }

        return new JsonResponse($data);
    }
}
