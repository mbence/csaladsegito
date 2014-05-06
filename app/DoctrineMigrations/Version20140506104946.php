<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140506104946 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `parameter` VALUES (0, '1', 'lunch_types', '2', 'Normál A', '1'), (0, '1', 'lunch_types', '2', 'Normál B', '1'), (0, '1', 'lunch_types', '3', 'Diétás', '1'), (0, '1', 'lunch_types', '4', 'Gyomor', '1'), (0, '1', 'lunch_types', '5', 'Epe', '1'), (0, '1', 'lunch_types', '6', 'Reggeli', '1');");
    }

    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `parameter` WHERE company_id='1' AND group_id='lunch_types';");
    }
}
