<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

use JCSGYK\AdminBundle\Services\DataStore;

// needed namespaces for 2.1 validation
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;

class ArchiveType extends AbstractType
{
    /** Datastore injection */
    protected $ds;
    /** Archive operation: 0 = close, 1 = open */
    protected $operation;

    /**
     * Save the Datastore for parameter retrieval
     *
     * @param \JCSGYK\AdminBundle\Services\DataStore $ds
     */
    public function __construct(DataStore $ds, $operation = 1)
    {
        $this->ds = $ds;
        $this->operation =  $operation;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', 'choice', [
            'label' => ($this->operation == 0 ? 'Lezárás oka' : 'Újranyitás oka'),
            'choices' => $this->ds->getGroup($this->operation == 0 ? 11 : 12),
        ]);
        $builder->add('description', 'textarea', [
            'label' => 'Megjegyzés',
            'required' => false
        ]);
        $builder->add('operation', 'hidden', array(
            'data' => $this->operation,
            'mapped' => false
        ));

        // VALIDATING NON MAPPED FIELD opearation direction
        $operationValidator = function(FormEvent $event){
            $form = $event->getForm();
            $op = $form->get('operation')->getData();
            if ($op != $this->operation) {
              $form->addError(new FormError($this->operation == 0 ? "Hiba: Az Ügyfél már újranyitásra került!" : "Hiba: Az Ügyfél már archiválásra került!"));
            }
        };

        // adding the validator to the FormBuilderInterface
        $builder->addEventListener(FormEvents::POST_BIND, $operationValidator);

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\Archive',
        ));
    }

    public function getName()
    {
        return 'archive';
    }
}