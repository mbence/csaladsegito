<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140506123924 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `club` CHANGE COLUMN `foodtypes` `lunch_types` text DEFAULT NULL;");
        $this->addSql("INSERT INTO `club` VALUES (0, '1', 'Központ', '1089 Budapest, Orczy út 41.', null, null, '[]', '1'), (0, '1', 'Napraforgó Idősek Klubja', '1089 Budapest, Delej u. 34.', null, null, '[]', '1'), (0, '1', 'Őszikék Idősek Klubja', '1082 Budapest, Baross u. 109.', null, null, '[]', '1'), (0, '1', '\"Víg-Otthon\" Idősek Klubja', '1084 Budapest, Víg utca 18.', null, null, '[]', '1'), (0, '1', 'Reménysugár Idősek Klubja', '1084 Budapest Mátyás tér 4.', null, null, '[]', '1'), (0, '1', 'Mátyás Klub', '1084 Budapest Mátyás tér 12.', null, null, '[]', '1'), (0, '1', 'Ciklámen Idősek Klubja', '1081 Budapest, II. János Pál pápa tér 17.', null, null, '[]', '1'), (0, '1', 'Nappali Ellátás - ÉNO', '1082 Budapest, Kis Stáció u. 11. félem./1', null, null, '[]', '1');");

    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `club` CHANGE COLUMN `lunch_types` `foodtypes` text DEFAULT NULL;");
        $this->addSql("DELETE FROM `club` WHERE company_id='1';");
    }
}
