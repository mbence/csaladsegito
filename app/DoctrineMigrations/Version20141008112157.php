<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20141008112157 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE `monthly_closing` ADD COLUMN `closingtype` smallint;");
        $this->addSql("ALTER TABLE `invoice` ADD COLUMN `invoicetype` smallint;");
        $this->addSql("ALTER TABLE `homehelpmonths_clients` ADD COLUMN `id` int NOT NULL AUTO_INCREMENT FIRST, CHANGE COLUMN `homehelpmonth_id` `homehelpmonth_id` int(11) NOT NULL AFTER `id`, CHANGE COLUMN `client_id` `client_id` int(11) NOT NULL AFTER `homehelpmonth_id`, ADD COLUMN `is_closed` tinyint(1) DEFAULT '0' AFTER `client_id`, DROP PRIMARY KEY, ADD PRIMARY KEY (`id`), ADD UNIQUE  (homehelpmonth_id, client_id);");

        // updates
        $this->addSql("UPDATE monthly_closing SET closingtype=1 WHERE summary LIKE 'Havi zárás%';");
        $this->addSql("UPDATE monthly_closing SET closingtype=2 WHERE summary LIKE 'Napi zárás%';");
        $this->addSql("UPDATE invoice SET invoicetype = 1 WHERE invoicetype IS NULL;");
    }

    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE `invoice` DROP COLUMN `invoicetype`");
        $this->addSql("ALTER TABLE `monthly_closing` DROP COLUMN `closingtype`");
        $this->addSql("ALTER TABLE `homehelpmonths_clients` DROP COLUMN `id`, DROP COLUMN `is_closed`, DROP PRIMARY KEY, ADD PRIMARY KEY (`homehelpmonth_id`, `client_id`);");
    }
}
