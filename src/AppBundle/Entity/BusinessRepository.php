<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * BusinessRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BusinessRepository extends EntityRepository
{
  public function findBusinessByLocation($zip){
    $qb    = $this->createQueryBuilder('b');
    $query = $qb->select(['b','a'])
       //->from('AppBundle:Business', 'b')
       ->leftjoin('AppBundle:Address','a')
       ->where('a.zip = :zip')
       ->andWhere('b.active = true')
       ->setParameter('zip',$zip);
    $result=$query->getQuery()
                  ->getResult();
    return $result;
  }

  public function findAll() {
    $qb    = $this->createQueryBuilder('b');
    $query = $qb->andWhere('b.active = true');

    $result = $query->getQuery()->getResult();
    return $result;
  }

  public function findBusinessByService($serviceTypeId, $serviceCategoryId = null){
    $qb    = $this->createQueryBuilder('b');
    $query = $qb->select(['b','bk','s','st','sc'])
       ->leftJoin('AppBundle:Booking','bk')
       ->leftJoin('AppBundle:Service','s')
       ->leftJoin('AppBundle:ServiceType','st')
       ->leftjoin('AppBundle:ServiceCategory','sc')
       ->where('st.serviceTypeId = :serviceTypeId')
       ->andWhere('b.active = true')
       ->setParameter('serviceTypeId',$serviceTypeId);
    if($serviceCategoryId !== null){
      $query->where('sc.serviceCategoryId = :ServiceCategoryId')
            ->setParameters('serviceCategoryId',$serviceCategoryId);
    }
    $result=$query->getQuery()
                  ->getResult();
    return $result;
  }
}
