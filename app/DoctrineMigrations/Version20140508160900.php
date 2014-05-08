<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140508160900 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `catering` ADD COLUMN `is_active` tinyint(1) DEFAULT '1' AFTER `discount`;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `catering` DROP COLUMN `is_active`;");
    }
}
