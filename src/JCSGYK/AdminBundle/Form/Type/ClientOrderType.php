<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use JCSGYK\AdminBundle\Entity\ClientOrder;
use JCSGYK\AdminBundle\Services\DataStore;

class ClientOrderType extends AbstractType
{
    // private $ds;

    /**
     * Save the Datastore for parameter retrieval
     *
     * @param \JCSGYK\AdminBundle\Services\DataStore $ds
     */
    // public function __construct(DataStore $ds)
    // {
    //     $this->ds = $ds;
    // }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\ClientOrder',
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('orders', 'hidden');
    }

    public function getName()
    {
        return 'clientorder';
    }
}