<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140422153512 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `company` CHANGE COLUMN `sequence_policy` `sequence_policy` varchar(64) DEFAULT NULL, CHANGE COLUMN `case_number_template` `case_number_template` text CHARACTER SET utf8 COLLATE utf8_hungarian_ci DEFAULT NULL;');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `company` CHANGE COLUMN `sequence_policy` `sequence_policy` tinyint(4) DEFAULT NULL, CHANGE COLUMN `case_number_template` `case_number_template` varchar(64) CHARACTER SET utf8 COLLATE utf8_hungarian_ci DEFAULT NULL;');
    }
}
