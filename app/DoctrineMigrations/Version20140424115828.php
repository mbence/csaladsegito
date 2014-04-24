<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140424115828 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `parameter` CHANGE COLUMN `name` `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_hungarian_ci DEFAULT NULL;');
        $this->addSql('CREATE TABLE `option` (`id` int(11) NOT NULL AUTO_INCREMENT,`name` varchar(64) NOT NULL,`value` text,`created_by` int(11) DEFAULT NULL,`created_at` datetime DEFAULT NULL,`modified_by` int(11) DEFAULT NULL,`modified_at` datetime DEFAULT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `parameter` CHANGE COLUMN `name` `name` text CHARACTER SET utf8 COLLATE utf8_hungarian_ci DEFAULT NULL;');
        $this->addSql('DROP TABLE IF EXISTS `option`;');
    }
}
