<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use JCSGYK\AdminBundle\Entity\Catering;
use JCSGYK\AdminBundle\Services\DataStore;
use JCSGYK\AdminBundle\Entity\ClubRepository;

class CateringType extends AbstractType
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
            'data_class' => 'JCSGYK\AdminBundle\Entity\Catering',
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $company_id = $this->ds->getCompanyId();

        // clubs
        $builder->add('club', 'entity', [
            'label'    => 'Klub',
            'class'    => 'JCSGYKAdminBundle:Club',
            'choices'  => $this->clubs,
            'required' => true,
        ]);

        $lunch_types = $this->ds->getGroup('lunch_types');
        if (empty($lunch_types)) {
            $lunch_types = [];
        }

        $builder->add('menu', 'choice', [
            'label'   => 'Ebéd',
            'choices' => $lunch_types,
        ]);

        $builder->add('subscriptions', 'hidden');
        $builder->add('isSingle', 'checkbox', ['label' => 'Egyedülálló']);
        $builder->add('income', 'text', ['label' => 'Jövedelem (Ft)', 'required' => false]);
        $builder->add('discount', 'text', [
            'label'    => 'Mérséklés (%)',
            'required' => false,
            'attr'     => array('class' => 'short'),
        ]);

        $min_date = 1;
        if (date('H') >= 10) {
            $min_date = 2;
        }

        $builder->add('discountFrom', 'date', [
            'label'    => 'Kezdete',
            'widget'   => 'single_text',
            'attr'     => array('class' => 'datepicker'),
            'required' => false,
                //'html5' => false,
        ]);
        $builder->add('discountTo', 'date', [
            'label'    => 'Vége',
            'widget'   => 'single_text',
            'attr'     => array('class' => 'datepicker'),
            'required' => false,
                //'html5' => false,
        ]);

        $builder->add('agreementFrom', 'date', [
            'label'    => 'Megállapodás',
            'widget'   => 'single_text',
            'attr'     => array('class' => 'datepicker'),
            'required' => false,
                //'html5' => false,
        ]);
        $builder->add('agreementTo', 'date', [
            'label'    => 'Vége',
            'widget'   => 'single_text',
            'attr'     => array('class' => 'datepicker', 'data-min-date' => $min_date),
            'required' => false,
                //'html5' => false,
        ]);
        $builder->add('pausedFrom', 'date', [
            'label'    => 'Szüneteltetés',
            'widget'   => 'single_text',
            'attr'     => array('class' => 'datepicker', 'data-min-date' => $min_date),
            'required' => false,
                //'html5' => false,
        ]);
        $builder->add('pausedTo', 'date', [
            'label'    => 'Vége',
            'widget'   => 'single_text',
            'attr'     => array('class' => 'datepicker', 'data-min-date' => $min_date),
            'required' => false,
                //'html5' => false,
        ]);
    }

    public function getName()
    {
        return 'catering';
    }
}