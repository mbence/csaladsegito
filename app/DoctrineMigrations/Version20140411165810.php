<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140411165810 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `parameter` CHANGE COLUMN `name` `name` text CHARACTER SET utf8 COLLATE utf8_hungarian_ci DEFAULT NULL, DROP INDEX `gpn`, ADD INDEX `gpn` USING BTREE (company_id, group_id, position);');
        $this->addSql('ALTER TABLE `paramgroup` ADD COLUMN `key` varchar(32) AFTER `type`, ADD INDEX `key` (company_id, `key`);');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `parameter` CHANGE COLUMN `name` `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_hungarian_ci DEFAULT NULL, DROP INDEX `gpn`, ADD INDEX `gpn` USING BTREE (company_id, group_id, position, `name`);');
        $this->addSql('ALTER TABLE `paramgroup` DROP COLUMN `key`, DROP INDEX `key`;');
    }
}
