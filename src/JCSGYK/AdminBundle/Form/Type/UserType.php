<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\User',
        ));
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('firstname', 'text', ['label' => 'Keresztnév']);
        $builder->add('lastname', 'text', ['label' => 'Vezetéknév']);
        $builder->add('username', 'text', ['label' => 'Felhasználói név']);
        $builder->add('email', 'email', ['label' => 'E-Mail cím', 'required' => false]);
        $builder->add('roles', 'choice', [
            'label' => 'Jogosultságok',
            'choices' => [
                'ROLE_ASSISTANCE' => 'Asszisztens',
                'ROLE_FAMILY_HELP' => 'Családsegítő',
                'ROLE_CHILD_WELFARE' => 'Gyermekvédelem',
                'ROLE_ADMIN' => 'Admin',
                'ROLE_SUPERADMIN' => 'Superadmin'
            ],
            'multiple'  => true,
            'expanded'  => true,
        ]);
        $builder->add('enabled', 'checkbox', ['label' => 'Aktív']);
    }

    public function getName()
    {
        return 'user';
    }
}