<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140516162757 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `monthly_closing` ADD COLUMN `files` blob AFTER `created_at`;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `monthly_closing` DROP COLUMN `files` ;");
    }
}
