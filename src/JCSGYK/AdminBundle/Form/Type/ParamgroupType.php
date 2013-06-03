<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ParamgroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('label', 'text', ['label' => 'Név']);
        $builder->add('type', 'choice', [
            'label' => 'Név',
            'choices' => [
                0 => 'System',
                1 => 'Client',
                2 => 'Problem',
                3 => 'Event'
            ]
        ]);
        $builder->add('position', 'hidden');
        $builder->add('value_type', 'choice', [
            'label' => 'Adat típus',
            'choices' => [
                0 => 'Select',
                1 => 'Input',
            ]
        ]);
        $builder->add('is_active', 'checkbox', ['label' => 'Aktív']);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\Paramgroup',
        ));
    }

    public function getName()
    {
        return 'paramgroup';
    }
}