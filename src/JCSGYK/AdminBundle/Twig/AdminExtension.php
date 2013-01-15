<?php

namespace JCSGYK\AdminBundle\Twig;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

class AdminExtension extends \Twig_Extension
{
    private $translator;
    private $dbparams;

    public function __construct(Translator $translator, $dbparams)
    {
        $this->translator = $translator;
        $this->dbparams = $dbparams;
    }

    public function getFunctions()
    {
        return array(
            'fname' => new \Twig_Function_Method($this, 'formatName'),
            'fdate' => new \Twig_Function_Method($this, 'formatDate'),
            'gender' => new \Twig_Function_Method($this, 'gender'),
            'fphone' => new \Twig_Function_Method($this, 'formatPhone'),
            'param' => new \Twig_Function_Method($this, 'getParam'),
        );
    }

    /**
     * Returns a parameter by it's id
     *
     * @param integer $id Parameter id
     */
    public function getParam($id)
    {
        return $this->dbparams->get($id);
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

    /**
     * Get gender name
     *
     * @param type $gender_id
     * @return type
     */
    public function gender($gender_id)
    {
        return $this->translator->trans($gender_id == 1 ? 'férfi' : 'nő');
    }

    /**
     * Format a phone number
     * @param string $phone_number
     * @return string
     */
    public function formatPhone($phone_number)
    {
        $prefix = '';
        $num = '';
        if (strlen($phone_number) == 7 || strlen($phone_number) == 6) {
            $num = $phone_number;
        }
        elseif (strlen($phone_number) == 8 && $phone_number[0] == '1') {
            $prefix = '1';
            $num = substr($phone_number, 1);
        }
        else {
            $prefix = substr($phone_number, 0, 2);
            $num = substr($phone_number, 2);
        }

        return sprintf('(%s) %s-%s', $prefix, substr($num, 0, 3), substr($num, 3));
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