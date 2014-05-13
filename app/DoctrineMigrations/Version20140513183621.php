<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140513183621 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `client_order` CHANGE COLUMN `change` `order` tinyint(4) DEFAULT NULL;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `client_order` CHANGE COLUMN `order` `change` tinyint(4) DEFAULT NULL;");
    }
}
