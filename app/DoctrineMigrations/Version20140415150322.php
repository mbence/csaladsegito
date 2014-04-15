<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140415150322 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `lunch_order` (`id` int(11) NOT NULL AUTO_INCREMENT,`company_id` int(11) NOT NULL,`date` date DEFAULT NULL,`status` tinyint(4) DEFAULT NULL,`summary` text,`file` blob,`created_by` int(11) DEFAULT NULL,`created_at` datetime DEFAULT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE IF EXISTS `lunch_order`;');
    }
}
