<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * BookingRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BookingRepository extends EntityRepository
{
    public function findByBusiness(\AppBundle\Entity\Business $business){
      $qb    = $this->createQueryBuilder('Booking');

      $query = $qb
            ->from('AppBundle:Booking', 'bk')
            ->innerJoin('bk.availability','a')
            ->innerJoin('a.business', 'b')
            ->where('b = :business')
            ->setParameter('business', $business);
      $result = $query->getQuery()->getResult();
      return $result;
    }
    public function findByTreatment(\AppBundle\Entity\Treatment $treatment){
      $qb    = $this->createQueryBuilder('Booking');

      $query = $qb
            ->from('AppBundle:Booking', 'bk')
            ->innerJoin('bk.availability','a')
            ->innerJoin('a.treatment', 't')
            ->where('t = :treatment')
            ->setParameter('treatment', $treatment);
      $result = $query->getQuery()->getResult();
      return $result;
    }
}