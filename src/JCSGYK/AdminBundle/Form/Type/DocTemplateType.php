<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JCSGYK\AdminBundle\Entity\DocTemplate;

class DocTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', ['label' => 'Név']);
        $builder->add('upload', 'file', ['label' => 'Fájl feltöltése']);

        $builder->add('doc_type', 'choice', [
            'label'    => 'Típus',
            'choices'  => [
                DocTemplate::CLIENT  => 'Ügyfél',
                DocTemplate::PROBLEM => 'Probléma'
            ],
            'expanded' => true
        ]);

        $builder->add('is_active', 'checkbox', ['label' => 'Aktív']);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\DocTemplate',
        ));
    }

    public function getName()
    {
        return 'doc_template';
    }
}