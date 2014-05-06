<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use JCSGYK\AdminBundle\Entity\Event;
use JCSGYK\AdminBundle\Services\DataStore;

class EventType extends AbstractType
{
    private $ds;
    private $event;
    private $client_type;

    /**
     * Save the Datastore for parameter retrieval
     *
     * @param \JCSGYK\AdminBundle\Services\DataStore $ds
     */
    public function __construct(DataStore $ds, Event $event, $client_type)
    {
        $this->ds = $ds;
        $this->event = $event;
        $this->client_type = $client_type;
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
        $g7 = $this->ds->getParamgroupById('events');

        $builder->add('type', 'choice', [
            'label' => 'Megnevezés',
            'choices'   => $this->ds->getGroup('events'),
            'data' => $this->ds->paramConvert($this->event->getType(), $g7[2]),
            'required' => $g7[1],
            'multiple' => $g7[2],
            'expanded' => $g7[2],
        ]);
        // parametergroups
        $pgroups = $this->ds->getParamGroup(3, false, $this->client_type);

        foreach ($pgroups as $param) {
            $choices = $this->ds->getGroup($param->getId());

            if (!empty($choices)) {
                $builder->add('param_' . $param->getId(), 'choice', [
                    'label' => $param->getName(),
                    'choices'   => $choices,
                    'mapped' => false,
                    'data' => $this->ds->paramConvert($this->event->getParam($param->getId()), $param->getControl()),
                    'required' => $param->getRequired(),
                    'multiple' => $param->getControl(),
                    'expanded' => $param->getControl(),
                ]);
            }
            else {
                $builder->add('param_' . $param->getId(), 'text', [
                    'label' => $param->getName(),
                    'mapped' => false,
                    'data' => $this->event->getParam($param->getId()),
                    'required' => false,
                    'attr' => ['class' => 'short']
                ]);
            }
        }
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