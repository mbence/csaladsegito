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

    /**
     * Save the Datastore for parameter retrieval
     *
     * @param \JCSGYK\AdminBundle\Services\DataStore $ds
     */
    public function __construct(DataStore $ds)
    {
        $this->ds = $ds;
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
            'choices'   => $this->ds->getClubs(),
            'required' => true,
        ]);

        $builder->add('menu', 'choice', [
            'label' => 'Ebéd',
            'choices'   => $this->ds->getGroup('lunch_types'),
        ]);

        $builder->add('is_single', 'checkbox', ['label' => 'Egyedülálló']);
        $builder->add('income', 'text', ['label' => 'Jövedelem', 'required' => false]);
        $builder->add('discount', 'text', ['label' => 'Mérséklés', 'required' => false]);
    }

    public function getName()
    {
        return 'problem';
    }
}