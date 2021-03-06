<?php

// src/AppBundle/Entity/Business.php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Cocur\Slugify\Slugify;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Cmf\Bundle\SeoBundle\Extractor\TitleReadInterface;
use Symfony\Cmf\Bundle\SeoBundle\Extractor\DescriptionReadInterface;
use Symfony\Cmf\Bundle\SeoBundle\Extractor\ExtrasReadInterface;

/**
 * @ORM\Entity(repositoryClass="BusinessRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="app_businesses")
 */
class Business implements TitleReadInterface, DescriptionReadInterface, ExtrasReadInterface {
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $slug;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $active = true;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Url()
     */
    protected $website = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     * @Assert\Email
     */
    protected $email = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $acceptsCash = true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $acceptsCredit = true;

    /**
     * @ORM\OneToOne(targetEntity="Address", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EAGER")
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id")
     */
    protected $address;

    /**
      * @ORM\Column(type="string", length=14, nullable=true)
      */
    protected $landline  = null;

    /**
      * @ORM\Column(type="string", length=14, nullable=true)
      */
    protected $mobile = null;

    /**
     * @ORM\OneToMany(targetEntity="Review", cascade={"persist"}, mappedBy="business")
     */
    protected $reviews;

    /**
     * @ORM\OneToOne(targetEntity="OperatingSchedule", cascade={"persist", "remove"}, orphanRemoval=true, fetch="EAGER")
     * @ORM\JoinColumn(name="operation_id", referencedColumnName="id")
     */
    protected $operation;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Url()
     */
    protected $yelpLink;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     * @Assert\Length(min = 10)
     */
    protected $description;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Assert\GreaterThanOrEqual(
     *     value = 0
     * )
     * @Assert\LessThanOrEqual(
     *     value = 5
     * )
     */
    protected $averageRating = 0;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $owner;

    /**
     * @ORM\OneToOne(targetEntity="Attachment", cascade={"remove"}, orphanRemoval=true, fetch="EAGER")
     * @ORM\JoinColumn(name="header_attachment_id", referencedColumnName="id")
     */
    protected $headerAttachment;

    /**
     * @ORM\OneToOne(targetEntity="Attachment", cascade={"remove"}, orphanRemoval=true, fetch="EAGER")
     * @ORM\JoinColumn(name="logo_attachment_id", referencedColumnName="id")
     */
    protected $logoAttachment;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    /**
     * @ORM\OneToMany(targetEntity="Treatment", cascade={"persist", "remove"}, mappedBy="business")
     */
    protected $treatments;

    /**
     * @ORM\OneToMany(targetEntity="Therapist", cascade={"persist", "remove"}, mappedBy="business")
     */
    protected $therapists;

    /**
     * @ORM\OneToMany(targetEntity="Availability", cascade={"persist", "remove"}, mappedBy="business", orphanRemoval=true)
     */
    protected $availabilities;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($active) {
        $this->active = $active;
        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Business
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set yelpLink
     *
     * @param string $yelpLink
     * @return Business
     */
    public function setYelpLink($yelpLink)
    {
        if (!empty($yelpLink)) {
            $this->yelpLink = $yelpLink;
        }

        return $this;
    }

    /**
     * Get yelpLink.
     *
     * @return string
     */
    public function getYelpLink() {
        return $this->yelpLink;
    }

    /**
     * Get yelpId. Converts to ID from the link
     *
     * @return string
     */
    public function getYelpId()
    {
        $regex = '/https?:\/\/(www.)?yelp.com\/biz\/(?P<id>[^\/?]+)/';

        if (preg_match($regex, $this->yelpLink, $matches)) {
            return $matches['id'];
        }
        return false;

    }

    /**
     * Set description
     *
     * @param string $description
     * @return Business
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Business
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set address
     *
     * @param \AppBundle\Entity\Address $address
     * @return Business
     */
    public function setAddress(\AppBundle\Entity\Address $address = null)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return \AppBundle\Entity\Address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set owner
     *
     * @param \AppBundle\Entity\User $owner
     * @return Business
     */
    public function setOwner(\AppBundle\Entity\User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \AppBundle\Entity\User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    public function hasHeaderAttachment() {
        if ($this->headerAttachment) return true;
        return false;
    }

    public function hasLogoAttachment() {
        if ($this->logoAttachment) return true;
        return false;
    }

    /**
     * Set headerAttachment
     *
     * @param \AppBundle\Entity\Attachment $headerAttachment
     * @return Business
     */
    public function setHeaderAttachment(\AppBundle\Entity\Attachment $headerAttachment = null)
    {
        $this->headerAttachment = $headerAttachment;

        return $this;
    }

    /**
     * Get headerAttachment
     *
     * @return \AppBundle\Entity\Attachment
     */
    public function getHeaderAttachment()
    {
        return $this->headerAttachment;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return Business
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    public function toJSON($showOffers = false) {
        $arr = array(
            'thumbnail' => $this->hasLogoAttachment() ? $this->getLogoAttachment()->toJSON() : false,
            'address' => array(
                'street' => $this->address->getStreet(),
                'line2' => $this->address->getLine2(),
                'city' => $this->address->getCity(),
                'state' => $this->address->getState(),
                'zip' => $this->address->getZip(),
                'country' => $this->address->getCountry()
            ),
            'coordinates' => array(
                'longitude' => $this->address->getLongitude(),
                'latitude' => $this->address->getLatitude()
            ),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'slug' => $this->getSlug(),
            'id' => $this->getID()
        );

        if ($showOffers && $this->getOffers()) {
            $hierarchy = $this->getOffersTreatmentHierarchy();

            $offers = array();

            foreach($hierarchy as $treatment) {
                $label = $treatment->getLabel();
                $name = $treatment->getName();
                $subOffers = $treatment->getOffers();

                $subOffersArray = array();

                foreach($subOffers as $subOffer) {
                    $subOffersArray[] = $subOffer->toJSON();
                }

                $offers[] = array(
                    'label' => $label,
                    'name' => $name,
                    'offers' => $subOffers
                );
            }

            $arr['offers'] = $offers;
        }

        return $arr;
    }

    /**
     * Set website
     *
     * @param string $website
     * @return Business
     */
    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * Get website
     *
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Business
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set acceptsCash
     *
     * @param boolean $acceptsCash
     * @return Business
     */
    public function setAcceptsCash($acceptsCash)
    {
        $this->acceptsCash = $acceptsCash;

        return $this;
    }

    /**
     * Get acceptsCash
     *
     * @return boolean
     */
    public function getAcceptsCash()
    {
        return $this->acceptsCash;
    }

    /**
     * Set acceptsCredit
     *
     * @param boolean $acceptsCredit
     * @return Business
     */
    public function setAcceptsCredit($acceptsCredit)
    {
        $this->acceptsCredit = $acceptsCredit;

        return $this;
    }

    /**
     * Get acceptsCredit
     *
     * @return boolean
     */
    public function getAcceptsCredit()
    {
        return $this->acceptsCredit;
    }

    /**
     * Set operation
     *
     * @param \AppBundle\Entity\OperatingSchedule $operation
     * @return Business
     */
    public function setOperation(\AppBundle\Entity\OperatingSchedule $operation = null)
    {
        $this->operation = $operation;

        return $this;
    }

    /**
     * Get operation
     *
     * @return \AppBundle\Entity\OperatingSchedule
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Set logoAttachment
     *
     * @param \AppBundle\Entity\Attachment $logoAttachment
     * @return Business
     */
    public function setLogoAttachment(\AppBundle\Entity\Attachment $logoAttachment = null)
    {
        $this->logoAttachment = $logoAttachment;

        return $this;
    }

    /**
     * Get logoAttachment
     *
     * @return \AppBundle\Entity\Attachment
     */
    public function getLogoAttachment()
    {
        return $this->logoAttachment;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reviews = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add reviews
     *
     * @param \AppBundle\Entity\Review $reviews
     * @return Business
     */
    public function addReview(\AppBundle\Entity\Review $reviews)
    {
        $this->reviews[] = $reviews;

        return $this;
    }

    /**
     * Remove reviews
     *
     * @param \AppBundle\Entity\Review $reviews
     */
    public function removeReview(\AppBundle\Entity\Review $reviews)
    {
        $this->reviews->removeElement($reviews);
    }

    public function hasReviews() {
        return $this->reviews->count() > 0;
    }

    /**
     * Get reviews
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReviews()
    {
        return $this->reviews;
    }

    public function getAverageRating() {
        return $this->averageRating;
    }

    public function setAverageRating($averageRating) {
        $this->averageRating = $averageRating;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAutomaticFields() {
        if (!$this->createdAt) {
            $this->createdAt = new \DateTime();
        }
        $this->updated = new \DateTime();
        $this->setSlug($this->generateSlug());
    }

    protected function generateSlug() {
        $slugify = new Slugify();
        return $slugify->slugify($this->name); // hello-world
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdated() {
        // will NOT be saved in the database
        $this->updated->modify("now");
    }


    /**
     * Add service
     *
     * @param \AppBundle\Entity\ServiceCategory $treatments
     * @return Treatment
     */
    public function addTreatment(\AppBundle\Entity\Treatment $treatment)
    {
        $this->treatments[] = $treatment;

        return $this;
    }

    /**
     * Remove service
     *
     * @param \AppBundle\Entity\Service $service
     */
    public function removeTreatment(\AppBundle\Entity\Treatment $treatment)
    {
        $this->treatments->removeElement($treatment);
    }

    /**
     * Get treatments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTreatments()
    {
        return $this->treatments;
    }

    /**
     * Add availability
     *
     * @param \AppBundle\Entity\Availability $availability
     * @return Business
     */
    public function addAvailability(\AppBundle\Entity\Availability $availability)
    {
        $this->availabilities[] = $availability;

        return $this;
    }

    /**
     * Remove availability
     *
     * @param \AppBundle\Entity\Therapist $therapist
     */
    public function removeAvailability(\AppBundle\Entity\Availability $availability)
    {
        $this->availabilities->removeElement($availability);
    }

    /**
     * Get availabilities
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAvailabilities()
    {
        return $this->availabilities;
    }

    /**
     * Add therapist
     *
     * @param \AppBundle\Entity\Therapist $therapist
     * @return Business
     */
    public function addTherapist(\AppBundle\Entity\Therapist $therapist)
    {
        $this->therapist[] = $therapist;

        return $this;
    }

    /**
     * Remove therapist
     *
     * @param \AppBundle\Entity\Therapist $therapist
     */
    public function removeTherapist(\AppBundle\Entity\Therapist $therapist)
    {
        $this->therapists->removeElement($therapist);
    }

    /**
     * Get thereapists
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTherapists()
    {
        return $this->therapists;
    }

    public function getServicesAsChoices() {
        $array = array();
        foreach ($this->services as $service) {
            $id = $service->getId();
            $array[$id] = $service->getLabel();
        }

        return $array;
    }

    protected $offers = array();

    public function getOffers() {
        return $this->offers;
    }

    public function addOffer(\AppBundle\Entity\Offer $offer) {
        $this->offers[] = $offer;
        return $this;
    }

    public function getOffersTreatmentHierarchy() {
        // Group these things by their treatment
        $treatments = array();

        foreach ($this->getOffers() as $offer) {

            $treatment = $offer->getTreatment();
            $tId = $treatment->getId();

            if (array_key_exists($tId, $treatments)) {
            } else {
                $treatments[$tId] = $treatment;
            }

            $treatments[$tId]->addOffer($offer);

        }

        return $treatments;
    }

    private $distanceFrom = 0;

    public function setDistanceFrom($distance) {
        $this->distanceFrom = $distance;
    }

    public function getDistanceFrom() {
        return $this->distanceFrom;
    }

    public function hasTreatments() {
        $treatments = $this->getTreatments();
        return count($treatments) > 0;
    }

    private $treatmentHierarchy = null;

    public function getTreatmentHierarchy() {

        if ($this->treatmentHierarchy) {
            return $this->treatmentHierarchy;
        }

        $treatments = $this->getTreatments();

        $categories = array();

        foreach($treatments as $treatment) {
            $category = $treatment->getTreatmentCategory();
            // Parent category name
            //getCategoryName
            $id = $category->getCategoryName();

            if (!array_key_exists($id, $categories)) {
                $slugify = new Slugify();

                $std = new \stdClass();
                $std->label = $id;
                $std->slug = $slugify->slugify($id);
                $std->treatments = array();
                $std->lowestPrice = false;

                $categories[$id] = $std;
            }

            $categories[$id]->treatments[] = $treatment;
            if ($std->lowestPrice === false || $std->lowestPrice > $treatment->getCheapestDiscountPrice()) {
                $std->lowestPrice = $treatment->getCheapestDiscountPrice();
            }

        }

        $this->treatmentHeirarchy = $categories;
        return $categories;

    }


    /**
     * Set landline
     *
     * @param string $landline
     * @return Business
     */
    public function setLandline($landline)
    {
        $this->landline = $landline;

        return $this;
    }

    /**
     * Get landline
     *
     * @return string
     */
    public function getLandline()
    {
        return $this->landline;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     * @return Business
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    public function loadRatings($yelp) {
        if ($this->getYelpId()) {
            $response = $yelp->getBusiness('soundview-service-center-mamaroneck');

            if ($response->rating) {
                $this->setAverageRating($response->rating);
            }
        }

        return $this;
    }

    public function getSeoDescription()
    {
        return $this->getDescription();
    }


    public function getSeoTitle()
    {
        return $this->getName() . ' - BiBeauty';
    }

    public function getSeoExtras()
    {
        $arr = array(
            'property' => array(
                'og:title'       => $this->getSeoTitle(),
                'og:description' => $this->getSeoDescription(),
            ),
        );

        if ($this->hasLogoAttachment()) {
            $arr['property']['og:image'] = $this->getLogoAttachment()->getLink();
        }

        return $arr;
    }
}
