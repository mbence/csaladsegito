<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140527095954 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `catering` CHANGE COLUMN `discount` `discount` int DEFAULT NULL, ADD COLUMN discount_from date DEFAULT NULL AFTER `discount`,  ADD COLUMN discount_to date DEFAULT NULL AFTER `discount_from`;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `catering` CHANGE COLUMN `discount` `discount` varchar(255) DEFAULT NULL, DROP COLUMN discount_from, DROP COLUMN discount_to;");
    }
}
