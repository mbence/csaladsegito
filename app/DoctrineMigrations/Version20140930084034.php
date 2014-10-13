<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140930084034 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `home_help` ADD COLUMN `inpatient` tinyint(1) AFTER `handicap`");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `home_help` DROP COLUMN `inpatient`");
    }
}
