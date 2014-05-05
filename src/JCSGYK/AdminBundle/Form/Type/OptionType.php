<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('valid_from', 'date', ['label' => 'Érvényes kezdete']);
        $builder->add('value', 'hidden');
        // $builder->add('created_by', 'text', ['label' => 'Létrehozó');

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
        return 'options';
    }
}