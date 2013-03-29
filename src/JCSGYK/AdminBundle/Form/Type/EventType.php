<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use JCSGYK\AdminBundle\Services\DataStore;
use JCSGYK\AdminBundle\Entity\UserRepository;

class EventType extends AbstractType
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

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\Event',
        ));
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('description', 'textarea', [
            'label' => 'Esemény részletes leírása',
            'required' => false
        ]);
        $builder->add('type', 'choice', [
            'label' => 'Megnevezés',
            'choices'   => $this->ds->getGroup(7),
            'required' => false
        ]);
        $builder->add('title_code', 'choice', [
            'label' => 'Esetkezelés jellege',
            'choices'   => $this->ds->getGroup(8),
            'required' => false
        ]);
        $builder->add('forward_code', 'choice', [
            'label' => 'Továbbirányítás',
            'choices'   => $this->ds->getGroup(9),
            'required' => false
        ]);
        $builder->add('activity_code', 'choice', [
            'label' => 'Egyéb tevékenység',
            'choices'   => $this->ds->getGroup(10),
            'required' => false
        ]);
        $builder->add('event_date', 'date', [
            'label' => 'Dátum',
            'widget' => 'choice',
            'format' => 'yMMdd',
            'required' => true
        ]);
        $builder->add('client_visit', 'checkbox', ['label' => 'Ügyfélfogadás']);
        $builder->add('client_cancel', 'checkbox', ['label' => 'Ügyfél lemondta']);

    }

    public function getName()
    {
        return 'event';
    }
}