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
 * Class HrvController
 * @package Runalyze\Bundle\PlaygroundBundle\Controller
 */
class HrvController extends Controller
{
    /**
     * @Security("has_role('ROLE_USER')")
     */
    public function hrvTableAction(Account $Account)
    {
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
            WHERE  `t`.`accountid` = '.$Account->getId().'
            ORDER BY `activityid` DESC LIMIT 70';
        $em = $this->getDoctrine()->getManager();
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $i = 0;
        while ($Row = $stmt->fetch()) {
            $Calculator = new Calculation\HRV\Calculator(new Model\HRV\Entity(array(
                Model\HRV\Entity::DATA => $Row['data']
            )));
            $Calculator->calculate();
            $data[$i]['calculator'] = $Calculator;
            $data[$i]['row'] =  $Row;
            $i++;
        }

        return $this->render('PlaygroundBundle::hrvTable.html.twig', array(
            'data' => $data
        ));
    }

}