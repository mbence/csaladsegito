<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AdminUserType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\AdminUser',
        ));
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', 'number');
        $builder->add('username', 'text', ['label' => 'Felhasználói név']);
        $builder->add('email', 'email');
        $builder->add('enabled', 'checkbox');
        $builder->add('lastlogin', 'datetime', array('widget' => 'single_text'));
    }

    public function getName()
    {
        return 'admin_user';
    }
}