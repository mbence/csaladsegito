<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140411155608 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `catering` (`id` int(11) NOT NULL AUTO_INCREMENT,`client_id` int(11) NOT NULL,`club_id` int(11) NOT NULL,`subscriptions` text,`menu` smallint(6) DEFAULT NULL,`is_single` tinyint(1) DEFAULT NULL,`income` int(11) DEFAULT NULL,`discount` tinyint(4) DEFAULT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE IF EXISTS `catering`;');
    }
}
