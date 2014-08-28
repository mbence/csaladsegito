<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140822150240 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE `stat_archive` (
	`id` int NOT NULL AUTO_INCREMENT,
	`company_id` int NOT NULL,
	`type` smallint,
	`start` date,
	`end` date,
	`created_at` datetime,
	PRIMARY KEY (`id`))");
        $this->addSql("CREATE TABLE `stat_file` (
	`id` int NOT NULL AUTO_INCREMENT,
	`stat_archive_id` int NOT NULL,
	`type` int,
	`data` text,
	`file` blob,
	`created_at` datetime,
	PRIMARY KEY (`id`))");
    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE IF EXISTS `stat_archive`;');
        $this->addSql('DROP TABLE IF EXISTS `stat_file`;');
    }
}
