<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140508144834 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE `client_order` (`id` int(11) NOT NULL AUTO_INCREMENT,`company_id` int(11) NOT NULL,`client_id` int(11) NOT NULL,`date` date DEFAULT NULL,`orders` text,`changes` text,`status` tinyint(4) DEFAULT NULL,`is_current` tinyint(1) DEFAULT NULL,`created_at` datetime DEFAULT NULL,`created_by` int(11) DEFAULT NULL,PRIMARY KEY (`id`),KEY `company_id` (`company_id`,`date`,`status`,`is_current`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $this->addSql("ALTER TABLE `lunch_order`  RENAME TO `daily_order`;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `daily_order`  RENAME TO `lunch_order`;");
        $this->addSql("DROP TABLE IF EXISTS `client_order`;");
    }
}
