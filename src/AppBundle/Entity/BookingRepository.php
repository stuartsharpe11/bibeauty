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
    public function findByMulti($search){
      $qb    = $this->createQueryBuilder('bk');
      $query = $qb->select(['bk','b','tas','s'])
                  ->leftJoin('AppBundle:Business','b')
                  ->leftJoin('AppBundle:TreatmentAvailabilitySet','tas')
                  ->leftJoin('AppBundle:Service','s');

      if($this->isAvailabilitySearch($search)){
        $this->filterBookingsByAvailability($query,$qb, $search);
      }

      if($this->isLocationSearch()){
        $this->filterBookingsByLocation($query,$search['location']);
      }

      if($this->isServiceSearch($search)){
        $this->filterBookingsByService($query,$search['serviceType']);
      }

      if($this->isCategorySearch($search)){
          $this->filterBookingsByCategory($query,$search['serviceCategory']);
      }

      if($this->isPriceSearch($search)){
          $this->filterBookingsByPrice($query,$qb,$search['price1'],$search['price2']);
      }

      $result=$query->getQuery()
                    ->getResult();
      return $result;
    }


    public function filterBookingsByPrice(&$query,$qb, $price1,$price2){
      $query->add('where',
          $qb->expr()->between(
              's.currentPrice',
              ':price1',
              ':price2'
          )
      )->setParameters([
        'price1' => $price1,
        'price2' => $price2
      ]);
    }

    public function filterBookingsByCategory(&$query,$category){
      $query->add('c')
            ->leftJoin('AppBundle:Category', 'c')
            ->add('where','b.categories = :category')
            ->setParameter('category',$category);
    }
    public function filterBookingsByService(&$query,$serviceType){
      $query->add('st')
            ->leftJoin('AppBundle:Service', 's')
            ->leftJoin('AppBundle:ServiceType','st')
            ->add('where','st.id = :serviceType')
            ->setParameter('serviceType',$serviceType);
    }

    public function filterBookingsByLocation(&$query,$location){
      $query->add('where','tas.time = :location')
            ->setParameter('location',$location);
    }

    public function filterBookingsByAvailability(&$query,$qb,$search){
      if($search['day'] !=null){
        $query->add('where', 'tas.day = :day')
              ->setParameter('day',$search['day']);
      }
      if($this->isTimeRangeSearch($search)){
        $query->add('where',
            $qb->expr()->between(
                'tas.time',
                ':starttime',
                ':endtime'
            )
        )->setParameters([
          'starttime' => $search['starttime'],
          'endtime' => $search['endtime']
        ]);
      }else if($this->isTimeSearch($search)){
        $time = ($starttime === null) ? $endtime : $starttime;
        $query->andWhere('tas.time = :time')
              ->setParameter('time',$time);
      }
      $result=$query->getQuery()
                    ->getResult();
      return $result;
    }

    public function isPriceSearch($search){
      return ($this->has('price1',$search)
              || ($this->has('price2',$search))
              ) ? true : false;
    }

    public function isServiceSearch($search) {
      return $this->has('serviceType',$search);
    }
    public function isCategorySearch($search){
      return $this->has('serviceCategory',$search);
    }
    public function isLocationSearch($search){
      return $this->has('location',$search);
    }

    public function isTimeSearch($search){
      return ($this->has('starttime',$search)
              || ($this->has('endtime',$search))
              ) ? true : false;
    }
    public function isTimeRangeSearch($search){
        return ($this->has('starttime',$search)
                &&  ($this->has('endtime',$search))
                ) ? true : false;
    }
    public function isAvailabilitySearch($search){
      foreach(['day','starttime','endtime'] as $field){
          if($this->has('day',$field)) return true;
      }
      return false;
    }
    public function has($k,$arr)
    {
      return array_key_exists($key, $search) ? true : false;
    }
  //if()andWhere('r.winner IN (:ids)')  ->setParameter('ids', $ids);
}