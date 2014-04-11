<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140411141431 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `club` (`id` int(11) NOT NULL AUTO_INCREMENT,`company_id` int(11) NOT NULL,`name` varchar(100) NOT NULL,`address` varchar(255) DEFAULT NULL,`phone` varchar(100) DEFAULT NULL,`user_id` int(11) DEFAULT NULL,`foodtypes` text,`is_active` tinyint(1) DEFAULT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE IF EXISTS `club`;');
    }
}
