<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Services\DataStore;

class ClubType extends AbstractType
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
        $builder->add('name', 'text', ['label' => 'Név']);
        $builder->add('address', 'text', ['label' => 'Cím']);
        $builder->add('phone', 'text', ['label' => 'Telefon']);
        $builder->add('coordinator', 'entity', [
            'label' => 'Koordinátor',
            'class' => 'JCSGYKAdminBundle:User',
            'choices' => $this->ds->getCaseAdmins(Client::CA),
            'required' => false,
        ]);
        $builder->add('foodtypes', 'text', ['label' => 'Menü fajták']);
        $builder->add('is_active', 'checkbox', ['label' => 'Aktív']);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\Club',
        ));
    }

    public function getName()
    {
        return 'club';
    }
}