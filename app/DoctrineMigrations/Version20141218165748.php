<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141218165748 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `catering` ADD COLUMN `agreement_from` date,  ADD COLUMN `agreement_to` date, ADD COLUMN `paused_from` date, ADD COLUMN `paused_to` date;");
        $this->addSql("ALTER TABLE `home_help` ADD COLUMN `paused_from` date, ADD COLUMN `paused_to` date;");
        $this->addSql("UPDATE `catering` a SET `agreement_from` = (SELECT `created_at` FROM `client` c WHERE c.id=a.client_id LIMIT 1) WHERE `agreement_from` IS NULL;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `catering` DROP COLUMN `agreement_from`, DROP COLUMN `agreement_to`, DROP COLUMN `paused_from`, DROP COLUMN `paused_to`;");
        $this->addSql("ALTER TABLE `home_help` DROP COLUMN `paused_from`, DROP COLUMN `paused_to`;");
    }
}
