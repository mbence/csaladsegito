/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50529
 Source Host           : localhost
 Source Database       : jgyk

 Target Server Type    : MySQL
 Target Server Version : 50529
 File Encoding         : utf-8

 Date: 08/06/2013 09:30:38 AM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `address`
-- ----------------------------
DROP TABLE IF EXISTS `address`;
CREATE TABLE `address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `country` varchar(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zip_code` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street_type` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street_number` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flat_number` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  `modified_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id2` (`id`,`client_id`),
  KEY `ssc` (`client_id`),
  FULLTEXT KEY `search` (`street`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
--  Table structure for `admin_user`
-- ----------------------------
DROP TABLE IF EXISTS `admin_user`;
CREATE TABLE `admin_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `firstname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `username_canonical` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_canonical` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL,
  `salt` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `locked` tinyint(1) NOT NULL,
  `expired` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `confirmation_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password_requested_at` datetime DEFAULT NULL,
  `roles` longtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:array)',
  `credentials_expired` tinyint(1) NOT NULL,
  `credentials_expire_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_AD8A54A992FC23A8` (`username_canonical`) USING BTREE,
  KEY `UNIQ_AD8A54A9A0D96FBF` (`email_canonical`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- ----------------------------
--  Records of `admin_user`
-- ----------------------------
BEGIN;
INSERT INTO `admin_user` VALUES ('1', '1', 'Bence', 'Mészáros', 'bence', 'bence', 'mxbence@gmail.com', 'mxbence@gmail.com', '1', '8monh30zxj404wggc48gsg840sk088k', 'tknMFBTjss49Q4QeXpslTbKJptBGMGknSRsCfCCsrUqtB8PTk1BviF1J5qehhHwlvCJ5JEtQYAA3qGurfv4ylw==', '2013-06-24 11:50:47', '0', '0', null, null, null, 'a:2:{i:0;s:16:\"ROLE_SUPER_ADMIN\";i:1;s:15:\"ROLE_ASSISTANCE\";}', '0', null);
COMMIT;

-- ----------------------------
--  Table structure for `archive`
-- ----------------------------
DROP TABLE IF EXISTS `archive`;
CREATE TABLE `archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `type` smallint(5) unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8_hungarian_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci ROW_FORMAT=COMPACT;

-- ----------------------------
--  Table structure for `client`
-- ----------------------------
DROP TABLE IF EXISTS `client`;
CREATE TABLE `client` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL,
  `case_year` smallint(6) DEFAULT NULL,
  `case_number` smallint(5) unsigned DEFAULT NULL,
  `case_label` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `firstname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lastname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `gender` tinyint(2) unsigned DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `birth_place` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birth_title` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birth_firstname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `birth_lastname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mother_title` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mother_firstname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mother_lastname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `social_security_number` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `identity_number` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id_card_number` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mobile` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fax` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `zip_code` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street_type` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `street_number` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `flat_number` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_country` varchar(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_zip_code` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_city` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_street` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_street_type` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_street_number` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `location_flat_number` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `citizenship_status` smallint(5) unsigned DEFAULT NULL,
  `citizenship` smallint(5) unsigned DEFAULT NULL,
  `note` text COLLATE utf8_unicode_ci,
  `case_admin` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  `modified_by` int(11) unsigned DEFAULT NULL,
  `opened_by` int(11) unsigned DEFAULT NULL,
  `doc_file` text COLLATE utf8_unicode_ci,
  `guardian_firstname` varchar(255) COLLATE utf8_unicode_ci DEFAULT '2',
  `guardian_lastname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT '0',
  `agreement_expires_at` date DEFAULT NULL,
  `parameters` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `id2` (`id`,`company_id`,`type`),
  KEY `ssc` (`social_security_number`,`company_id`,`type`),
  FULLTEXT KEY `search` (`firstname`,`lastname`,`street`,`case_label`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
--  Table structure for `client_sequence`
-- ----------------------------
DROP TABLE IF EXISTS `client_sequence`;
CREATE TABLE `client_sequence` (
  `id` int(11) NOT NULL DEFAULT '0',
  `company_id` int(11) NOT NULL DEFAULT '0',
  `year` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`id`,`company_id`),
  UNIQUE KEY `company_id` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- ----------------------------
--  Records of `client_sequence`
-- ----------------------------
BEGIN;
INSERT INTO `client_sequence` VALUES ('1', '1', null);
COMMIT;

-- ----------------------------
--  Table structure for `company`
-- ----------------------------
DROP TABLE IF EXISTS `company`;
CREATE TABLE `company` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `short_name` varchar(16) COLLATE utf8_hungarian_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_hungarian_ci DEFAULT NULL,
  `host` varchar(255) COLLATE utf8_hungarian_ci DEFAULT NULL,
  `types` varchar(16) COLLATE utf8_hungarian_ci DEFAULT NULL,
  `sequence_policy` tinyint(4) DEFAULT NULL,
  `case_number_template` varchar(64) COLLATE utf8_hungarian_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci ROW_FORMAT=COMPACT;

-- ----------------------------
--  Records of `company`
-- ----------------------------
BEGIN;
INSERT INTO `company` VALUES ('1', 'JCSK', 'Józsefvárosi Családsegítő Központ', 'cs.local,jcssz.dyndns.org', '1', '0', 'Ü-{num,5}', '1');
COMMIT;

-- ----------------------------
--  Table structure for `debt`
-- ----------------------------
DROP TABLE IF EXISTS `debt`;
CREATE TABLE `debt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `problem_id` int(11) DEFAULT NULL,
  `utilityprovider_id` int(11) unsigned DEFAULT NULL,
  `registered_debt` decimal(9,0) DEFAULT NULL,
  `managed_debt` decimal(9,0) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `problem_id` (`problem_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci ROW_FORMAT=COMPACT;

-- ----------------------------
--  Table structure for `event`
-- ----------------------------
DROP TABLE IF EXISTS `event`;
CREATE TABLE `event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `problem_id` int(11) NOT NULL,
  `description` text COLLATE utf8_hungarian_ci,
  `type` smallint(6) DEFAULT NULL,
  `parameters` text COLLATE utf8_hungarian_ci,
  `created_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `client_visit` tinyint(1) DEFAULT NULL,
  `client_cancel` tinyint(1) DEFAULT NULL,
  `attachment` varchar(255) COLLATE utf8_hungarian_ci DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `problem_id` (`problem_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci ROW_FORMAT=COMPACT;

-- ----------------------------
--  Table structure for `parameter`
-- ----------------------------
DROP TABLE IF EXISTS `parameter`;
CREATE TABLE `parameter` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT '1',
  `group_id` smallint(5) unsigned DEFAULT NULL,
  `position` smallint(5) unsigned DEFAULT NULL,
  `name` varchar(100) COLLATE utf8_hungarian_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `gpn` (`company_id`,`group_id`,`position`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci ROW_FORMAT=COMPACT;

-- ----------------------------
--  Records of `parameter`
-- ----------------------------
BEGIN;
INSERT INTO `parameter` VALUES ('1', '1', '1', '1', 'Tájékoztatás', '1'), ('2', '1', '1', '2', 'Továbbirányítás', '1'), ('8', '1', '101', '1', 'Tanköteles kornál fiatalabb', '1'), ('9', '1', '101', '2', 'Általános iskola 8 osztályánál kevesebb', '1'), ('10', '1', '101', '3', 'Általános iskola 8 osztálya', '1'), ('11', '1', '101', '4', 'Általános iskola 10 osztálya', '1'), ('12', '1', '101', '5', 'Befejezett szakmunkásképző, szakiskola', '1'), ('13', '1', '101', '6', 'Befejezett szakközépiskola', '1'), ('14', '1', '101', '7', 'Befejezett gimnázium', '1'), ('15', '1', '101', '8', 'Felsőfokú iskola', '1'), ('16', '1', '102', '1', 'Aktív kereső (szerződéssel)', '1'), ('17', '1', '102', '2', 'Aktív kereső - rendsz. nem bejelentett', '1'), ('18', '1', '102', '3', 'Aktív kereső - alkalmi munka', '1'), ('19', '1', '102', '4', 'Inaktív - nyugdíjas', '1'), ('20', '1', '102', '5', 'Inaktív - rokkantnyugdíjas', '1'), ('21', '1', '102', '6', 'Inaktív - GYES, GYED, GYET', '1'), ('22', '1', '102', '7', 'Inaktív - szociális járadék', '1'), ('23', '1', '102', '8', 'Inaktív - ápolási díj', '1'), ('24', '1', '102', '9', 'Munkanélküli - regisztrált, ellátással', '1'), ('25', '1', '102', '10', 'Munkanélküli - regisztrált, ellátás nélkül', '1'), ('26', '1', '102', '11', 'Munkanélküli - nem regisztrált', '1'), ('27', '1', '102', '12', 'Munkanélküli - rendszeres szoc. segély', '1'), ('28', '1', '102', '13', 'Eltartott - gyermek/fiatalkorú (0-17 év)', '1'), ('29', '1', '102', '14', 'Eltartott - felnőtt korú', '1'), ('30', '1', '102', '15', 'Egyéb', '1'), ('31', '1', '103', '1', 'Házast. (élettársi) kapcs. - gyermek(ek)kel', '1'), ('32', '1', '103', '2', 'Házast. (élettársi) kapcs. - gyermek nélkül', '1'), ('33', '1', '103', '3', 'Egy szülő gyermek(ek)kel', '1'), ('34', '1', '103', '4', 'Egyedül élő', '1'), ('35', '1', '103', '5', 'Egyéb', '1'), ('36', '1', '105', '1', 'Életviteli', '1'), ('37', '1', '105', '2', 'Családi-kapcsolati', '1'), ('38', '1', '105', '3', 'Családon belüli bántalmazás', '1'), ('39', '1', '105', '4', 'Lelki - mentális', '1'), ('40', '1', '105', '5', 'Gyermeknevelési', '1'), ('41', '1', '105', '6', 'Anyagi', '1'), ('42', '1', '105', '7', 'Foglalkozással kapcsolatos', '1'), ('43', '1', '105', '8', 'Egészségkárosodás következménye', '1'), ('44', '1', '105', '9', 'Ügyintézési nehézség', '1'), ('45', '1', '105', '10', 'Információhiány', '1'), ('46', '1', '105', '11', 'Krízishelyzet', '1'), ('47', '1', '105', '12', 'Egyéb', '1'), ('48', '1', '105', '13', 'Népkonyha', '1'), ('50', '1', '7', '1', 'Családlátogatás', '1'), ('51', '1', '7', '2', 'Személyes megkeresés', '1'), ('52', '1', '7', '3', 'Telefonos megkeresés', '1'), ('53', '1', '7', '4', 'Egyéb', '1'), ('54', '1', '106', '1', 'Szoc.mentális esetk.: információ', '1'), ('55', '1', '106', '2', 'Szoc.mentális esetk.: ügyintézés', '1'), ('56', '1', '106', '3', 'Szoc.mentális esetk.: segítő beszélgetés', '1'), ('57', '1', '106', '4', 'Szoc.mentális esetk.: tanácsadás', '1'), ('58', '1', '106', '5', 'Szoc.mentális esetk.: továbbirányítás', '1'), ('59', '1', '106', '6', 'Szoc.mentális esetk.: egyéb', '1'), ('60', '1', '106', '7', 'Pszichológiai tanácsadás', '1'), ('61', '1', '106', '8', 'Pszichológiai konzultáció', '1'), ('62', '1', '106', '9', 'Pszichológiai terápia', '1'), ('63', '1', '106', '10', 'Jogi - tanácsadás', '1'), ('64', '1', '106', '11', 'Jogi - iratszerkesztés', '1'), ('65', '1', '107', '1', 'Népkonyha', '1'), ('66', '1', '107', '2', 'Vöröskereszt', '1'), ('67', '1', '107', '3', 'Szigony Alapítvány', '1'), ('68', '1', '107', '4', 'Ébredések Alapítvány', '1'), ('69', '1', '107', '5', 'Félúton Alapítvány', '1'), ('70', '1', '107', '6', 'SzemPont', '1'), ('71', '1', '107', '7', 'Munkaügyi Központ', '1'), ('72', '1', '107', '8', 'Csoport', '1'), ('73', '1', '107', '9', 'Gyermekjóléti Szolgálat', '1'), ('74', '1', '107', '10', 'Őszirózsa Gondozószolgálat', '1'), ('75', '1', '108', '1', 'Szolg. közvetítése és nyújtása', '1'), ('76', '1', '108', '2', 'Dologi javak közv. és nyújtása', '1'), ('77', '1', '108', '3', 'Adósságkezelési tanácsadói szolgáltatás', '1'), ('78', '1', '108', '4', 'Aktív korúak szociális segély. beill. program', '1'), ('79', '1', '108', '5', 'Ifjusági inf. és tanácsadói szolgáltatás', '1'), ('80', '1', '108', '6', 'Mediáció és konfliktuskezelés', '1'), ('81', '1', '2', '1', 'Lezárás - Elköltözött', '1'), ('82', '1', '2', '2', 'Lezárás - Elhunyt', '1'), ('83', '1', '2', '3', 'Lezárás - Átadva más intézménynek', '1'), ('84', '1', '2', '4', 'Lezárás - Elhelyezkedett', '1'), ('85', '1', '2', '5', 'Lezárás - Egyszeri megkeresés óta nem jelentkezett', '1'), ('86', '1', '2', '6', 'Lezárás - Utolsó megkeresés óta eltelt 1 év', '1'), ('87', '1', '2', '7', 'Lezárás - ACST óta eltelt 1 év', '1'), ('88', '1', '2', '8', 'Lezárás - Lakásbérlet - önrész hiánya', '1'), ('89', '1', '2', '9', 'Lezárás - Adósságát rendezte', '1'), ('90', '1', '2', '10', 'Lezárás - Egyéb', '1'), ('91', '1', '3', '1', 'Újranyitás - Újból jelentkezett', '1'), ('92', '1', '1', '3', 'Érdeklődés', '0'), ('93', '1', '4', '1', 'Elköltözött', '1'), ('94', '0', '7', '5', 'Nyomtatvány generálás', '0'), ('95', '0', '7', '6', 'Megállapodás kezdete', '0'), ('96', '0', '7', '7', 'Megállapodás vége', '0'), ('97', '0', '5', '1', 'magyar', '1'), ('98', '0', '5', '2', 'hontalan', '1'), ('99', '0', '5', '3', 'ismeretlen', '1'), ('100', '0', '6', '1', 'Magyarországon élő magyar', '1'), ('101', '0', '6', '2', 'Bevándorolt (külföldi)', '1'), ('102', '0', '6', '3', 'Letelepedett', '1'), ('103', '0', '6', '4', 'Menekült (külföldi)', '1'), ('104', '0', '6', '5', 'Oltalmazott (külföldi)', '1'), ('105', '0', '6', '6', 'EGT tartózkodási engedélyes', '1'), ('106', '0', '6', '7', 'Szabad mozgás és tartózkodás jogával rendelkező', '1'), ('107', '0', '5', '4', 'afgán', '1'), ('108', '2', '7', '4', 'Családlátogatás', '1'), ('109', '2', '7', '5', 'Személyes megkeresés', '1'), ('110', '2', '7', '6', 'Telefonos megkeresés', '1'), ('111', '2', '7', '7', 'Egyéb', '1'), ('112', '2', '109', '1', 'Tanácsadás', '1'), ('113', '2', '109', '2', 'Átmeneti nevelt', '1'), ('114', '2', '109', '3', 'Védelembe vett', '1'), ('115', '2', '109', '4', 'Alapellátott', '1');
COMMIT;

-- ----------------------------
--  Table structure for `paramgroup`
-- ----------------------------
DROP TABLE IF EXISTS `paramgroup`;
CREATE TABLE `paramgroup` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL COMMENT '0-system, 1-client, 2-problem, 3-event',
  `position` smallint(5) unsigned DEFAULT NULL,
  `name` varchar(32) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gpn` (`company_id`,`type`,`position`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- ----------------------------
--  Records of `paramgroup`
-- ----------------------------
BEGIN;
INSERT INTO `paramgroup` VALUES ('1', '0', '0', '1', 'Érdeklődés típusok', '1'), ('2', '0', '0', '4', 'Ügyfél archiválások', '1'), ('3', '0', '0', '5', 'Ügyfél újranyitás', '1'), ('4', '0', '0', '6', 'Probléma lezárások', '1'), ('5', '0', '0', '2', 'Állampolgárság', '1'), ('6', '0', '0', '3', 'Állampolgársági jogállás', '1'), ('7', '0', '0', '7', 'Esemény', '1'), ('101', '1', '1', '1', 'Végzettség', '1'), ('102', '1', '1', '2', 'Gazdasági aktivitás', '1'), ('103', '1', '1', '3', 'Családi összetétel', '1'), ('104', '1', '1', '4', 'Igénylők (fő)', '1'), ('105', '1', '2', '1', 'Jelleg', '1'), ('106', '1', '3', '2', 'Esetkezelés jellege', '1'), ('107', '1', '3', '3', 'Továbbirányítás', '1'), ('108', '1', '3', '4', 'Egyéb tevékenység', '1'), ('109', '2', '1', '1', 'Gondozási tevékenység', '1'), ('110', '2', '1', '2', 'Törzsszám', '1');
COMMIT;

-- ----------------------------
--  Table structure for `problem`
-- ----------------------------
DROP TABLE IF EXISTS `problem`;
CREATE TABLE `problem` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_hungarian_ci DEFAULT NULL,
  `description` text COLLATE utf8_hungarian_ci,
  `parameters` text COLLATE utf8_hungarian_ci,
  `is_active` tinyint(1) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `close_code` int(11) DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `opened_by` int(11) DEFAULT NULL,
  `attachment` varchar(255) COLLATE utf8_hungarian_ci DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT '0',
  `has_agreement` tinyint(1) DEFAULT '0',
  `agreement_expires_at` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci ROW_FORMAT=COMPACT;

-- ----------------------------
--  Table structure for `relation`
-- ----------------------------
DROP TABLE IF EXISTS `relation`;
CREATE TABLE `relation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `type` smallint(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `child_id` (`child_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- ----------------------------
--  Table structure for `stat`
-- ----------------------------
DROP TABLE IF EXISTS `stat`;
CREATE TABLE `stat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` smallint(5) unsigned NOT NULL,
  `event` mediumint(9) NOT NULL,
  `counter` int(11) DEFAULT NULL,
  `created_at` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`created_at`,`type`,`event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;

-- ----------------------------
--  Table structure for `task`
-- ----------------------------
DROP TABLE IF EXISTS `task`;
CREATE TABLE `task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assigned_to` int(11) DEFAULT NULL,
  `type` smallint(6) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `problem_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_user` (`id`,`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- ----------------------------
--  Table structure for `template`
-- ----------------------------
DROP TABLE IF EXISTS `template`;
CREATE TABLE `template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(64) DEFAULT NULL,
  `original_name` varchar(64) DEFAULT NULL,
  `mime_type` varchar(32) DEFAULT NULL,
  `path` varchar(64) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `modified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- ----------------------------
--  Records of `template`
-- ----------------------------
BEGIN;
INSERT INTO `template` VALUES ('1', '1', 'ACST kérelem', 'acst_kerelem.docx', 'application/vnd.openxmlformats-o', 'a16532eb850f30687284d0b76644dfa2a09d69f7.docx', '1', '2013-04-11 17:57:20'), ('2', '1', 'Esettörténet', 'esettortenet.docx', 'application/vnd.openxmlformats-o', 'd87b4d93a3980dc6cf4e16cf713c5fdf14f4d7ed.docx', '1', '2013-04-11 17:53:54'), ('3', '1', 'Főgáz igazolás', 'fogaz_igazolas.docx', 'application/vnd.openxmlformats-o', '1559addb2a07ab72b4f7982bc505bb4f65c67bd6.docx', '1', '2013-04-11 17:54:01'), ('4', '1', 'Adósságkezelési megállapodás', 'adossagkezelesi_megallapodas.docx', 'application/vnd.openxmlformats-o', '077488ce94579dda878b533de658228d867bd930.docx', '1', '2013-04-11 17:52:16'), ('5', '1', 'Elmü igazolás', 'elmu_igazolas.docx', 'application/vnd.openxmlformats-o', '4e34d903a7d820ad5ddccb9ea6956d02271a4ae3.docx', '1', '2013-04-11 17:53:11'), ('6', '1', 'Főtáv igazolás', 'fotav_dhk_igazolas.docx', 'application/vnd.openxmlformats-o', '8f5c57265c800e38ea8fed75ae062b47f5b13e5c.docx', '1', '2013-04-11 17:54:11'), ('7', '1', 'Javaslat', 'javaslat.docx', 'application/vnd.openxmlformats-o', '196381da41492c3fd7be3abed67e1d00be0fb40b.docx', '1', '2013-04-11 17:54:18'), ('8', '1', 'JVK halasztás', 'jvk_halasztas.docx', 'application/vnd.openxmlformats-o', '4c32124c2e3da533b9ce695a47d5ff2d71aebb83.docx', '1', '2013-04-19 17:16:48'), ('9', '1', 'Társasház igazolás', 'tarsashaz_igazolas.docx', 'application/vnd.openxmlformats-o', '480f3cc483685774e21ddcba3bc71459fccc4a51.docx', '1', '2013-04-11 17:54:31'), ('10', '1', 'Együttműködési megállapodás', 'egyuttmukodesi_megallapodas.docx', 'application/vnd.openxmlformats-o', 'e621b7827186d1af0e7dcfbd999f051dc0f76fe2.docx', '1', '2013-04-11 17:53:06'), ('11', '1', 'Lezárás', null, null, null, '1', '2013-04-16 20:52:26');
COMMIT;

-- ----------------------------
--  Table structure for `utilityprovider`
-- ----------------------------
DROP TABLE IF EXISTS `utilityprovider`;
CREATE TABLE `utilityprovider` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(32) DEFAULT NULL,
  `template_key` varchar(32) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

-- ----------------------------
--  Records of `utilityprovider`
-- ----------------------------
BEGIN;
INSERT INTO `utilityprovider` VALUES ('1', '1', 'Elmü', 'elmu', '1'), ('2', '1', 'Főgáz', 'fogaz', '1'), ('3', '1', 'Főtáv', 'fotav', '1'), ('4', '1', 'JVK', 'jvk', '1'), ('5', '1', 'Társasház', 'tarsashaz', '1'), ('6', '1', 'Díjbeszedő', 'dijbeszedo', '1');
COMMIT;

-- ----------------------------
--  Table structure for `utilityprovider_clientnumber`
-- ----------------------------
DROP TABLE IF EXISTS `utilityprovider_clientnumber`;
CREATE TABLE `utilityprovider_clientnumber` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `utilityprovider_id` int(11) NOT NULL,
  `value` varchar(60) COLLATE utf8_hungarian_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `person_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci ROW_FORMAT=COMPACT;

SET FOREIGN_KEY_CHECKS = 1;
