<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * BookingRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class OfferRepository extends EntityRepository
{
    public function recentDeals($limit = 3) {
            $qb = $this->createQueryBuilder('o');
            $qb
                ->innerJoin('o.business','b')
                ->leftJoin('b.address', 'ba')
                ->innerJoin('o.treatment', 't')
                ->setMaxResults(3);

            $query = $qb->getQuery();
            $results = $query->getResult();

            return $results;
    }

    public function findByMulti($search, $pageSize = 20, $currentPage = 1){
      $qb    = $this->createQueryBuilder('o');
      $query = $qb
                ->innerJoin('o.availabilitySet', 'oas')
                ->innerJoin('o.business','b')
                ->innerJoin('b.address', 'ba')
                ->innerJoin('o.treatment', 't')
                ->innerJoin('oas.availabilities', 'a')
                ->andWhere('o.isOpen = true')
                // ->andWhere('a.active = true')
                ->addOrderBy('o.currentPrice', 'ASC');

      if($this->isAvailabilitySearch($search)){
          $this->filterOffersByAvailability($query, $qb, $search);
      }

      if($this->isLocationSearch($search)){
         $this->filterBookingsByLocation($query, $search['latitude'], $search['longitude']);
      }

      if($this->isTreatmentCategorySearch($search)){
        $this->filterOffersByTreatmentCategory($query, $qb, $search['treatment']);
      }

      if ($price = $this->isPriceSearch($search)){
          list($min, $max) = $price;
          $this->filterOffersByPrice($query, $qb, $min, $max);
      }

      $paginator = new Paginator($query, $fetchJoin = true);
      $result = $paginator
        ->getQuery()
        ->setFirstResult($pageSize * ($currentPage-1)) // set the offset
        ->setMaxResults($pageSize); // set the limit


      return $result->getResult();
    }


    public function filterOffersByPrice(&$query, $qb, $price1, $price2){
        // $price1 = number_format($price1, 2);
        $query->andWhere($qb->expr()->between(
            'o.currentPrice',
            ':min',
            ':max'
        ))
        ->setParameter('min', $price1)
        ->setParameter('max', $price2);
    }

    public function filterOffersByTreatmentCategory(&$query, $qb, $treatmentCategory){
      $query
        ->innerJoin('t.treatmentCategory', 'tc')
            ->andWhere('tc = :treatmentCategory OR tc.parent = :treatmentCategory')
            ->setParameter('treatmentCategory', $treatmentCategory);

            return $query;
    }

    public function filterBookingsByLocation(&$query, $latitude, $longitude){
        $miles = 3959;
        $km = 6371;
        $query
            ->setParameter('latitude', $latitude)
            ->setParameter('longitude', $longitude)
            ->setParameter('unit', $miles)
            //
            ->addSelect("( :unit * ACOS( COS( radians(:latitude) ) * COS( radians( ba.latitude ) ) * COS( radians( ba.longitude ) - radians(:longitude) ) + SIN( radians(ba.latitude) ) * SIN(radians(:latitude)) ) ) as distance")
            ->orderBy('distance', 'asc');
    }

    public function filterOffersByAvailability(&$query, $qb, $search){

        $timeQ = $search['time'];
        $dayQ = $search['day'];

        $days = array();
        $dates = array();

        $tomorrow = new \DateTime('tomorrow');
        $today = new \DateTime('today');

        if ($dayQ === 'tomorrow') {
            $days[] = $tomorrow;
        } elseif ($dayQ === 'today') {
            $days[] = $today;
        } elseif ($dayQ === 'all') {
            $days[] = $today;
            $days[] = $tomorrow;
            // Want to make sure these dates are entered in
        }

        // all - Anytime
        // morning - 5 am - 12 pm
        // afternoon - 12 pm - 5 pm
        // evening - 5 pm to 12 am

        // Need to create the boundaries now

        foreach($days as $day) {
            $min = clone $day;
            $max = clone $day;

            if ($timeQ === 'all') {
                $dates[] = array(
                    $min->setTime('0', '0', '0'),
                    $max->setTime('23', '59', '59')
                );
            } elseif ($timeQ === 'morning') {
                $dates[] = array(
                    $min->setTime('5', '0', '0'),
                    $max->setTime('11', '59', '59')
                );
            } elseif ($timeQ === 'afternoon') {
                $dates[] = array(
                    $min->setTime('12', '0', '0'),
                    $max->setTime('16', '59', '59')
                );
            } elseif ($timeQ === 'afternoon') {
                $dates[] = array(
                    $min->setTime('17', '0', '0'),
                    $max->setTime('23', '59', '59')
                );
            }

        }

        // Now we have the boundaries for the given days. We need to add them to the query
        if (count($dates) < 1) return;

        $expression = $qb->expr()->orX();

        foreach($dates as $k => $dateSet) {

            list($min, $max) = $dateSet;

            $expression->add($qb->expr()->between(
                    'a.date',
                    ':mindate' . $k,
                    ':maxdate' . $k
            ));

            $query
                ->setParameter('mindate' . $k, $min)
                ->setParameter('maxdate' . $k, $max);
        }

        $query->andWhere($expression);

        return $query;

    }

    public function isPriceSearch($search){
        if ($this->has('price_min',$search) || ($this->has('price_max', $search))) {

            $min = isset($search['price_min']) ? $search['price_min'] : 0;
            $max = isset($search['price_max']) ? $search['price_max'] : 500;;

            return array($min, $max);
        } else {
            return false;
        }
    }

    public function isTreatmentCategorySearch($search) {
      return $this->has('treatment', $search);
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
          if($this->has($field,$search)) return true;
      }
      return false;
    }
    public function has($key,$search)
    {
        if (is_array($search))
            return array_key_exists($key, $search) ? true : false;
        else
            return false;
    }

    public function strongParams($req) {
        //All possible search fields in format: postkey=>table_abbrev.field_name
        $keys= [
                'day'=>'day',
                'time'=>'time',
                'location'=>'location',
                'treatment'=>'treatment',
                'min'=>'price_min',
                'max'=>'price_max'
        ];
        //searched fields and values
        $data=[];
        //build data array of fields present in post from search and their values
        foreach($keys as $key=>$field){

          if(array_key_exists($key, $req) && $val = $req[$key] ){
              if ($field === 'location') {
                 $geo = Address::geocodeZip($val);
                 if ($geo) {
                     $data['latitude'] = $geo->getLatitude();
                     $data['longitude'] = $geo->getLongitude();
                     $data['location'] = array($data['latitude'], $data['longitude']);
                 }
             } else {
                $data[$field] = $val;
            }
          }
        }

        return $data;
    }

  //if()andWhere('r.winner IN (:ids)')  ->setParameter('ids', $ids);
}
