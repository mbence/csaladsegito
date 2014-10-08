<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141008112157 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `monthly_closing` ADD COLUMN `closingtype` smallint AFTER `files`;");
        $this->addSql("ALTER TABLE `invoice` ADD COLUMN `invoicetype` smallint AFTER `files`;");

    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `invoice` DROP COLUMN `invoicetype`");
        $this->addSql("ALTER TABLE `monthly_closing` DROP COLUMN `closingtype`");
    }
}
