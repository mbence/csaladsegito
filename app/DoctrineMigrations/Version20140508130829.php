<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140508130829 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `option` VALUES (0, '1', 'cateringcosts', '[[0,0,0,false],[0,28500,0,true],[0,30210,15,false],[30211,39900,35,null],[39901,45600,50,null],[45601,49875,70,null],[49876,55005,100,null],[55006,57855,135,null],[57856,59850,170,null],[59851,62700,205,null],[62701,64980,240,null],[64981,67545,270,null],[67546,69825,305,null],[69826,72105,340,null],[72106,74670,375,null],[74671,77520,410,null],[77521,79800,440,null],[79801,82365,475,null],[82366,84930,510,null],[84931,87495,545,null],[87496,89775,580,null],[89776,92340,610,null],[92341,94905,645,null],[94906,99999999,680,null]]', '2014-04-01', '1', '2014-05-06 09:58:08', '1', '2014-05-08 12:58:14', '1'), (0, '1', 'holidays', '[[\"2014-01-01\",\"1\",\"\\\u00daj\\\u00e9v\"],[\"2014-04-21\",\"1\",\"H\\\u00fasv\\\u00e9t h\\\u00e9tf\\\u0151\"],[\"2014-05-01\",\"1\",\"A munka \\\u00fcnnepe\"],[\"2014-05-02\",\"3\",null],[\"2014-05-10\",\"2\",\"m\\\u00e1jus 2. ledolgoz\\\u00e1sa\"],[\"2014-06-09\",\"1\",\"P\\\u00fcnk\\\u00f6sd h\\\u00e9tf\\\u0151\"],[\"2014-08-20\",\"1\",\"Az \\\u00e1llamalap\\\u00edt\\\u00e1s \\\u00fcnnepe\"],[\"2014-10-18\",\"2\",\"okt\\\u00f3ber 24. ledolgoz\\\u00e1sa\"],[\"2014-10-23\",\"1\",\"Az 1956-os forradalom \\\u00fcnnepe\"],[\"2014-10-24\",\"3\",null],[\"2014-12-13\",\"2\",\"december 24. ledolgoz\\\u00e1sa\"],[\"2014-12-24\",\"3\",\"Kar\\\u00e1csony\"],[\"2014-12-25\",\"1\",\"Kar\\\u00e1csony\"],[\"2014-12-26\",\"1\",\"Kar\\\u00e1csony\"]]', '2014-01-01', '1', '2014-05-08 10:17:06', '1', '2014-05-08 13:06:45', '1');");
    }

    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `option` WHERE name IN ('cateringcosts', 'holidays');");
    }
}
