<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140528123605 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `client_order` ADD COLUMN `menu` smallint AFTER `closed`");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `client_order` DROP COLUMN `menu`");
    }
}
