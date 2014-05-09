<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140509110742 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `client_order` DROP COLUMN `orders`, CHANGE COLUMN `date` `date` date NOT NULL, CHANGE COLUMN `changes` `change` tinyint(4) DEFAULT NULL;");
        $this->addSql("ALTER TABLE `invoice` CHANGE COLUMN `date` `start_date` date NOT NULL, ADD COLUMN `end_date` date NOT NULL AFTER `start_date`;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `client_order` ADD COLUMN `orders` text DEFAULT NULL AFTER `date`, CHANGE COLUMN `date` `date` date DEFAULT NULL, CHANGE COLUMN `change` `changes` text DEFAULT NULL;");
        $this->addSql("ALTER TABLE `invoice` CHANGE COLUMN `start_date` `date` date NOT NULL, DROP COLUMN `end_date`;");
    }
}
