<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140411171854 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `monthly_closing` (`id` int(11) NOT NULL AUTO_INCREMENT,`company_id` int(11) NOT NULL,`date` date DEFAULT NULL,`status` tinyint(4) DEFAULT NULL,`summary` text COLLATE utf8_hungarian_ci,`created_by` int(11) DEFAULT NULL,`created_at` datetime DEFAULT NULL,PRIMARY KEY (`id`),KEY `date` (`company_id`,`date`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;');
        $this->addSql('CREATE TABLE `invoice` (`id` int(11) NOT NULL AUTO_INCREMENT,`company_id` int(11) NOT NULL,`client_id` int(11) NOT NULL,`date` date DEFAULT NULL,`items` text,`amount` int(11) DEFAULT NULL,`balance` int(11) DEFAULT NULL,`payments` text,`status` tinyint(4) DEFAULT NULL,`created_by` int(11) DEFAULT NULL,`created_at` datetime DEFAULT NULL,PRIMARY KEY (`id`),KEY `company_id` (`company_id`,`date`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE IF EXISTS `monthly_closing`;');
        $this->addSql('DROP TABLE IF EXISTS `invoice`;');
    }
}
