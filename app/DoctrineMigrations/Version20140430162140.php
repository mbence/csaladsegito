<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140430162140 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `option` ADD COLUMN `valid_from` date DEFAULT NULL AFTER `value`;');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `option` DROP COLUMN `valid_from`;');
    }
}
