<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141003235123 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO `parameter` VALUES "
                . "('', '1', 'social_workers', '1', 'Baranyai Zsuzsanna', '1'), "
                . "('', '1', 'social_workers', '2', 'Bene Lászlóné', '1'), "
                . "('', '1', 'social_workers', '3', 'Besenci Sándorné', '1'), "
                . "('', '1', 'social_workers', '4', 'Bozsur Ágnes', '1'), "
                . "('', '1', 'social_workers', '5', 'Buri Erika', '1'), "
                . "('', '1', 'social_workers', '6', 'Csercsa Ivánné', '1'), "
                . "('', '1', 'social_workers', '7', 'Dávid Józsefné', '1'), "
                . "('', '1', 'social_workers', '8', 'Ganyi Gina', '1'), "
                . "('', '1', 'social_workers', '9', 'Gulyás Gyula', '1'), "
                . "('', '1', 'social_workers', '10', 'Horváth Beatrix', '1'), "
                . "('', '1', 'social_workers', '11', 'Istrován Frigyesné', '1'), "
                . "('', '1', 'social_workers', '12', 'Karacsosz Katalin', '1'), "
                . "('', '1', 'social_workers', '13', 'Kiss Gáborné', '1'), "
                . "('', '1', 'social_workers', '14', 'Kocsisán Petra', '1'), "
                . "('', '1', 'social_workers', '15', 'Kóczé Józsefné', '1'), "
                . "('', '1', 'social_workers', '16', 'Kökényné P. Ildikó', '1'), "
                . "('', '1', 'social_workers', '17', 'Kovácsné M. Ágnes', '1'), "
                . "('', '1', 'social_workers', '18', 'Lőrincz Klára', '1'), "
                . "('', '1', 'social_workers', '19', 'Lukácsy Árpádné', '1'), "
                . "('', '1', 'social_workers', '20', 'Mészáros Irén', '1'), "
                . "('', '1', 'social_workers', '21', 'Miklós Szilvia', '1'), "
                . "('', '1', 'social_workers', '22', 'Pap Csaba', '1'), "
                . "('', '1', 'social_workers', '23', 'Pécsi Viktória', '1'), "
                . "('', '1', 'social_workers', '24', 'Pordány Tiborné', '1'), "
                . "('', '1', 'social_workers', '25', 'Sándor Márta', '1'), "
                . "('', '1', 'social_workers', '26', 'Soltész Miklósné', '1'), "
                . "('', '1', 'social_workers', '27', 'Sosterics Lászlóné', '1'), "
                . "('', '1', 'social_workers', '28', 'Sugár Lászlóné', '1'), "
                . "('', '1', 'social_workers', '29', 'Szilágyi Judit', '1'), "
                . "('', '1', 'social_workers', '30', 'Szűk Tiborné', '1'), "
                . "('', '1', 'social_workers', '31', 'Tarnóczi Lászlóné', '1'), "
                . "('', '1', 'social_workers', '32', 'Zobolyák Andrea', '1');");

    }

    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM `parameter` WHERE company_id=1 AND group_id='social_workers';");
    }
}
