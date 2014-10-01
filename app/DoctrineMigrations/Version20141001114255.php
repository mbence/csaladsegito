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
  `data` text,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        $this->addSql('CREATE TABLE `homehelp_months_clients` (
  `homehelp_month_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  PRIMARY KEY (`homehelp_month_id`,`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE IF EXISTS `homehelp_months_clients`;');
        $this->addSql('DROP TABLE IF EXISTS `homehelp_month`;');
    }
}
