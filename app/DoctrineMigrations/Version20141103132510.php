<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141103132510 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `parameter` VALUES ('', '1', 'club_events', '1', 'Ügyintézés', '1'), ('', '1', 'club_events', '2', 'Mentális tanácsadás', '1'), ('', '1', 'club_events', '3', 'Szabadidős program', '1'), ('', '1', 'club_events', '4', 'Egyéb', '1');");
        $this->addSql("ALTER TABLE `homehelp_month` DROP COLUMN `homehelptype`, DROP COLUMN `club_id`;");
        $this->addSql("CREATE TABLE `club_visit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `visit` tinyint(1),
  `events` varchar(100),
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`client_id`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

    }

    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `parameter` WHERE company_id=1 AND group_id='club_events';");
        $this->addSql("ALTER TABLE `homehelp_month` ADD COLUMN `homehelptype` smallint, ADD COLUMN `club_id` int;");
        $this->addSql("DROP TABLE IF EXISTS `club_visit`;");
    }
}
