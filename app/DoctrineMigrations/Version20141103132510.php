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
    }

    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `parameter` WHERE company_id=1 AND group_id='club_events';");

    }
}
