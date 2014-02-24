<?php
namespace JCSGYK\AdminBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ClientClass extends Constraint
{
    public $message = 'Hibás ügyiratszám!';

    public function validatedBy()
    {
        return 'client_validator';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

}

