<?php

namespace Runalyze\Bundle\PlaygroundBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Runalyze\Calculation;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Model;

class HrvController extends Controller
{
    /**
     * @Security("has_role('ROLE_USER')")
     */
    public function hrvTableAction(Account $account, Request $request)
    {
        $data = [];
        $prefix = $this->getParameter('database_prefix');
        $sql = 'SELECT
                `data`,
                `t`.`id`,
                `t`.`time`,
                `t`.`s`,
                `t`.`distance`,
                `s`.`img`,
                `t`.`accountid`
            FROM `'.$prefix.'hrv`
            JOIN `'.$prefix.'training` AS `t` ON `'.$prefix.'hrv`.`activityid` = `t`.`id`
            JOIN `'.$prefix.'sport` AS `s` ON `t`.`sportid` = `s`.`id`
            WHERE  `t`.`accountid` = '.$account->getId().'
            ORDER BY `activityid` DESC LIMIT '.$request->query->getInt('limit', 100);

        $stmt = $this->getDoctrine()->getManager()->getConnection()->prepare($sql);
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            $calculator = new Calculation\HRV\Calculator(new Model\HRV\Entity([
                Model\HRV\Entity::DATA => $row['data']
            ]));
            $calculator->calculate();

            $data[] = [
                'calculator' => $calculator,
                'row' => $row
            ];
        }

        return $this->render('PlaygroundBundle::hrvTable.html.twig', array(
            'data' => $data
        ));
    }
}
