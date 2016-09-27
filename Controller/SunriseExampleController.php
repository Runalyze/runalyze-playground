<?php
namespace Runalyze\Bundle\PlaygroundBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use DateTime;
use DateTimeZone;
use DateInterval;
/**
 * Class SunriseExampleController
 * @package Runalyze\Bundle\PlaygroundBundle\Controller
 */
class SunriseExampleController extends Controller
{
    public function sunriseAction()
    {
        date_default_timezone_set('Europe/Berlin');

        $lat = isset($_GET['lat']) ? (float)$_GET['lat'] : 49.440066;
        $lng = isset($_GET['lng']) ? (float)$_GET['lng'] : 7.749126;
        $zenith = 90 + 5/6;
        $sunrise= $this->sunriseData(function(DateTime $date) use ($lat, $lng, $zenith) { return round(date_sunrise($date->getTimestamp(), SUNFUNCS_RET_DOUBLE, $lat, $lng, $zenith, $date->getOffset()/3600), 2); });
        $sunset = $this->sunriseData(function(DateTime $date) use ($lat, $lng, $zenith) { return round(date_sunset($date->getTimestamp(), SUNFUNCS_RET_DOUBLE, $lat, $lng, $zenith, $date->getOffset()/3600), 2); });

        return $this->render('PlaygroundBundle::sunriseExample.html.twig', array(
            'sunrise' => $sunrise,
            'sunset' => $sunset
        ));
    }

    public function sunriseData(callable $creator) {
            $Date = new DateTime('NOW', new DateTimeZone('Europe/Berlin'));
            $OneDay = new DateInterval('P1D');
            $singleData = array();

            for ($i = 0; $i < 365; ++$i) {
                $singleData[] = "'date':new Date('".$Date->format('Y-m-d')."'),'value':".$creator($Date);
                $Date->add($OneDay);
            }

            return '[{'.implode('},{', $singleData).'}]';
    }

}