<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use JCSGYK\AdminBundle\Services\DataStore;
use JCSGYK\AdminBundle\Entity\UserRepository;

class ProblemType extends AbstractType
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

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\Problem',
        ));
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('title', 'text', ['label' => 'Probléma', 'required' => false]);
        $builder->add('description', 'textarea', ['label' => 'Megjegyzés', 'required' => false]);
        $builder->add('type', 'choice', [
            'label' => 'Jellege',
            'choices'   => $this->ds->getGroup(105),
            'required' => false
        ]);
        $builder->add('assignee', 'entity', [
            'label' => 'Felelős',
            'class' => 'JCSGYKAdminBundle:User',
            'query_builder' => function(UserRepository $er) {
                return $er->createQueryBuilder('u')
                    ->orderBy('u.lastname', 'ASC', 'u.firstname', 'ASC');
            },
        ]);

        $builder->add('debts', 'collection', [
            'label' => '',
            'type' => new DebtType($this->ds),
            'allow_add'    => true,
            'by_reference' => false,
        ]);
    }

    public function getName()
    {
        return 'problem';
    }
}