<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use JCSGYK\AdminBundle\Services\DataStore;
use JCSGYK\AdminBundle\Form\Type\UtilityproviderType;

class ClientType extends AbstractType
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
            'data_class' => 'JCSGYK\AdminBundle\Entity\Client',
        ));
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text', ['label' => 'Titulus']);
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
        ]);
        $builder->add('birth_place', 'text', ['label' => 'Születési hely']);
        $builder->add('birth_title', 'text', ['label' => 'Titulus']);
        $builder->add('birth_firstname', 'text', ['label' => 'Keresztnév']);
        $builder->add('birth_lastname', 'text', ['label' => 'Vezetéknév']);
        $builder->add('mother_title', 'text', ['label' => 'Titulus']);
        $builder->add('mother_firstname', 'text', ['label' => 'Keresztnév']);
        $builder->add('mother_lastname', 'text', ['label' => 'Vezetéknév']);
        $builder->add('social_security_number', 'text', ['label' => 'TAJ']);
        $builder->add('identity_number', 'text', ['label' => 'Szem.a.j']);
        $builder->add('id_card_number', 'text', ['label' => 'Szig.sz.']);

        $builder->add('mobile', 'text', ['label' => 'Mobil']);
        $builder->add('phone', 'text', ['label' => 'Telefon']);
        $builder->add('fax', 'text', ['label' => 'Fax']);
        $builder->add('email', 'text', ['label' => 'E-Mail']);

        $builder->add('zip_code', 'text', ['label' => 'Irsz.']);
        $builder->add('city', 'text', ['label' => 'Város']);
        $builder->add('street', 'text', ['label' => 'Utca']);
        $builder->add('street_type', 'text', ['label' => 'Közt.jell.']);
        $builder->add('street_number', 'text', ['label' => 'Házsz.']);
        $builder->add('flat_number', 'text', ['label' => 'Ajtó']);

        $builder->add('location_zip_code', 'text', ['label' => 'Irsz.']);
        $builder->add('location_city', 'text', ['label' => 'Város']);
        $builder->add('location_street', 'text', ['label' => 'Utca']);
        $builder->add('location_street_type', 'text', ['label' => 'Közt.jell.']);
        $builder->add('location_street_number', 'text', ['label' => 'Házsz.']);
        $builder->add('location_flat_number', 'text', ['label' => 'Ajtó']);

        $builder->add('marital_status', 'choice', [
            'label' => 'Családi összetétel',
            'choices'   => $this->ds->getGroup(5),
        ]);

        // TODO: do we need this two?
        //$builder->add('citizenship', 'text', ['label' => 'állampolgárság']);
        //$builder->add('citizenship_status', 'text', ['label' => 'állampolgársági jogállás']);

        $builder->add('education_code', 'choice', [
            'label' => 'Végzettség',
            'choices'   => $this->ds->getGroup(3),
        ]);
        $builder->add('ec_activity', 'choice', [
            'label' => 'Gazd. aktiv.',
            'choices'   => $this->ds->getGroup(4),
        ]);

        $builder->add('family_size', 'text', ['label' => 'Igénylők']);
        $builder->add('note', 'textarea', ['label' => 'Megjegyzés']);

//        $builder->add('case_admin', 'text', ['label' => 'Esetgazda']);

        $builder->add('guardian_firstname', 'text', ['label' => 'Keresztnév']);
        $builder->add('guardian_lastname', 'text', ['label' => 'Vezetéknév']);

        $builder->add('utilityproviders', 'collection', [
            'label' => '',
            'type' => new UtilityproviderType($this->ds),
            'allow_add'    => true,
            'by_reference' => false,
        ]);

        // missing fields: country, location_country, doc_file, job_type
    }

    public function getName()
    {
        return 'client';
    }
}