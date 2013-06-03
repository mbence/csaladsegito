<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ClientParamType extends AbstractType
{
    private $ds;

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

        if (0 == $type) {
            $builder->add('value', 'choice', [
                'label' => $this->pgroup->getLabel(),
                'choices' => $this->ds->getGroup($this->pgroup->getId())
            ]);
        }
        else {
            $builder->add('value', 'text', ['label' => $this->pgroup->getLabel()]);
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\ClientParam',
        ));
    }

    public function getName()
    {
        return 'client_param';
    }
}