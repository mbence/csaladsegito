<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContext;
use JCSGYK\AdminBundle\Services\DataStore;

class UserType extends AbstractType
{
    protected $ds;
    protected $security;

    /**
     * Save the Datastore for parameter retrieval
     *
     * @param \JCSGYK\AdminBundle\Services\DataStore $ds
     */
    public function __construct(DataStore $ds, SecurityContext $security_context)
    {
        $this->ds = $ds;
        $this->security = $security_context;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\User',
        ));
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // check the company for enabled client-types
        $co = $this->ds->getCompany();

        $choices = [
            'ROLE_ASSISTANCE' => 'Asszisztens',
            'ROLE_FAMILY_HELP' => 'Családsegítő',
            'ROLE_CHILD_WELFARE' => 'Gyermekvédelem',
            'ROLE_ADMIN' => 'Admin',
        ];
        if (!empty($co['types'])) {
            $types = array_flip(explode(',', $co['types']));
            if (!isset($types[1])) {
                unset($choices['ROLE_FAMILY_HELP']);
            }
            if (!isset($types[2])) {
                unset($choices['ROLE_CHILD_WELFARE']);
            }
        }
        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            $choices['ROLE_SUPER_ADMIN'] = 'Superadmin';
        }

        $builder->add('firstname', 'text', ['label' => 'Keresztnév']);
        $builder->add('lastname', 'text', ['label' => 'Vezetéknév']);
        $builder->add('username', 'text', ['label' => 'Felhasználói név']);
        $builder->add('email', 'email', ['label' => 'E-Mail cím', 'required' => false]);
        $builder->add('roles', 'choice', [
            'label' => 'Jogosultságok',
            'choices' => $choices,
            'multiple'  => true,
            'expanded'  => true,
        ]);
        $builder->add('enabled', 'checkbox', ['label' => 'Aktív']);

        $builder->add('plainPassword', 'repeated', array(
            'type' => 'password',
            'first_options' => array('label' => 'Jelszó'),
            'second_options' => array('label' => 'Jelszó újra'),
            'invalid_message' => 'A nem egyezik a két jelszó',
        ));
    }

    public function getName()
    {
        return 'user';
    }
}