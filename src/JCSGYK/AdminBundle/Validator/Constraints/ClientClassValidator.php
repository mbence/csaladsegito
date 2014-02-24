<?php
namespace JCSGYK\AdminBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use JCSGYK\AdminBundle\Entity\Client;

class ClientClassValidator extends ConstraintValidator
{
    private $doctrine;

    public function __construct($doctrine) {
        $this->doctrine = $doctrine;
    }

    public function validate($client, Constraint $constraint)
    {
        $case_number = $client->getCaseNumber();

        // only check for valid case number, if it is set
        if (!empty($case_number)) {
            $case = $this->doctrine->getRepository('JCSGYKAdminBundle:Client')->getCase($client);
            if (empty($case)) {
                $this->context->addViolationAt(
                    'case_number',
                    $constraint->message,
                    array(),
                    null
                );
            }
        }
    }
}