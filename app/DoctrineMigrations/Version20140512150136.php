<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140512150136 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `invoice` ADD COLUMN `days` text AFTER `items`, ADD COLUMN `changes` text AFTER `days`;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `invoice` DROP COLUMN `days`, DROP COLUMN `changes`;");
    }
}
