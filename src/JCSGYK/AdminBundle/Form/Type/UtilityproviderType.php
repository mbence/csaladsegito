<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use JCSGYK\AdminBundle\Services\DataStore;

class UtilityproviderType extends AbstractType
{
    protected $ds;

    /**
     * Save the Datastore for parameter retrieval
     *
     * @param \JCSGYK\AdminBundle\Services\DataStore $ds
     */
    public function __construct(DataStore $ds)
    {
        $this->ds = $ds;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', 'choice', [
            'label' => '',
            'choices' => $this->ds->getGroup(2),
        ]);
        $builder->add('value', 'text', ['label' => '']);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\Utilityprovider',
        ));
    }

    public function getName()
    {
        return 'utilityprovier';
    }
}