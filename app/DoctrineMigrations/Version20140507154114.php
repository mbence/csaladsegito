<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140507154114 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `csalad_master`.`catering` CHANGE COLUMN `club_id` `club_id` int(11), CHANGE COLUMN `discount` `discount` int DEFAULT NULL;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `csalad_master`.`catering` CHANGE COLUMN `club_id` `club_id` int(11) NOT NULL, CHANGE COLUMN `discount` `discount` tinyint DEFAULT NULL;");

    }
}
