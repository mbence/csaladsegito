<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use JCSGYK\AdminBundle\Services\DataStore;

/**
 * A subset of the ClientType with only the personal data
 */
class RelativeType extends AbstractType
{
    protected $ds;
    protected $relation_type;
    protected $client_type;

    /**
     * Save the Datastore for parameter retrieval
     *
     * @param \JCSGYK\AdminBundle\Services\DataStore $ds
     */
    public function __construct(DataStore $ds, $relation_type, $client_type)
    {
        $this->ds = $ds;
        $this->relation_type = $relation_type;
        $this->client_type = $client_type;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\Client',
        ));
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('relation_type', 'choice', [
            'label'     => 'Típus',
            'choices'   => $this->ds->getRelationTypes($this->client_type),
            'mapped'    => false,
            'data'      => $this->relation_type,
        ]);
        $builder->add('id', 'hidden');
        $builder->add('title', 'text', ['label' => 'Titulus', 'required' => false]);
        $builder->add('firstname', 'text', ['label' => 'Keresztnév']);
        $builder->add('lastname', 'text', ['label' => 'Vezetéknév']);
        $builder->add('gender', 'choice', [
            'label' => 'Nem',
            'choices'   => ['1' => 'Férfi', '2' => 'Nő'],
        ]);
        $builder->add('birth_date', 'birthday', [
            'label' => 'Születési idő',
            'widget' => 'choice',
            'format' => 'yMMdd',
            'required' => false,
            'years' => range(1920, date('Y'))
        ]);
        $builder->add('birth_place', 'text', ['label' => 'Születési hely', 'required' => false]);
        $builder->add('birth_title', 'text', ['label' => 'Titulus', 'required' => false]);
        $builder->add('birth_firstname', 'text', ['label' => 'Keresztnév', 'required' => false]);
        $builder->add('birth_lastname', 'text', ['label' => 'Vezetéknév', 'required' => false]);
        $builder->add('mother_title', 'text', ['label' => 'Titulus', 'required' => false]);
        $builder->add('mother_firstname', 'text', ['label' => 'Keresztnév', 'required' => false]);
        $builder->add('mother_lastname', 'text', ['label' => 'Vezetéknév', 'required' => false]);
        $builder->add('citizenship', 'choice', [
            'label' => 'Állampolgárság',
            'choices'   => $this->ds->getGroup('citizenship'),
        ]);
        $builder->add('citizenship_status', 'choice', [
            'label' => 'Állampolgársági jogállás',
            'choices'   => $this->ds->getGroup('citizenship_status'),
        ]);
        $builder->add('mobile', 'text', ['label' => 'Mobil', 'required' => false]);
        $builder->add('phone', 'text', ['label' => 'Telefon', 'required' => false]);
        $builder->add('fax', 'text', ['label' => 'Fax', 'required' => false]);
        $builder->add('email', 'text', ['label' => 'E-Mail', 'required' => false]);

        $builder->add('country', 'text', ['label' => 'Ország', 'required' => false]);
        $builder->add('zip_code', 'text', ['label' => 'Irsz.', 'required' => false]);
        $builder->add('city', 'text', ['label' => 'Város', 'required' => false]);
        $builder->add('street', 'text', ['label' => 'Utca', 'required' => false]);
        $builder->add('street_type', 'text', ['label' => 'Közt.jell.', 'required' => false]);
        $builder->add('street_number', 'text', ['label' => 'Házsz.', 'required' => false]);
        $builder->add('flat_number', 'text', ['label' => 'Ajtó', 'required' => false]);

        $builder->add('location_country', 'text', ['label' => 'Ország', 'required' => false]);
        $builder->add('location_zip_code', 'text', ['label' => 'Irsz.', 'required' => false]);
        $builder->add('location_city', 'text', ['label' => 'Város', 'required' => false]);
        $builder->add('location_street', 'text', ['label' => 'Utca', 'required' => false]);
        $builder->add('location_street_type', 'text', ['label' => 'Közt.jell.', 'required' => false]);
        $builder->add('location_street_number', 'text', ['label' => 'Házsz.', 'required' => false]);
        $builder->add('location_flat_number', 'text', ['label' => 'Ajtó', 'required' => false]);

        $builder->add('note', 'textarea', ['label' => 'Megjegyzés', 'required' => false]);
    }

    public function getName()
    {
        return 'parent';
    }
}