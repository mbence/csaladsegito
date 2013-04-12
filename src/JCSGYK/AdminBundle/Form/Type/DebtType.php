<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

use JCSGYK\AdminBundle\Services\DataStore;

class DebtType extends AbstractType
{
    protected $ds;

    /**
     * Save the Datastore for parameter retrieval
     *
     * @param \JCSGYK\AdminBundle\Services\DataStore $ds
     */
    public function __construct(DataStore $ds)
    {
        $this->ds = $ds;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('utilityprovider', 'entity', [
            'label' => '',
            'class' => 'JCSGYKAdminBundle:Utilityprovider',
            'property' => 'name',
            'query_builder' => function(EntityRepository $er) {
                return $er->createQueryBuilder('u')
                    ->where('u.isActive=1')
                    ->andwhere('u.companyId=:company')
                    ->setParameter('company', $this->ds->getCompanyId())
                    ->orderBy('u.name', 'ASC');
            },
        ]);
        $builder->add('registered_debt', 'text', ['label' => '']);
        $builder->add('managed_debt', 'text', ['label' => '']);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\Debt',
        ));
    }

    public function getName()
    {
        return 'debt';
    }
}