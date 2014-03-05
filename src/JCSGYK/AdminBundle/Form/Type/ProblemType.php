<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use JCSGYK\AdminBundle\Entity\Problem;
use JCSGYK\AdminBundle\Services\DataStore;
use JCSGYK\AdminBundle\Entity\UserRepository;

class ProblemType extends AbstractType
{
    private $ds;
    private $problem;

    /**
     * Save the Datastore for parameter retrieval
     *
     * @param \JCSGYK\AdminBundle\Services\DataStore $ds
     */
    public function __construct(DataStore $ds, Problem $problem)
    {
        $this->ds = $ds;
        $this->problem = $problem;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\Problem',
        ));
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('title', 'text', ['label' => 'Cím', 'required' => false]);
        $builder->add('description', 'textarea', ['label' => 'Megjegyzés', 'required' => false]);

        // parametergroups
        $pgroups = $this->ds->getParamGroup(2);

        foreach ($pgroups as $param) {
            $choices = $this->ds->getGroup($param->getId());

            if (!empty($choices)) {
                $builder->add('param_' . $param->getId(), 'choice', [
                    'label' => $param->getName(),
                    'choices'   => $choices,
                    'mapped' => false,
                    'data' => $this->ds->paramConvert($this->problem->getParam($param->getId()), $param->getMultiple()),
                    'required' => $param->getRequired(),
                    'multiple' => $param->getMultiple(),
                    'expanded' => $param->getMultiple(),
                ]);
            }
            else {
                $builder->add('param_' . $param->getId(), 'text', [
                    'label' => $param->getName(),
                    'mapped' => false,
                    'data' => $this->problem->getParam($param->getId()),
                    'required' => false,
                    'attr' => ['class' => 'short']
                ]);
            }
        }

        $builder->add('assignee', 'entity', [
            'label' => 'Felelős',
            'class' => 'JCSGYKAdminBundle:User',
            'query_builder' => function(UserRepository $er) {
                return $er->createQueryBuilder('u')
                    ->where('u.enabled=1')
                    ->andWhere("u.roles NOT LIKE '%ROLE_SUPER_ADMIN%'")
                    ->orderBy('u.lastname', 'ASC', 'u.firstname', 'ASC');
            },
            'required' => false,
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