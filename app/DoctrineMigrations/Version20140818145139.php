<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140818145139 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `invoice` ADD COLUMN `cancel_id` int AFTER `end_date`");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `invoice` DROP COLUMN `cancel_id`");
    }
}
