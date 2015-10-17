<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151017111632 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `catering` ADD COLUMN `delivery` smallint;");
        $this->addSql("INSERT INTO parameter (id, company_id, group_id, position, name, is_active) VALUES (593, 1, 'delivery', 1, 'Helyben fogyasztja', 1);");
        $this->addSql("INSERT INTO parameter (id, company_id, group_id, position, name, is_active) VALUES (594, 1, 'delivery', 2, 'Személyes átvétel', 1);");
        $this->addSql("INSERT INTO parameter (id, company_id, group_id, position, name, is_active) VALUES (595, 1, 'delivery', 3, 'Házhoz szállítás', 1);");
        $this->addSql("UPDATE `catering` SET `delivery`=595 WHERE `club_id`=1");
        $this->addSql("UPDATE `catering` SET `delivery`=593 WHERE `club_id`!=1");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `catering` DROP COLUMN `delivery`;");
        $this->addSql("DELETE FROM parameter WHERE group_id='delivery';");
    }
}
