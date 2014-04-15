<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140415150322 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `lunch_order` (`id` int(11) NOT NULL AUTO_INCREMENT,`company_id` int(11) NOT NULL,`date` date DEFAULT NULL,`status` tinyint(4) DEFAULT NULL,`summary` text,`file` blob,`created_by` int(11) DEFAULT NULL,`created_at` datetime DEFAULT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
        $this->addSql('ALTER TABLE `paramgroup` DROP COLUMN `key`, DROP INDEX `key`;');
        $this->addSql("update parameter set group_id = null where group_id=0;");
        $this->addSql('ALTER TABLE `parameter` CHANGE COLUMN `group_id` `group_id` varchar(32) DEFAULT NULL;');
        $this->addSql("update parameter set group_id='inquiry' where group_id='1';");
        $this->addSql("update parameter set group_id='client_archives' where group_id='2';");
        $this->addSql("update parameter set group_id='client_reopens' where group_id='3';");
        $this->addSql("update parameter set group_id='problem_closings' where group_id='4';");
        $this->addSql("update parameter set group_id='citizenship' where group_id='5';");
        $this->addSql("update parameter set group_id='citizenship_status' where group_id='6';");
        $this->addSql("update parameter set group_id='events' where group_id='7';");
        $this->addSql("update parameter set group_id='signals' where group_id='8';");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE IF EXISTS `lunch_order`;');
        $this->addSql('ALTER TABLE `paramgroup` ADD COLUMN `key` varchar(32) AFTER `type`, ADD INDEX `key` (company_id, `key`);');
        $this->addSql("update parameter set group_id=1 where group_id='inquiry';");
        $this->addSql("update parameter set group_id=2 where group_id='client_archives';");
        $this->addSql("update parameter set group_id=3 where group_id='client_reopens';");
        $this->addSql("update parameter set group_id=4 where group_id='problem_closings';");
        $this->addSql("update parameter set group_id=5 where group_id='citizenship';");
        $this->addSql("update parameter set group_id=6 where group_id='citizenship_status';");
        $this->addSql("update parameter set group_id=7 where group_id='events';");
        $this->addSql("update parameter set group_id=8 where group_id='signals';");
        $this->addSql('ALTER TABLE `parameter` CHANGE COLUMN `group_id` `group_id` smallint(5) DEFAULT NULL;');
    }
}
