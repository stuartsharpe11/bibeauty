<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * AvailabilityRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AvailabilityRepository extends EntityRepository
{
    public function findTodayAndTomorrowForTreatment($treatment) {

        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        $tomorrow->setTime(11, 59, 59);

        $qb = $this->createQueryBuilder('Availabilities');
        $qb
            ->from('AppBundle:Availability', 'a')
            ->innerJoin('a.treatment','t')
            ->innerJoin('a.availabilitySet', 'oas')
            ->innerJoin('oas.offer', 'o')
            ->where('o.isOpen = true')
            ->andWhere('t = :treatment')
            ->setParameter('treatment', $treatment)
            ->add('where',
              $qb->expr()->between(
                  'a.date',
                  ':todaystart',
                  ':tomorrowend'
              ))
             ->setParameters(array(
                 'todaystart' => $today,
                 'tomorrowend' => $tomorrow
            ))
            ->addOrderBy('a.date', 'ASC')
            ->addOrderBy('o.currentPrice',  'ASC')
            ;

        $query = $qb->getQuery();
        $results = $query->getResult();

        // Sort through today and tomororw

        $todayArray = array();
        $tomorrowArray = array();

        $cmpFormat = 'Y-m-d';

        foreach($results as $result) {
            $date = $result->getDate();

            $f = $date->format($cmpFormat);
            if ($f == $today->format($cmpFormat)) {
                $todayArray[] = $result;
            } elseif ($f === $tomorrow->format($cmpFormat)) {
                $tomorrowArray[] = $result;
            }
        }

        return array('today' => $todayArray, 'tomorrow' => $tomorrowArray);

        return $results;

    }
}
