<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140908141912 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE `history` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `company_id` int(11) NOT NULL,
            `user_id` int(11) DEFAULT NULL,
            `hash` varchar(255) COLLATE utf8_hungarian_ci DEFAULT NULL,
            `event` varchar(255) COLLATE utf8_hungarian_ci DEFAULT NULL,
            `data` text COLLATE utf8_hungarian_ci,
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `company_id` (`company_id`,`hash`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;");
    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE IF EXISTS `history`;');
    }
}
