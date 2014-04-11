<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JCSGYK\AdminBundle\Entity\Club;
use JCSGYK\AdminBundle\Entity\UserRepository;

class ClubType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', ['label' => 'Név']);
        $builder->add('address', 'text', ['label' => 'Cím']);
        $builder->add('phone', 'text', ['label' => 'Telefon']);
        $builder->add('coordinator', 'entity', [
            'label' => 'Koordinátor',
            'class' => 'JCSGYKAdminBundle:User',
            'query_builder' => function(UserRepository $er) {
                return $er->createQueryBuilder('u')
                    ->where('u.enabled=1')
                    ->andWhere("u.roles NOT LIKE '%ROLE_SUPER_ADMIN%'")
                    ->orderBy('u.lastname', 'ASC', 'u.firstname', 'ASC');
            },
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