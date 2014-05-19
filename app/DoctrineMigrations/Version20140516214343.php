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
        $this->addSql("ALTER TABLE `catering` ADD COLUMN `balance` int DEFAULT NULL AFTER `discount`;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `catering` DROP COLUMN `balance`;");
    }
}
