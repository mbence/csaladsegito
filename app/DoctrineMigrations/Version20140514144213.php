<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140514144213 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `client_order` CHANGE COLUMN `order` `order` tinyint(1) DEFAULT NULL, CHANGE COLUMN `status` `cancel` tinyint(1) DEFAULT NULL, CHANGE COLUMN `is_current` `closed` tinyint(1) DEFAULT NULL;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `client_order` CHANGE COLUMN `order` `order` tinyint(4) DEFAULT NULL, CHANGE COLUMN `cancel` `status` tinyint(4) DEFAULT NULL, CHANGE COLUMN `closed` `is_current` tinyint(1) DEFAULT NULL;");
    }
}
