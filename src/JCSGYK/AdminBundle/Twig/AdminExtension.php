<?php

namespace JCSGYK\AdminBundle\Twig;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

use JCSGYK\AdminBundle\Entity\Client;
use JCSGYK\AdminBundle\Entity\Problem;
use JCSGYK\AdminBundle\Entity\Paramgroup;

class AdminExtension extends \Twig_Extension
{
    private $translator;
    private $ds;

    public function __construct(Translator $translator, $ds)
    {
        $this->translator = $translator;
        $this->ds = $ds;
    }

    public function getFilters()
    {
        return [
            'fdate' => new \Twig_Filter_Method($this, 'formatDate'),
            'check' => new \Twig_Filter_Method($this, 'check', ['is_safe' => ['html']]),
            'fphone' => new \Twig_Filter_Method($this, 'formatPhone'),
            'gender' => new \Twig_Filter_Method($this, 'gender'),
            'ctype' => new \Twig_Filter_Method($this, 'clientType'),
            'fcurr' => new \Twig_Filter_Method($this, 'formatCurrency'),
            'cid' => new \Twig_Filter_Method($this, 'formatId'),
            'casenum' => new \Twig_Filter_Method($this, 'formatCaseNumber'),
            'caselabel' => new \Twig_Filter_Method($this, 'formatCaseLabel'),
            'adate' => new \Twig_Filter_Method($this, 'formatAgreeDate', ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions()
    {
        return array(
            'fname' => new \Twig_Function_Method($this, 'formatName'),
            'param' => new \Twig_Function_Method($this, 'getParam'),
            'pgroup' => new \Twig_Function_Method($this, 'getParamGroup'),
            'inquiry_types' => new \Twig_Function_Method($this, 'getInquiryTypes'),
            'faddr' => new \Twig_Function_Method($this, 'formatAddress'),
            'pstatus' => new \Twig_Function_Method($this, 'problemStatus'),
            'rel_types' => new \Twig_Function_Method($this, 'getRelationTypes'),
            'casefield' => new \Twig_Function_Method($this, 'formatCaseNumberFields', ['is_safe' => ['html']]),
        );
    }

    /**
     * Returns the array of the parameter groups
     * @param int $group Paramgroup type
     * @return array of Paramgroups
     */
    public function getParamGroup($group = 1)
    {
        return $this->ds->getParamGroup($group);
    }

    public function problemStatus(Problem $problem)
    {
        $re = $problem->getIsActive() ? 'nyitott' : 'lezárt';
        if ($problem->getIsActive() == 0 && is_null($problem->getConfirmer())) {
            $re = 'jóváhagyásra vár';
        }

        return $this->translator->trans($re);
    }

    public function formatAgreeDate($d)
    {
        if (is_null($d)) {
            return '-';
        }
        if ($d instanceof \DateTime) {
            if ($d->format('Y') == '9999') {
                return 'visszavonásig';
            }
            else {
                $class = $d < new \DateTime('today') ? ' expired' : '';

                return sprintf('<span class="agreement-date%s">%s</span>', $class, $d->format('Y.m.d.'));
            }
        }
    }

    public function formatId($val)
    {
        return 'Ü-' . str_pad($val, 5, '0', STR_PAD_LEFT);
    }

    public function formatCaseLabel($type)
    {
        return $this->translator->trans($type == Client::FH ? 'Ügyfélszám' : 'Ügyiratszám');
    }

    public function formatCaseNumberFields($year, $num)
    {
        global $view;

        $co = $this->ds->getCompany();
        $tpl = $co['caseNumberTemplate'];
        if (empty($tpl)) {
            $tpl = '{num}';
        }
        preg_match_all('/(.*?)(\{.*?\})(.*?)/', $tpl, $matches, PREG_SET_ORDER);
        $re = '<table class="client-edit-inner" cellspacing="0" border="0" style="width:auto;"><tr>';
        foreach ($matches as $m) {
            if (!empty($m[1])) {
                $re .= '<td><div class="bottompad4">' . $m[1] . '</div></td>';
            }
            if (!empty($m[2])) {
                $re .= '<td class="short">';
                if (strpos($m[2], 'year') !== false) {
                    $re .= $year;
                }
                elseif (strpos($m[2], 'num') !== false) {
                    $re .= $num;
                }
                $re .= '</td>';
            }

            if (!empty($m[3])) {
                $re .= '<td><div class="bottompad4">' . $m[3] . '</div></td>';
            }
        }
        $re .= '</tr></table>';

        return $re;
    }

    public function formatCaseNumber($client)
    {
        $co = $this->ds->getCompany();
        $tpl = $co['caseNumberTemplate'];
        if (empty($tpl)) {
            $tpl = '{num}';
        }

        if ($client instanceof Client) {
            $type = $client->getType();
            $case_number = $client->getCaseNumber();
            $case_year = $client->getCaseYear();
        }
        else {
            $type = $client['type'];
            $case_number = $client['case_number'];
            $case_year = $client['case_year'];
        }

        // replace the year and number
        $re = str_replace(['{year}', '{num}'], [$case_year, $case_number], $tpl);
        // find the number pad
        preg_match('/\{num,(\d)\}/', $tpl, $matches);
        if (!empty($matches[1])) {
            $padded_case_number = str_pad($case_number, $matches[1], '0', STR_PAD_LEFT);
            $re = str_replace($matches[0], $padded_case_number, $re);
        }

        return $re;
    }

    public function formatFilename($in)
    {
        $tr = array('á' => 'a', 'Á' => 'A', 'é' => 'e', 'É' => 'E', 'í' => 'i', 'Í' => 'I', 'ó' => 'o', 'Ó' => 'O', 'ö' => 'o', 'Ö' => 'O', 'ő' => 'o', 'Ő' => 'O', 'ú' => 'u', 'Ú' => 'U', 'ü' => 'u', 'Ü' => 'U', 'ű' => 'u', 'Ű' => 'U', ' ' => '_', '.' => '');

        return str_replace(array_keys($tr), array_values($tr), $in);
    }

    public function check($val)
    {
        return $val ? '&#10004;' : '-';
    }

    public function getInquiryTypes()
    {
        return $this->ds->getGroup(1);
    }

    public function getRelationTypes($type = null)
    {
        $ptypes = $this->ds->getRelationTypes();

        if (is_null($type)) {
            return $ptypes;
        }

        return !empty($ptypes[$type]) ? $ptypes[$type] : '';
    }

    /**
     * Returns a parameter by it's id
     *
     * @param integer $id Parameter id
     */
    public function getParam($id, $paramGroup = null)
    {
        $param = $this->ds->get($id, $paramGroup);

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
     * Address formatter
     *
     * @param string $zipCode
     * @param string $city
     * @param string $street
     * @param string $streetType
     * @param string $streetNumber
     * @param string $flatNumber
     * @return string The formatted address
     */
    public function formatAddress($country, $zipCode, $city, $street, $streetType, $streetNumber, $flatNumber = '')
    {
        if (empty($country) || $country == 'Magyarország') {
            $country = '';
        }
        else {
            $country .= ', ';
        }
        if (!empty($streetNumber) && '.' != substr($streetNumber, -1)) {
            $streetNumber .= '.';
        }
        if (!empty($city)) {
            $city .= ',';
        }
        $re = sprintf('%s%s %s %s %s %s', $country, $zipCode, $city, $street, $streetType, $streetNumber);
        if (!empty($flatNumber)) {
            $re .= sprintf(' (%s)', $flatNumber);
        }

        return $re;
    }

    /**
     * Format a date
     * @param \JCSGYK\AdminBundle\Twig\DateTime $d
     * @param string $type
     * @return formatted string or nothing if not a \DateTime given
     */
    public function formatDate($d = null, $type = '')
    {
        if (is_null($d)) {
            $d = new \DateTime();
        }
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
            // short date time with optional date (only if date is not today)
            elseif ('osdt' ==  $type) {
                return $d->format('ymd') == date('ymd') ? $d->format('H:i:s') : $d->format('Y.m.d. H:i:s');
            }
            // long date (with month name)
            else {
                return $d->format('Y. ') .  $this->translator->trans($this->ds->getMonth($d->format('n'))) . $d->format(' j.');
            }
        }
    }

    /**
     * Get gender name
     *
     * @param int $gender_id
     * @return string
     */
    public function gender($gender_id)
    {
        return $this->translator->trans($gender_id == 1 ? 'férfi' : 'nő');
    }

    /**
     * Return client type names
     *
     * @param int $type
     * @return string
     */
    public function clientType($type)
    {
        return $this->translator->trans($type == 1 ? 'családsegítő' : 'gyermekjólét');
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