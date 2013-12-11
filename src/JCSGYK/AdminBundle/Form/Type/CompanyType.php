<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JCSGYK\AdminBundle\Entity\Company;

class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('shortname', 'text', ['label' => 'Röv.']);
        $builder->add('name', 'text', ['label' => 'Név']);
        $builder->add('host', 'text', ['label' => 'Hosztok']);
        $builder->add('types', 'text', ['label' => 'Típusok']);
        $builder->add('sequence_policy', 'choice', [
            'label' => 'Ügyiratszámozás',
            'choices' => [
                Company::CONTINUOUS => 'Folyamatos',
                Company::BY_YEAR => 'Évente'
            ]
        ]);
        $builder->add('case_number_template', 'text', ['label' => 'Üsz formátum']);
        $builder->add('logo', 'text', ['label' => 'Logo']);
        $builder->add('is_active', 'checkbox', ['label' => 'Aktív']);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\Company',
        ));
    }

    public function getName()
    {
        return 'company';
    }
}