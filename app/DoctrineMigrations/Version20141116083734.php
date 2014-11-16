<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141116083734 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `doc_template` ADD COLUMN `club_id` int DEFAULT NULL");

    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `doc_template` DROP COLUMN `club_id`");

    }
}
