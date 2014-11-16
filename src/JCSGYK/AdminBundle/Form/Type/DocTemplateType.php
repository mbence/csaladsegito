<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use JCSGYK\AdminBundle\Entity\DocTemplate;
use JCSGYK\AdminBundle\Services\DataStore;

class DocTemplateType extends AbstractType
{
    private $ds;
    private $clubs;

    /**
     * Save the Datastore for parameter retrieval
     *
     * @param \JCSGYK\AdminBundle\Services\DataStore $ds
     */
    public function __construct(DataStore $ds)
    {
        $this->ds = $ds;
        $this->clubs = $this->ds->getClubs();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', ['label' => 'Név']);
        $builder->add('upload', 'file', ['label' => 'Fájl feltöltése']);

        $builder->add('client_template', 'checkbox', ['label' => 'ügyfél']);
        $builder->add('problem_template', 'checkbox', ['label' => 'probléma']);
        $builder->add('is_active', 'checkbox', ['label' => 'Aktív']);

        if (!empty($this->clubs)) {
            // clubs
            $builder->add('club', 'entity', [
                'label' => 'Klub',
                'class' => 'JCSGYKAdminBundle:Club',
                'choices'   => $this->clubs,
                'required' => true,
            ]);
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\DocTemplate',
        ));
    }

    public function getName()
    {
        return 'doc_template';
    }
}