<?php

namespace JCSGYK\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Problemproblems
 *
 * @ORM\Table(name="ProblemProblems")
 * @ORM\Entity
 */
class Problemproblems
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
     * @ORM\Column(name="RootProblem_ID", type="integer", nullable=false)
     */
    private $rootproblemId;

    /**
     * @var integer
     *
     * @ORM\Column(name="SubProblem_ID", type="integer", nullable=false)
     */
    private $subproblemId;



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
     * Set rootproblemId
     *
     * @param integer $rootproblemId
     * @return Problemproblems
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

    /**
     * Set subproblemId
     *
     * @param integer $subproblemId
     * @return Problemproblems
     */
    public function setSubproblemId($subproblemId)
    {
        $this->subproblemId = $subproblemId;
    
        return $this;
    }

    /**
     * Get subproblemId
     *
     * @return integer 
     */
    public function getSubproblemId()
    {
        return $this->subproblemId;
    }
}