<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140430154331 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `option` ADD COLUMN `company_id` int NOT NULL AFTER `id`, ADD COLUMN `is_active` tinyint DEFAULT 1;');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `option` DROP COLUMN `company_id`, DROP COLUMN `is_active`;');

    }
}
