<?php

namespace AppBundle\Controller\Account;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Controller\ApplicationController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use AppBundle\Form\BookingType;
use AppBundle\Form\ServiceType;

use AppBundle\Entity\Business;
use AppBundle\Entity\Service;

use AppBundle\Entity\OfferAvailabilitySet;
use AppBundle\Entity\Offer;

use Symfony\Component\Process\Process;

class OffersController extends Controller
{
    /**
     * @Route("/account/offers/{id}/{slug}", name="admin_business_offers_path")
     * @Method("GET")
     */
    public function indexAction($id, $slug, Request $request)
    {
        $offers   = $this->getRepo('Offer');
        $business = $this->businessBySlugAndId($slug, $id);

        $offers   = $offers->findByBusiness($business);

        //print_r($offers);

        return $this->render('account/offers/index.html.twig', array(
            'offers' => $offers,
            'business' => $business,
        ));
    }

    /**
     * @Route("/account/offers/show/{id}/{slug}/{bookingId}/", name="admin_show_booking_path")
     * @Method("GET")
     */
    public function showAction($id, $slug, $bookingId, Request $request)
    {
        $booking = new Booking();
        $booking->find($bookingId);

        // replace this example code with whatever you need
        return $this->render('account/offers/show.html.twig', array(
            'booking' => $booking,
            'business' => $booking->getBusiness(),
            'service' => $booking->getService(),
            'serviceType'=> $service->getServiceType(),
        ));
    }


    /**
     * @Route("/account/offers/{id}/{slug}/new", name="admin_business_offers_new_path")
     * @Method({"GET"})
     */
    public function createAction($id, $slug, Request $request) {

        $business = $this->businessBySlugAndId($slug, $id);

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository("AppBundle:Treatment");

        $treatments = $repository->findByBusiness($business);

        return $this->render(
            'account/offers/new.html.twig',
            array(
                'treatments' => $treatments,
                'business' => $business,
            )
        );
    }

    /**
     * @Route("/account/offers/{id}/{slug}/new", name="admin_offer_new_path")
     * @Method("POST")
     */
    public function createCheckAction($id, $slug, Request $request) {

        // Treatment ID comes from request then we just return the checkCreate

        $business = $this->businessBySlugAndId($slug, $id);

        $post = $request->get('offer');
        $em = $this->getDoctrine()->getManager();

        $failed = array();
        $total = count($post);

        $errors = array();

        print_r($post);
        die();

        foreach($post as $offerData) {

            $this->createOffer($business, $offerData);

        }

        $numFailed = count($failed);

        if ($numFailed === $total) {
            // All failed
            $this->addFlash(
                'error',
                implode(' ', $errors)
            );
        } elseif ($numFailed > 0) {
            // Some failed
            $this->addFlash(
                'error',
                'We could not add ' . $failed . ' of your new treatments.'
            );
            $em->flush();
        } else {
            $this->addFlash(
                'notice',
                'Successfully added all of your new treatments.'
            );
            $em->flush();
            // All succeeded
            return $this->redirectToRoute('admin_business_treatments_path',["slug"=>$slug,"id"=>$id]);
        }

        return $this->render('account/treatments/new.html.twig', array(
            //'businessForm' => $form->createView(),
            'business' => $business,
            'failed'  => $failed,
            'treatments' => $this->getDoctrine()->getManager()->getRepository("AppBundle:TreatmentCategory")->getHeirarchy(),
        ));

        if (!$treatmentId) {
            return $this->redirectToRoute('admin_business_offers_path', array(
                'id' => $id,
                'slug' => $slug
            ));
        }

        return $this->checkCreateAvailabilityAction($id, $slug, $treatmentId, $request);

    }

    public function createOffer($business, $data) {
        // this is absolutely something that would be offloaded to the worker

        //treatmentCategory
        //startDate
        //times
        //discountPrice
        //RecurrenceType
        // RecurrenceDates

        $slug = $business->getSlug();
        $id = $business->getId();

        $treatmentId = $data->treatmentCategory;

        if (!$data->startDate) {
            $date = false;
        } else {
            $date = $data->startDate;
        }

        if (!$data->times) {
            $times = array();
        } else {
            $times = $data->times;
        }

        if (!$data->recurrenceType) {
            $recurrenceType = 'never';
        } else {
            $recurrenceType = $data->recurrenceType;
        }

        if($times[0] == 'ALL'){
          $times = $this->buildAllTimes();
        }
        if (!$date || !$times || count($times) < 1 ){
            return $this->redirectToRoot($slug, $id, $treatmentId, array(
                'error',
                'Date and time must be specified.'
            ));
        }

        if (!$data->discountPrice) {
            $discount = false;
        } else {
            $discount = $data->discountPrice;
        }

        if (!$discount) {
            return $this->redirectToRoot($slug, $id, $treatmentId, array(
                'error',
                'You must specify a discount.'
            ));
        }

        if (!$data->recurrenceDates) {
            $recurrenceDOWs = array();
        } else {
            $recurrenceDOWs = $data->recurrenceDOWs;
        }

        $recurrenceDOWs = array_unique($recurrenceDOWs);

        $treatments = $this->getRepo('Treatment');
        $treatment = $treatments->findOneBy(array( 'id' => $treatmentId ));

        if (!$treatment) {
            return $this->redirectToRoot($slug, $id, $treatmentId, array(
                'error',
                'Treatment not found.'
            ));
        }

        if ((float) $discount >= (float) $treatment->getOriginalPrice()) {
            return $this->redirectToRoot($slug, $id, $treatmentId, array(
                'error',
                'Discount must be cheaper than the original price.'
            ));
        }

        $em = $this->getDoctrine()->getManager();

        $offer = new Offer();
        $offer->setBusiness($business);
        $offer->setTreatment($treatment);
        $offer->setCurrentPrice($discount);
        $em->persist($offer);

        $startDateTime = new \DateTime($date);

        $availabilitySet = new OfferAvailabilitySet();
        $availabilitySet->setOffer($offer);
        $availabilitySet->setStartDate($startDateTime);
        $availabilitySet->setDaysOfTheWeek($recurrenceDOWs);
        $availabilitySet->setTimes($times);
        $availabilitySet->setTreatment($treatment);
        $availabilitySet->setRecurrenceType($recurrenceType);

        $em->persist($availabilitySet);
        $em->flush();

        $success = $this->doAvailabilities($availabilitySet->getId());

        // we now need to create the availability set

        if ($success) {
            $message = 'Queued the creation of your availabilities.';
        } else {
            $em->remove($availabilitySet);
            $em->remove($offer);
            $em->flush();
            $message = 'Unable to queue your availabilities. Please report this to us on the contact us page.';
        }

        return $this->redirectToRoot($slug, $id, $treatmentId, array(
            'notice',
            $message
        ));
    }


    protected function buildAllTimes() {
      $times = [];
        for($i=7;$i<=21;$i++){
          for($j=0;$j<=3;$j++){
            $min = $j*15;
            if($min == 0) $min = '00';
            $times[] = $i . ':' . $min;
          }
        }
        return $times;
    }

    /**
     * @Route("/account/offers/{id}/{slug}/toggleEnabled", name="admin_treatment_toggle_is_open")
     * @Method("POST")
     */
    public function updateOfferStatusAction($id,$slug,Request $request)
    {
      $req       = $request->request;
      $offerid   = $req->get('offerId',false);
      $isOpen    = $req->get('onoffswitch',false);
      $isOpen    = ($isOpen == 'on') ? true : $isOpen;
      $em = $this->getDoctrine()->getManager();
      $offer    = $em->getRepository("AppBundle:Offer")->findOneBy(['id'=>$offerid]);
      $offer->setIsOpen($isOpen);
      $em->flush();
      return $this->redirectToRoute('admin_business_offers_path',['id'=>$id,'slug'=>$slug]);
    }


    /**
     * @Route("/ajax/offers/recurrenceform", name="ajax_delete_offer")
     * @Method("GET")
     */
    public function offerForm(Request $request) {
        return $this->render(
            'account/offers/form.html.twig',
            array(
                'prefix' => $request->query->get('prefix'),
                'index' => $request->query->get('index'),
            )
        );
    }

    /**
     * @Route("/account/offers/{id}/{slug}/remove", name="admin_delete_offer")
     * @Method("POST")
     */
    public function deleteOffer($id,$slug,Request $request)
    {
      $req    = $request->request;
      $offers = $req->get('offers',false);
      foreach ($offers as $offerid){
        $em = $this->getDoctrine()->getManager();
        $offer    = $em->getRepository("AppBundle:Offer")->findOneBy(['id'=>$offerid]);
        $em->persist($offer);
        $em->remove($offer);
      }
      $em->flush();
      //return true;//$this->redirectToRoute('admin_business_offers_path',['id'=>$id,'slug'=>$slug]);
      return new Response();
    }


    protected function redirectToRoot($slug, $id, $treatmentId, $flash = false) {
        if ($flash) {
            list($type, $message) = $flash;
            $this->addFlash(
                $type,
                $message
            );
        }
        return $this->redirectToRoute(
            'admin_business_offers_path',
            array( "slug"=> $slug,
                   "id"=> $id,
                   "treatmentId"=> $treatmentId
            )
        );
    }

    protected function doAvailabilities($availabilitySetId) {

        try {
            $this->get('old_sound_rabbit_mq.create_availabilities_producer')->publish($availabilitySetId);
            return true;
        } catch (\Exception $e) {
            return false;
        }


        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $offerAvailability = $em->getRepository("AppBundle:OfferAvailabilitySet");

        $availabilitySet = $offerAvailability->findOneById($availabilitySetId);

        if (!$availabilitySet) {
            return false;
        }

        // Start it going
        $business = $availabilitySet->getTreatment()->getBusiness();

        // Delete previous availabilities to make this rerunable
        $queryBuilder = $em
            ->createQueryBuilder()
            ->delete('AppBundle:Availability', 'a')
            ->innerJoin('a.availabilitySet', 's')
            ->where('a.availabilitySet = :availabilitySet')
            ->setParameter(':availabilitySet', $availabilitySet);

        $queryBuilder->getQuery()->execute();

        // Offer is made. We need to make its availability now
        $matchingDates = $availabilitySet->datesThatMatchRecurrence();
        // $availabilitySets = $availabilitySet->datesToAvailabilities($matchingDates, $business);
        // $treatment = $availabilitySet->getTreatment();

        $batchSize = 20;

        foreach($matchingDates as $i => $date) {
            $x = new \AppBundle\Entity\Availability();
            $x->setDate($date);
            $x->setAvailabilitySet($availabilitySet);
            $x->setTreatment($availabilitySet->getTreatment());
            $x->setBusiness($business);
            $em->persist($x);

            if ($i !== 0 && ($i % $batchSize) === 0) {
                $em->flush();
                $em->clear(); // Detaches objects

                $availabilitySet = $offerAvailability->findOneById($availabilitySetId);
                $business = $availabilitySet->getTreatment()->getBusiness();

            }
        }

        $availabilitySet->setProcessed(true);

        $em->flush(); //Persist objects that did not make up an entire batch
        $em->clear();
        // End it

        return count($i);

    }

    /**
     * @Route("/account/offers/{id}/{slug}/edit", name="admin_edit_offer")
     * @Method("POST")
     */
     function editOfferAction($id, $slug, Request $request)
     {
       $req    = $request->request;
       $id     = $req->get('pk',false);
       $price  = str_replace('$','',$req->get('value',false));
       $em = $this->getDoctrine()->getManager();
       $offers = $em->getRepository("AppBundle:Offer");
       $offer  = $offers->findOneBy(['id'=>$id]);
       $offer->setCurrentPrice($price);
       $em->flush();
       return new JsonResponse(array('success' => true));
     }

}
