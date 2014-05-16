<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140516214343 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `client_order` ADD COLUMN `billed_state` tinyint(1) DEFAULT NULL AFTER `closed`;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `client_order` DROP COLUMN `billed_state` ;");
    }
}
