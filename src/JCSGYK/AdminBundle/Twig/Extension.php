<?php 

namespace JCSGYK\AdminBundle\Twig;

class Extension extends \Twig_Extension
{

    public function getFunctions()
    {
        return array(
            'admin_header' => new \Twig_Function_Method($this, 'adminHeader'),
        );
    }
    
    public function adminHeader($request)
    {
        //var_dump($request);
        //return true;
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
        return 'jcsgykadmin_extension';
    }
}