<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Personproblems
 *
 * @ORM\Table(name="PersonProblems")
 * @ORM\Entity
 */
class Personproblems
{
    /**
     * @var integer
     *
     * @ORM\Column(name="PP_ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $ppId;

    /**
     * @var integer
     *
     * @ORM\Column(name="Person_ID", type="integer", nullable=false)
     */
    private $personId;

    /**
     * @var integer
     *
     * @ORM\Column(name="RootProblem_ID", type="integer", nullable=false)
     */
    private $rootproblemId;



    /**
     * Get ppId
     *
     * @return integer 
     */
    public function getPpId()
    {
        return $this->ppId;
    }

    /**
     * Set personId
     *
     * @param integer $personId
     * @return Personproblems
     */
    public function setPersonId($personId)
    {
        $this->personId = $personId;
    
        return $this;
    }

    /**
     * Get personId
     *
     * @return integer 
     */
    public function getPersonId()
    {
        return $this->personId;
    }

    /**
     * Set rootproblemId
     *
     * @param integer $rootproblemId
     * @return Personproblems
     */
    public function setRootproblemId($rootproblemId)
    {
        $this->rootproblemId = $rootproblemId;
    
        return $this;
    }

    /**
     * Get rootproblemId
     *
     * @return integer 
     */
    public function getRootproblemId()
    {
        return $this->rootproblemId;
    }
}