<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140422162757 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `client_sequence` ADD COLUMN `type` smallint AFTER `year`, CHANGE COLUMN `year` `year` smallint(6) DEFAULT NULL, DROP INDEX `company_id`, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`, `company_id`, `type`);');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `client_sequence` DROP COLUMN `type`, CHANGE COLUMN `year` `year` smallint(6) NOT NULL, ADD UNIQUE `company_id` USING BTREE (company_id), DROP PRIMARY KEY, ADD PRIMARY KEY (`id`, `company_id`);');
    }
}
