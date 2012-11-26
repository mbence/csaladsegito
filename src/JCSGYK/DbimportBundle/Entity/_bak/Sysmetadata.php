<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sysmetadata
 *
 * @ORM\Table(name="SysMetaData")
 * @ORM\Entity
 */
class Sysmetadata
{
    /**
     * @var integer
     *
     * @ORM\Column(name="SYS_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $sysId;

    /**
     * @var string
     *
     * @ORM\Column(name="MetaInfoKey", type="text", nullable=false)
     */
    private $metainfokey;

    /**
     * @var string
     *
     * @ORM\Column(name="MetaInfoValue", type="text", nullable=false)
     */
    private $metainfovalue;



    /**
     * Get sysId
     *
     * @return integer 
     */
    public function getSysId()
    {
        return $this->sysId;
    }

    /**
     * Set metainfokey
     *
     * @param string $metainfokey
     * @return Sysmetadata
     */
    public function setMetainfokey($metainfokey)
    {
        $this->metainfokey = $metainfokey;
    
        return $this;
    }

    /**
     * Get metainfokey
     *
     * @return string 
     */
    public function getMetainfokey()
    {
        return $this->metainfokey;
    }

    /**
     * Set metainfovalue
     *
     * @param string $metainfovalue
     * @return Sysmetadata
     */
    public function setMetainfovalue($metainfovalue)
    {
        $this->metainfovalue = $metainfovalue;
    
        return $this;
    }

    /**
     * Get metainfovalue
     *
     * @return string 
     */
    public function getMetainfovalue()
    {
        return $this->metainfovalue;
    }
}