<?php

namespace JCSGYK\AdminBundle\Twig;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

class AdminExtension extends \Twig_Extension
{
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function getFunctions()
    {
        return array(
            'fname' => new \Twig_Function_Method($this, 'formatName'),
            'fdate' => new \Twig_Function_Method($this, 'formatDate'),
        );
    }

    public function formatName($title, $firstname, $lastname)
    {
        $re = '';
        $re .= $title ? $title . ' ' : '';
        $re .= $lastname . ' ' . $firstname;

        return $re;
    }

    public function formatDate(\Datetime $d)
    {
        // TODO: find a better place for the month names
        $months = ['január', 'február', 'március', 'április', 'május', 'június', 'július', 'augusztus', 'szeptember', 'október', 'november', 'december'];

        return $d->format('Y. ') .  $this->translator->trans($months[$d->format('n') - 1]) . $d->format(' j.');
    }

/*    public function getFilters()
    {
        return array(
            'price' => new \Twig_Filter_Method($this, 'priceFilter'),
        );
    }

    public function priceFilter($number, $decimals = 0, $decPoint = '.', $thousandsSep = ',')
    {
        $price = number_format($number, $decimals, $decPoint, $thousandsSep);
        $price = '$' . $price;

        return $price;
    }
*/
    public function getName()
    {
        return 'jcsgykadmin_adminextension';
    }
}