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
        $this->addSql("ALTER TABLE `csalad_master`.`catering` CHANGE COLUMN `club_id` `club_id` int(11);");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `csalad_master`.`catering` CHANGE COLUMN `club_id` `club_id` int(11) NOT NULL;");

    }
}
