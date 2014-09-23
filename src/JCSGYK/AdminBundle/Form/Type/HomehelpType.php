<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use JCSGYK\AdminBundle\Entity\Homehelp;
use JCSGYK\AdminBundle\Services\DataStore;
use JCSGYK\AdminBundle\Entity\ClubRepository;

class HomehelpType extends AbstractType
{
    private $ds;
    private $clubs;

    /**
     * Save the Datastore for parameter retrieval
     *
     * @param \JCSGYK\AdminBundle\Services\DataStore $ds
     */
    public function __construct(DataStore $ds, $clubs)
    {
        $this->ds = $ds;
        $this->clubs = $clubs;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\Homehelp',
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $company_id = $this->ds->getCompanyId();

        // clubs
        $builder->add('club', 'entity', [
            'label' => 'Klub',
            'class' => 'JCSGYKAdminBundle:Club',
            'choices'   => $this->clubs,
            'required' => true,
        ]);

        $social_workers = $this->ds->getGroup('social_workers');
        if (empty($social_workers)) {
            $social_workers = [];
        }
        $builder->add('social_worker', 'choice', [
            'label'   => 'Gondozó',
            'choices' => $social_workers,
        ]);

        $handicaps = $this->ds->getGroup('handicaps');
        if (empty($handicaps)) {
            $handicaps = [];
        }
        $builder->add('handicap', 'choice', [
            'label'    => 'Fogyaték',
            'choices'  => $handicaps,
            'required' => false,
            'multiple' => true,
            'expanded' => true
        ]);

        $services = $this->ds->getGroup('homehelp_services');
        if (empty($services)) {
            $services = [];
        }
        $builder->add('services', 'choice', [
            'label'    => 'Szolgáltatások',
            'choices'  => $services,
            'multiple' => true,
            'expanded' => true
        ]);

        $builder->add('warning_system', 'checkbox', ['label' => 'Jelzőrendszer']);
        $builder->add('hours', 'text', ['label' => 'ORSZI óra', 'required' => false]);
        $builder->add('income', 'text', ['label' => 'Jövedelem (Ft)', 'required' => false]);
        $builder->add('discount', 'text', [
            'label'     => 'Mérséklés (%)',
            'required'  => false,
            'attr' => array('class' => 'short'),
        ]);
        $builder->add('discount_from', 'date', [
            'label' => 'Kezdete',
            'widget' => 'single_text',
            'attr' => array('class' => 'datepicker', 'type' => 'text'),
            'required' => false,
        ]);
        $builder->add('discount_to', 'date', [
            'label' => 'Vége',
            'widget' => 'single_text',
            'attr' => array('class' => 'datepicker'),
            'required' => false,
        ]);
        $builder->add('agreement_from', 'date', [
            'label' => 'Megállapodás kezdete',
            'widget' => 'single_text',
            'attr' => array('class' => 'datepicker', 'type' => 'text'),
            'required' => false,
        ]);
        $builder->add('agreement_to', 'date', [
            'label' => 'Vége',
            'widget' => 'single_text',
            'attr' => array('class' => 'datepicker'),
            'required' => false,
        ]);
        $builder->add('is_active', 'choice', [
            'label' => '',
            'choices' => [1 => 'Aktiválás', 0 => 'Szüneteltetés'],
            'expanded' => true
        ]);
    }

    public function getName()
    {
        return 'catering';
    }
}