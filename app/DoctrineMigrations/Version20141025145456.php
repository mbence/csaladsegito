<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use JCSGYK\AdminBundle\Entity\HomeHelp;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141025145456 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `club` ADD COLUMN `homehelptype` smallint;");
//        $this->addSql("ALTER TABLE `homehelp_month` ADD COLUMN `homehelptype` smallint, ADD COLUMN `club_id` int;");
        $this->addSql("UPDATE `club` SET `homehelptype` = " . HomeHelp::VISIT . " WHERE id != 1;");
        $this->addSql("UPDATE `club` SET `homehelptype` = " . HomeHelp::HELP . "  WHERE id = 1;");
//        $this->addSql("UPDATE `homehelp_month` SET `homehelptype` = " . HomeHelp::HELP);
    }

    public function down(Schema $schema)
    {
//        $this->addSql("ALTER TABLE `homehelp_month` DROP COLUMN `homehelptype`, DROP COLUMN `club_id`;");
        $this->addSql("ALTER TABLE `club` DROP COLUMN `homehelptype`;");
    }
}
