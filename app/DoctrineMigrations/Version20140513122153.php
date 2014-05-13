<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140513122153 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `monthly_closing` CHANGE COLUMN `date` `start_date` date NOT NULL, ADD COLUMN `end_date` date NOT NULL AFTER `start_date`;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `monthly_closing` CHANGE COLUMN `start_date` `date` date NOT NULL, DROP COLUMN `end_date`;");
    }
}
