<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141001114255 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE `homehelp_month` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `social_worker` int(11) DEFAULT NULL,
  `rowheaders` text,
  `data` text,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE  (`company_id`, `social_worker`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        $this->addSql('CREATE TABLE `homehelpmonths_clients` (
  `homehelpmonth_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  PRIMARY KEY (`homehelpmonth_id`,`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE IF EXISTS `homehelpmonths_clients`;');
        $this->addSql('DROP TABLE IF EXISTS `homehelp_month`;');
    }
}
