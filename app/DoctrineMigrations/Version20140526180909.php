<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140526180909 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `catering` CHANGE COLUMN `discount` `discount` varchar(255) DEFAULT NULL;");
        $this->addSql("ALTER TABLE `club` CHANGE COLUMN `user_id` `users` varchar(255) DEFAULT NULL;");
        $this->addSql("ALTER TABLE `invoice` ADD INDEX `client_id` (client_id, `status`);");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `invoice` DROP INDEX `client_id`;");
        $this->addSql("ALTER TABLE `club` CHANGE COLUMN `users` `user_id` int DEFAULT NULL;");
        $this->addSql("ALTER TABLE `catering` CHANGE COLUMN `discount` `discount` int DEFAULT NULL;");
    }
}
