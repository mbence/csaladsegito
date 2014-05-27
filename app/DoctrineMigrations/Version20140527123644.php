<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140527123644 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `parameter` VALUES "
                . "('519', '1', '204', '1', 'Haszonélvező', '1'), "
                . "('520', '1', '204', '2', 'Családtag', '1'), "
                . "('521', '1', '204', '3', 'Hajléktalan', '1'), "
                . "('522', '1', '204', '4', 'Bérlő', '1'), "
                . "('523', '1', '204', '5', 'Tulajdonos', '1');");
        $this->addSql("DELETE FROM `parameter` WHERE group_id = 202;");
        $this->addSql("INSERT INTO `parameter` VALUES "
                . "('505', '1', '202', '1', 'Jövedelemmel nem rendelkezik', '1'), "
                . "('506', '1', '202', '2', 'Aktív korúak ellátása', '1'), "
                . "('507', '1', '202', '3', 'Álláskeresési támogatás', '1'), "
                . "('508', '1', '202', '4', 'Ápolási díj', '1'), "
                . "('509', '1', '202', '5', 'Bérpótló juttatás', '1'), "
                . "('510', '1', '202', '6', 'Foglalkoztatást helyettesítő támogatás', '1'), "
                . "('511', '1', '202', '7', 'Fogyatékossági támogatás', '1'), "
                . "('512', '1', '202', '8', 'Idős korúak járadéka', '1'), "
                . "('513', '1', '202', '9', 'Közhasznú foglalkoztatott', '1'), "
                . "('514', '1', '202', '10', 'Öregségi nyugdíj', '1'), "
                . "('515', '1', '202', '11', 'Rehabilitációs ellátás', '1'), "
                . "('516', '1', '202', '12', 'Rendszeres szociális segély', '1'), "
                . "('517', '1', '202', '13', 'Rokkant nyugdíj', '1'), "
                . "('518', '1', '202', '14', 'Egyéb', '1');");
    }

    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `parameter` WHERE id >= 519 AND id <= 523;");

    }
}
