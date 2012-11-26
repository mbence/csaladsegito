<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Addresses
 *
 * @ORM\Table(name="Addresses")
 * @ORM\Entity
 */
class Addresses
{
    /**
     * @var integer
     *
     * @ORM\Column(name="Address_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $addressId;

    /**
     * @var string
     *
     * @ORM\Column(name="City", type="text", nullable=true)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="Street", type="text", nullable=true)
     */
    private $street;

    /**
     * @var string
     *
     * @ORM\Column(name="StreetNum", type="text", nullable=true)
     */
    private $streetnum;

    /**
     * @var string
     *
     * @ORM\Column(name="FlatNum", type="text", nullable=true)
     */
    private $flatnum;

    /**
     * @var integer
     *
     * @ORM\Column(name="ZipCode", type="integer", nullable=true)
     */
    private $zipcode;



    /**
     * Get addressId
     *
     * @return integer 
     */
    public function getAddressId()
    {
        return $this->addressId;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return Addresses
     */
    public function setCity($city)
    {
        $this->city = $city;
    
        return $this;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set street
     *
     * @param string $street
     * @return Addresses
     */
    public function setStreet($street)
    {
        $this->street = $street;
    
        return $this;
    }

    /**
     * Get street
     *
     * @return string 
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set streetnum
     *
     * @param string $streetnum
     * @return Addresses
     */
    public function setStreetnum($streetnum)
    {
        $this->streetnum = $streetnum;
    
        return $this;
    }

    /**
     * Get streetnum
     *
     * @return string 
     */
    public function getStreetnum()
    {
        return $this->streetnum;
    }

    /**
     * Set flatnum
     *
     * @param string $flatnum
     * @return Addresses
     */
    public function setFlatnum($flatnum)
    {
        $this->flatnum = $flatnum;
    
        return $this;
    }

    /**
     * Get flatnum
     *
     * @return string 
     */
    public function getFlatnum()
    {
        return $this->flatnum;
    }

    /**
     * Set zipcode
     *
     * @param integer $zipcode
     * @return Addresses
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;
    
        return $this;
    }

    /**
     * Get zipcode
     *
     * @return integer 
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }
}