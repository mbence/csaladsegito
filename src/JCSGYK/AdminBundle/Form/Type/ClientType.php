<?php
namespace JCSGYK\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Services\DataStore;
use JCSGYK\AdminBundle\Form\Type\UtilityproviderType;
use JCSGYK\AdminBundle\Form\Type\AddressType;
use JCSGYK\AdminBundle\Entity\UserRepository;
use JCSGYK\AdminBundle\Form\Type\ParentType;

class ClientType extends AbstractType
{
    private $ds;
    private $client;

    /**
     * Save the Datastore for parameter retrieval
     *
     * @param \JCSGYK\AdminBundle\Services\DataStore $ds
     */
    public function __construct(DataStore $ds, Client $client)
    {
        $this->ds = $ds;
        $this->client = $client;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'JCSGYK\AdminBundle\Entity\Client',
        ));
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $client_types = $this->ds->getClientTypes();

        if (count($client_types) > 1) {
            $builder->add('type', 'choice', [
                'label' => 'Típus',
                'choices'   => $client_types,
            ]);
        }
        $builder->add('case_year', 'text', ['label' => '', 'required' => false]);
        $builder->add('case_number', 'text', ['label' => '', 'required' => false]);

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
            'required' => false
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
            'choices'   => $this->ds->getGroup(14),
        ]);
        $builder->add('citizenship_status', 'choice', [
            'label' => 'Állampolgársági jogállás',
            'choices'   => $this->ds->getGroup(15),
        ]);
        $builder->add('social_security_number', 'text', ['label' => 'TAJ', 'required' => false]);
        $builder->add('identity_number', 'text', ['label' => 'Szem.a.j', 'required' => false]);
        $builder->add('id_card_number', 'text', ['label' => 'Szig.sz.', 'required' => false]);

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

        $builder->add('family_size', 'text', ['label' => 'Igénylők', 'required' => false]);
        $builder->add('note', 'textarea', ['label' => 'Megjegyzés', 'required' => false]);

//        $builder->add('case_admin', 'text', ['label' => 'Esetgazda']);

        $builder->add('guardian_firstname', 'text', ['label' => 'Keresztnév', 'required' => false]);
        $builder->add('guardian_lastname', 'text', ['label' => 'Vezetéknév', 'required' => false]);

        $builder->add('utilityprovidernumbers', 'collection', [
            'label' => '',
            'type' => new UtilityproviderClientnumberType($this->ds),
            'allow_add'    => true,
            'by_reference' => false,
        ]);

        // parametergroups
        $pgroups = $this->ds->getParamGroup(1);

        foreach ($pgroups as $param) {
            if ($param->getValueType() == 0) {
                $builder->add('param_' . $param->getId(), 'choice', [
                    'label' => $param->getLabel(),
                    'choices'   => $this->ds->getGroup($param->getId()),
                    'mapped' => false,
                    'data' => $this->client->getParam($param->getId()),
                    'required' => false,
                ]);
            }
            else {
                $builder->add('param_' . $param->getId(), 'text', [
                    'label' => $param->getLabel(),
                    'mapped' => false,
                    'data' => $this->client->getParam($param->getId()),
                    'required' => false,
                ]);
            }
        }

        $builder->add('addresses', 'collection', [
            'label' => 'Gondozási hely',
            'type' => new AddressType(),
            'allow_add'    => true,
            'by_reference' => false,
        ]);

        // TODO: do something about disabled users (a custom form control probably)
        $builder->add('case_admin', 'entity', [
            'label' => 'Esetgazda',
            'class' => 'JCSGYKAdminBundle:User',
            'query_builder' => function(UserRepository $er) {
                return $er->createQueryBuilder('u')
                    ->orderBy('u.lastname', 'ASC', 'u.firstname', 'ASC');
            },
            'required' => false,
        ]);
    }

    public function getName()
    {
        return 'client';
    }
}