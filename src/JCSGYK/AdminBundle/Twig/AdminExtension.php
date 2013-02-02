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

    public function getFilters()
    {
        return [
            'fdate' => new \Twig_Filter_Method($this, 'formatDate'),
        ];
    }

    public function getFunctions()
    {
        return array(
            'fname' => new \Twig_Function_Method($this, 'formatName'),
            'gender' => new \Twig_Function_Method($this, 'gender'),
            'fphone' => new \Twig_Function_Method($this, 'formatPhone'),
            'param' => new \Twig_Function_Method($this, 'getParam'),
            'inquiry_types' => new \Twig_Function_Method($this, 'getInquiryTypes'),
            'fcurr' => new \Twig_Function_Method($this, 'formatCurrency'),
            'check' => new \Twig_Function_Method($this, 'check', ['is_safe' => ['html']]),
        );
    }

    public function check($val)
    {
        return $val ? '&#10004;' : '-';
    }

    public function getInquiryTypes()
    {
        return $this->dbparams->getGroup(1);
    }
    /**
     * Returns a parameter by it's id
     *
     * @param integer $id Parameter id
     */
    public function getParam($id)
    {
        $param = $this->dbparams->get($id);

        return $param ? $param : 'Nincs megadva';
    }

    public function formatName($firstname, $lastname, $title = '')
    {
        $re = '';
        $re .= $title ? $title . ' ' : '';
        $re .= $lastname . ' ' . $firstname;

        return $re;
    }

    /**
     * Format a date
     * @param \JCSGYK\AdminBundle\Twig\DateTime $d
     * @param string $type
     * @return formatted string or nothing if not a \DateTime given
     */
    public function formatDate($d, $type = '')
    {
        if ($d instanceof \DateTime) {
            // TODO: find a better place for the month names
            $months = ['január', 'február', 'március', 'április', 'május', 'június', 'július', 'augusztus', 'szeptember', 'október', 'november', 'december'];

            // short date
            if ('sd' == $type) {
                return $d->format('Y.m.d.');
            }
            // date time
            elseif ('dt' ==  $type) {
                return $this->formatDate($d) . $d->format(' H:i:s');
            }
            // short date time
            elseif ('sdt' ==  $type) {
                return $d->format('Y.m.d. H:i:s');
            }
            // long date (with month name)
            else {
                return $d->format('Y. ') .  $this->translator->trans($months[$d->format('n') - 1]) . $d->format(' j.');
            }
        }
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

    public function formatCurrency ($number, $decimals = 0, $decPoint = ',', $thousandsSep = ' ')
    {
        if (!empty($number)) {
            $price = number_format($number, $decimals, $decPoint, $thousandsSep);
            $price = $price . ' Ft';
        }
        else {
            $price = '';
        }

        return $price;
    }

/*
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