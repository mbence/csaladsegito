<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141010184216 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `parameter` VALUES "
                . "('', '1', 'social_workers', '33', 'Németh Józsefné', '1'), "
                . "('', '1', 'social_workers', '34', 'Hontvári Anna', '1'), "
                . "('', '1', 'social_workers', '35', 'Barta Béláné', '1'), "
                . "('', '1', 'social_workers', '36', 'Steimetz Sándorné', '1'), "
                . "('', '1', 'social_workers', '37', 'Dali Zsolt', '1'), "
                . "('', '1', 'social_workers', '38', 'Sipos Károly', '1'), "
                . "('', '1', 'social_workers', '39', 'Böde Elvira', '1'), "
                . "('', '1', 'social_workers', '40', 'Gellén Magdolna', '1'), "
                . "('', '1', 'social_workers', '41', 'Fazekas Sarolta', '1'), "
                . "('', '1', 'social_workers', '42', 'Szálasi Magdolna', '1'), "
                . "('', '1', 'social_workers', '43', 'Fazekas Sára', '1'), "
                . "('', '1', 'social_workers', '44', 'Mohácsi Ilona', '1'), "
                . "('', '1', 'social_workers', '45', 'Mellen Attiláné', '1'), "
                . "('', '1', 'social_workers', '46', 'Hirsch Tiborné', '1'), "
                . "('', '1', 'social_workers', '47', 'Kerepesi Gondozóház', '1');");

    }

    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `parameter` WHERE company_id=1 AND group_id='social_workers' AND id>564;");
    }
}
