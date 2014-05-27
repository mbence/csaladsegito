<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140527110942 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `paramgroup` VALUES "
                . "('203', '1', '4', '1', '4', 'Iktatószám', '1', '0', '0'), "
                . "('204', '1', '4', '1', '5', 'Lakás tartozkodási jog', '1', '0', '0');");
        $this->addSql("DELETE FROM `parameter` WHERE group_id = 202;");
        $this->addSql("INSERT INTO `parameter` VALUES "
                . "('505', '1', '202', '1', 'Jövedelemmel nem rendelkezik', '1'), "
                . "('506', '1', '202', '2', 'Aktív korúak ellátása', '1'), "
                . "('507', '1', '202', '3', 'Álláskeresési támogatás', '1'), "
                . "('508', '1', '202', '4', 'Ápolási díj', '1'), "
                . "('509', '1', '202', '5', 'Bérpótló juttatás', '1'), "
                . "('510', '1', '202', '5', 'Foglalkoztatást helyettesítő támogatás', '1'), "
                . "('511', '1', '202', '5', 'Fogyatékossági támogatás', '1'), "
                . "('512', '1', '202', '5', 'Idős korúak járadéka', '1'), "
                . "('513', '1', '202', '5', 'Közhasznú foglalkoztatott', '1'), "
                . "('514', '1', '202', '5', 'Öregségi nyugdíj', '1'), "
                . "('515', '1', '202', '5', 'Rehabilitációs ellátás', '1'), "
                . "('516', '1', '202', '5', 'Rendszeres szociális segély', '1'), "
                . "('517', '1', '202', '5', 'Rokkant nyugdíj', '1'), "
                . "('518', '1', '202', '6', 'Egyéb', '1');");

    }

    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `paramgroup` WHERE id in (203,204);");
        $this->addSql("DELETE FROM `parameter` WHERE group_id = 202;");
        $this->addSql("INSERT INTO `parameter` VALUES "
                . "('505', '1', '202', '1', 'Nyugdíj', '1'), "
                . "('506', '1', '202', '2', 'Családi pótlék', '1'), "
                . "('507', '1', '202', '3', 'Családtámogatás', '1'), "
                . "('508', '1', '202', '4', 'Fogyatékosok támogatása', '1'), "
                . "('509', '1', '202', '5', 'Rendszeres szociális segély', '1'), "
                . "('510', '1', '202', '6', 'Rendelkezésreálló támogatás', '1');");

    }
}
