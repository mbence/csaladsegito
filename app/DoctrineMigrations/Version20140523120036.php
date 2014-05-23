<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140523120036 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `paramgroup` VALUES ('200', '1', '4', '1', '1', 'lakás komfort fokozat', '1', '0', '0'), ('201', '1', '4', '1', '3', 'nyugdíj törzsszám', '1', '0', '0'), ('202', '1', '4', '1', '2', 'jövedelemforrás', '1', '0', '0');");
        $this->addSql("INSERT INTO `parameter` VALUES "
                . "('500', '1', '200', '1', 'Komfort nélküli', '1'), "
                . "('501', '1', '200', '2', 'Félkomfortos', '1'), "
                . "('502', '1', '200', '3', 'Komfortos', '1'), "
                . "('503', '1', '200', '4', 'Összkomfortos', '1'), "
                . "('504', '1', '200', '5', 'Duplakomfortos', '1'), "
                . "('505', '1', '202', '1', 'Nyugdíj', '1'), "
                . "('506', '1', '202', '2', 'Családi pótlék', '1'), "
                . "('507', '1', '202', '3', 'Családtámogatás', '1'), "
                . "('508', '1', '202', '4', 'Fogyatékosok támogatása', '1'), "
                . "('509', '1', '202', '5', 'Rendszeres szociális segély', '1'), "
                . "('510', '1', '202', '6', 'Rendelkezésreálló támogatás', '1');");
    }

    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `paramgroup` WHERE id IN (200, 201, 202);");
        $this->addSql("DELETE FROM `parameter` WHERE id >= 500 AND id <= 510;");
    }
}
