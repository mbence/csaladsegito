<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Services\DataStore;
use JCSGYK\AdminBundle\Entity\HomeHelp;

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
        $builder->add('users', 'choice', [
            'label' => 'Koordinátor',
            'choices' => $this->ds->getCaseAdmins(Client::CA),
            'required' => false,
            'multiple'  => true,
            'expanded'  => false,
            'attr'  => ['style' => 'height: 200px;']
        ]);

        $lunch_types = $this->ds->getGroup('lunch_types');
        if (empty($lunch_types)) {
            $lunch_types = [];
        }

        $builder->add('lunch_types', 'choice', [
            'label' => 'Ebéd típusok',
            'choices' => $lunch_types,
            'multiple'  => true,
            'expanded'  => true,
        ]);

        $builder->add('homehelptype', 'choice', [
            'label' => 'Típus',
            'choices' => [
                HomeHelp::HELP => 'Gondozás',
                HomeHelp::VISIT => 'Látogatás'
            ],
            'expanded'  => true,
        ]);

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