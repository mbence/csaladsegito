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
            'label' => 'Klub',
            'class' => 'JCSGYKAdminBundle:Club',
            // 'choices'   => $this->ds->getClubs(),
            'choices'   => $this->clubs,
            'required' => true,
        ]);

        $lunch_types = $this->ds->getGroup('lunch_types');
        if (empty($lunch_types)) {
            $lunch_types = [];
        }

        $builder->add('menu', 'choice', [
            'label' => 'Ebéd',
            'choices'   => $lunch_types,
        ]);

        $builder->add('subscriptions', 'hidden');
        $builder->add('is_single', 'checkbox', ['label' => 'Egyedülálló']);
        $builder->add('income', 'text', ['label' => 'Jövedelem (Ft)', 'required' => false]);
        $builder->add('discount', 'text', ['label' => 'Mérséklés (Ft)', 'required' => false]);
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