<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
// use JCSGYK\AdminBundle\Entity\Option;

class CateringCostType extends AbstractType
{
    // private $ds;

    // *
    //  * Save the Datastore for parameter retrieval
    //  *
    //  * @param \JCSGYK\AdminBundle\Services\DataStore $ds
     
    // public function __construct(DataStore $ds)
    // {
    //     $this->ds = $ds;
    // }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('valid_from', 'date', ['label' => 'Érvényes kezdete']);

        // foreach ($options['value'] as $key => $value ) {
            $builder->add('value', 'collection', [
                'type'  => 'text',
                ]);
        // }

        $builder->add('is_active', 'checkbox', ['label' => 'Aktív']);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\Option',
        ));
    }

    public function getName()
    {
        return 'cateringcosts';
    }
}