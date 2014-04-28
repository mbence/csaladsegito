<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JCSGYK\AdminBundle\Entity\Company;
use JCSGYK\AdminBundle\Services\DataStore;

class CompanyType extends AbstractType
{
    private $ds;
    private $company;

    /**
     * Save the Datastore for parameter retrieval
     *
     * @param \JCSGYK\AdminBundle\Services\DataStore $ds
     */
    public function __construct(DataStore $ds, Company $company)
    {
        $this->ds = $ds;
        $this->company = $company;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('shortname', 'text', ['label' => 'Röv.']);
        $builder->add('name', 'text', ['label' => 'Név']);
        $builder->add('host', 'text', ['label' => 'Hosztok']);

        $builder->add('types', 'choice', [
            'label'    => 'Típusok',
            'choices'  => $this->ds->getClientTypeNames(),
            'multiple' => true,
            'expanded' => true
        ]);

        $client_types = $this->ds->getAllClientTypes();

        foreach ($client_types as $key => $type) {
            $builder->add('sequence_policy_'.$key, 'choice', [
                'label'   => 'Ügyiratszámozás',
                'mapped'  => false,
                'data'    => $this->company->getSequencePolicy()[$key],
                'expanded' => true,
                'choices' => [
                    Company::CONTINUOUS => 'Folyamatos',
                    Company::BY_YEAR    => 'Évente'
                ]
            ]);
            $builder->add('case_number_template_'.$key, 'text', [
                'label'  => 'Üsz formátum',
                'mapped' => false,
                'data'   => $this->company->getCaseNumberTemplate()[$key]
            ]);
        }

        $builder->add('logo', 'text', ['label' => 'Logo']);
        $builder->add('is_active', 'checkbox', ['label' => 'Aktív']);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\Company',
        ));
    }

    public function getName()
    {
        return 'company';
    }
}