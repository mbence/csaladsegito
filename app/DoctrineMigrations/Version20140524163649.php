<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140524163649 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `catering` ADD INDEX `client_id` (client_id);");
        $this->addSql("ALTER TABLE `client_order` ADD INDEX `client_id` (client_id, date, closed);");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `client_order` DROP INDEX `client_id`;");
        $this->addSql("ALTER TABLE `catering` DROP INDEX `client_id`;");
    }
}
