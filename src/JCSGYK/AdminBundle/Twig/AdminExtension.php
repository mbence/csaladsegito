<?php 

namespace JCSGYK\AdminBundle\Twig;

class AdminExtension extends \Twig_Extension
{

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
        return $d->format("Y.m.d");
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