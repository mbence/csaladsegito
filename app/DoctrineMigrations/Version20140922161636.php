<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140922161636 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE `home_help` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `club_id` int(11) DEFAULT NULL,
  `social_worker` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `income` int(11) DEFAULT NULL,
  `discount` tinyint(4) DEFAULT NULL,
  `discount_from` date DEFAULT NULL,
  `discount_to` date DEFAULT NULL,
  `agreement_from` date DEFAULT NULL,
  `agreement_to` date DEFAULT NULL,
  `services` text,
  `warning_system` tinyint(1) DEFAULT NULL,
  `handicap` smallint(6) DEFAULT NULL,
  `hours` smallint(6) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        $this->addSql("INSERT INTO `option` VALUES ('', '1', 'homehelpcosts',
'[[0,0,0,null],
[0,30210,20,null],
[30211,39900,40,null],
[39901,49875,80,null],
[49876,55005,120,null],
[55006,57855,160,null],
[57856,59850,200,null],
[59851,62700,235,null],
[62701,64980,275,null],
[64981,67545,315,null],
[67546,69825,355,null],
[69826,72105,395,null],
[72106,74670,435,null],
[74671,77520,475,null],
[77521,79800,515,null],
[79801,82365,555,null],
[82366,84930,595,null],
[84931,87495,630,null],
[87496,89775,670,null],
[89776,92340,710,null],
[92341,94905,750,null],
[94906,99999999,790,null],
[null,null,null,null]]', '2014-04-01', '1', '2014-09-18 18:06:44', '1', '2014-09-18 18:52:33', '1')");

    }

    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `option` WHERE company_id=1 AND name='homehelpcosts';");
        $this->addSql('DROP TABLE IF EXISTS `home_help`;');
    }
}
