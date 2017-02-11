--
-- Database: `bf2stats`
-- Version: 3.0
--

-- --------------------------------------------------------
-- Delete Tables/Views/Triggers First
-- --------------------------------------------------------

DROP TRIGGER IF EXISTS `server_update`;
DROP TRIGGER IF EXISTS `player_joined`;
DROP TRIGGER IF EXISTS `_version_inserttime`;
DROP TRIGGER IF EXISTS `player_award_timestamps`;
DROP VIEW IF EXISTS `player_weapon_view`;
DROP PROCEDURE IF EXISTS `create_player`;
DROP TABLE IF EXISTS `ip2nationcountries`;
DROP TABLE IF EXISTS `ip2nation`;
DROP TABLE IF EXISTS `player_weapon`;
DROP TABLE IF EXISTS `player_unlock`;
DROP TABLE IF EXISTS `player_vehicle`;
DROP TABLE IF EXISTS `player_history`;
DROP TABLE IF EXISTS `player_map`;
DROP TABLE IF EXISTS `player_kill`;
DROP TABLE IF EXISTS `player_kit`;
DROP TABLE IF EXISTS `player_army`;
DROP TABLE IF EXISTS `player_award`;
DROP TABLE IF EXISTS `player`;
DROP TABLE IF EXISTS `unlock`;
DROP TABLE IF EXISTS `round_history`;
DROP TABLE IF EXISTS `server`;
DROP TABLE IF EXISTS `mapinfo`;
DROP TABLE IF EXISTS `army`;
DROP TABLE IF EXISTS `_version`;

-- --------------------------------------------------------
-- Non-Player Tables First
-- --------------------------------------------------------

--
-- Table structure for table `_version`
--

CREATE TABLE `_version` (
  `updateid` INT UNSIGNED,
  `version` VARCHAR(10) NOT NULL,
  `time` INT DEFAULT 0,
  PRIMARY KEY(`updateid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TRIGGER `_version_inserttime` BEFORE INSERT ON `_version`
FOR EACH ROW SET new.time = UNIX_TIMESTAMP();

--
-- Table structure for table `army`
--

CREATE TABLE `army` (
  `id` TINYINT UNSIGNED,
  `name` VARCHAR(32) NOT NULL,
  `desc` VARCHAR(32) DEFAULT NULL,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `mapinfo`
--

CREATE TABLE `mapinfo` (
  `id` SMALLINT UNSIGNED,
  `name` VARCHAR(50) UNIQUE NOT NULL,
  `score` INT UNSIGNED NOT NULL DEFAULT 0,
  `time` INT UNSIGNED NOT NULL DEFAULT 0,
  `times` INT UNSIGNED NOT NULL DEFAULT 0,
  `kills` INT UNSIGNED NOT NULL DEFAULT 0,
  `deaths` INT UNSIGNED NOT NULL DEFAULT 0,
  `custom` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `server`
--

CREATE TABLE `server` (
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(46) NOT NULL DEFAULT '',
  `prefix` VARCHAR(30) NOT NULL DEFAULT '',
  `name` VARCHAR(100) DEFAULT NULL,
  `port` SMALLINT UNSIGNED DEFAULT 0,
  `queryport` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `banned` TINYINT(1) NOT NULL DEFAULT 0,
  `lastupdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(`id`),
  UNIQUE KEY `ip-prefix-unq` (`ip`,`prefix`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TRIGGER `server_update` BEFORE UPDATE ON `server` FOR EACH ROW SET new.lastupdate = CURRENT_TIMESTAMP;

--
-- Table structure for table `round_history`
--

CREATE TABLE `round_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `mapid` SMALLINT UNSIGNED NOT NULL,
  `serverid` SMALLINT UNSIGNED NOT NULL,
  `round_start` INT UNSIGNED NOT NULL,
  `round_end` INT UNSIGNED NOT NULL,
  `gamemode` TINYINT UNSIGNED NOT NULL,
  `mod` VARCHAR(20) NOT NULL,
  `winner` TINYINT NOT NULL,
  `team1` TINYINT UNSIGNED NOT NULL,
  `team2` TINYINT UNSIGNED NOT NULL,
  `tickets1` SMALLINT UNSIGNED NOT NULL,
  `tickets2` SMALLINT UNSIGNED NOT NULL,
  `pids1` SMALLINT UNSIGNED NOT NULL,
  `pids1_end` SMALLINT UNSIGNED NOT NULL,
  `pids2` SMALLINT UNSIGNED NOT NULL,
  `pids2_end` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY(`id`),
  FOREIGN KEY(`mapid`) REFERENCES mapinfo(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY(`serverid`) REFERENCES server(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `unlock`
--

CREATE TABLE `unlock` (
  `id` SMALLINT PRIMARY KEY,
  `kit` TINYINT NOT NULL,
  `name` VARCHAR(32) NOT NULL,
  `desc` VARCHAR(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- Stats: Player Tables
-- --------------------------------------------------------
-- On composite key tables, Always place the player ID first!
--
-- Indexes on fields such as (a,b,c); the records are sorted first
-- on a, then b, then c. Most searches are by player ID, therefor
-- the player ID should come first in the index (includes primary
-- keys).
-- --------------------------------------------------------


--
-- Table structure for table `player`
--

CREATE TABLE `player` (
  `id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(25) UNIQUE NOT NULL,
  `password` VARCHAR(32) NOT NULL,
  `country` CHAR(2) NOT NULL DEFAULT 'xx',
  `lastip` VARCHAR(15) NOT NULL DEFAULT '',
  `joined` INT UNSIGNED NOT NULL DEFAULT 0,
  `lastonline` INT UNSIGNED NOT NULL DEFAULT 0,
  `time` INT UNSIGNED NOT NULL DEFAULT 0,
  `rounds` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `rank` TINYINT(2) UNSIGNED NOT NULL DEFAULT 0,
  `score` INT UNSIGNED NOT NULL DEFAULT 0,
  `cmdscore` INT UNSIGNED NOT NULL DEFAULT 0,
  `skillscore` INT UNSIGNED NOT NULL DEFAULT 0,
  `teamscore` INT UNSIGNED NOT NULL DEFAULT 0,
  `kills` INT UNSIGNED NOT NULL DEFAULT 0,
  `deaths` INT UNSIGNED NOT NULL DEFAULT 0,
  `captures` INT UNSIGNED NOT NULL DEFAULT 0,
  `neutralizes` INT UNSIGNED NOT NULL DEFAULT 0,
  `captureassists` INT UNSIGNED NOT NULL DEFAULT 0,
  `neutralizeassists` INT UNSIGNED NOT NULL DEFAULT 0,
  `defends` INT UNSIGNED NOT NULL DEFAULT 0,
  `damageassists` INT UNSIGNED NOT NULL DEFAULT 0,
  `heals` INT UNSIGNED NOT NULL DEFAULT 0,
  `revives` INT UNSIGNED NOT NULL DEFAULT 0,
  `ammos` INT UNSIGNED NOT NULL DEFAULT 0,
  `repairs` INT UNSIGNED NOT NULL DEFAULT 0,
  `targetassists` INT UNSIGNED NOT NULL DEFAULT 0,
  `driverspecials` INT UNSIGNED NOT NULL DEFAULT 0,
  `driverassists` INT UNSIGNED NOT NULL DEFAULT 0,
  `passengerassists` INT UNSIGNED NOT NULL DEFAULT 0,
  `teamkills` INT UNSIGNED NOT NULL DEFAULT 0,
  `teamdamage` INT UNSIGNED NOT NULL DEFAULT 0,
  `teamvehicledamage` INT UNSIGNED NOT NULL DEFAULT 0,
  `suicides` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `killstreak` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `deathstreak` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `banned` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `kicked` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `cmdtime` INT UNSIGNED NOT NULL DEFAULT 0,
  `sqltime` INT UNSIGNED NOT NULL DEFAULT 0,
  `sqmtime` INT UNSIGNED NOT NULL DEFAULT 0,
  `lwtime` INT UNSIGNED NOT NULL DEFAULT 0,
  `timepara` MEDIUMINT NOT NULL DEFAULT 0,	-- Time in parachute
  `wins` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `losses` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `rndscore` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `chng` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `decr` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `mode0` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `mode1` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `mode2` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `permban` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `clantag` VARCHAR(20) NOT NULL DEFAULT '',
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

delimiter $$

CREATE TRIGGER `player_joined` BEFORE INSERT ON `player`
FOR EACH ROW BEGIN
  IF new.joined = 0 THEN
    SET new.joined = UNIX_TIMESTAMP();
  END IF;
END $$

delimiter ;

--
-- Table structure for table `player_army`
--

CREATE TABLE `player_army` (
  `id` TINYINT UNSIGNED NOT NULL,
  `pid` INT UNSIGNED NOT NULL,
  `time` INT UNSIGNED NOT NULL DEFAULT 0,
  `wins` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `losses` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `score` INT NOT NULL DEFAULT 0,
  `best` SMALLINT NOT NULL DEFAULT 0,
  `worst` SMALLINT NOT NULL DEFAULT 0,
  `brnd` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY(`pid`,`id`),
  FOREIGN KEY(`id`) REFERENCES army(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`pid`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `player_award`
--

CREATE TABLE `player_award` (
  `id` MEDIUMINT UNSIGNED NOT NULL,
  `pid` INT UNSIGNED NOT NULL,
  `roundid` INT UNSIGNED DEFAULT NULL,
  `level` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `earned` INT UNSIGNED NOT NULL DEFAULT 0,
  `first` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`pid`,`id`,`level`),
  FOREIGN KEY(`pid`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`roundid`) REFERENCES round_history(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

delimiter $$

CREATE TRIGGER `player_award_timestamps` BEFORE INSERT ON `player_award`
FOR EACH ROW BEGIN
  IF new.earned = 0 THEN
    SET new.earned = UNIX_TIMESTAMP();
  END IF;
END $$

delimiter ;

--
-- Table structure for table `player_kill`
--

CREATE TABLE `player_kill` (
  `attacker` INT UNSIGNED NOT NULL,
  `victim` INT UNSIGNED NOT NULL,
  `count` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY(`attacker`,`victim`),
  FOREIGN KEY(`attacker`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`victim`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `player_map`
--

CREATE TABLE `player_map` (
  `pid` INT UNSIGNED NOT NULL,
  `mapid` SMALLINT UNSIGNED NOT NULL,
  `time` INT UNSIGNED NOT NULL DEFAULT 0,
  `wins` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `losses` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `bestscore` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `worstscore` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`pid`,`mapid`),
  FOREIGN KEY(`pid`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`mapid`) REFERENCES mapinfo(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `player_kit`
--

CREATE TABLE `player_kit` (
  `id` TINYINT UNSIGNED NOT NULL,
  `pid` INT UNSIGNED NOT NULL,
  `time` INT UNSIGNED NOT NULL DEFAULT 0,
  `kills` INT UNSIGNED NOT NULL DEFAULT 0,
  `deaths` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`pid`,`id`),
  FOREIGN KEY(`pid`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `player_history`
--

CREATE TABLE `player_history` (
  `pid` INT UNSIGNED NOT NULL,
  `roundid` INT UNSIGNED NOT NULL,
  `team` TINYINT(1) UNSIGNED NOT NULL,
  `timestamp` INT UNSIGNED NOT NULL,
  `time` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `score` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `cmdscore` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `skillscore` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `teamscore` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `kills` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `deaths` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `rank` TINYINT(2) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`pid`,`roundid`),
  FOREIGN KEY(`pid`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`roundid`) REFERENCES round_history(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `player_unlock`
--

CREATE TABLE `player_unlock` (
  `pid` INT UNSIGNED NOT NULL,
  `unlockid` SMALLINT NOT NULL,
  PRIMARY KEY(`pid`,`unlockid`),
  FOREIGN KEY(`pid`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`unlockid`) REFERENCES `unlock`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

--
-- Table structure for table `player_vehicle`
--

CREATE TABLE `player_vehicle` (
  `id` TINYINT UNSIGNED NOT NULL,
  `pid` INT UNSIGNED NOT NULL,
  `time` INT UNSIGNED NOT NULL DEFAULT 0,
  `kills` INT UNSIGNED NOT NULL DEFAULT 0,
  `deaths` INT UNSIGNED NOT NULL DEFAULT 0,
  `roadkills` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`pid`,`id`),
  FOREIGN KEY(`pid`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `weapons`
--

CREATE TABLE `player_weapon` (
  `id` TINYINT UNSIGNED NOT NULL,
  `pid` INT UNSIGNED NOT NULL,
  `time` INT UNSIGNED NOT NULL DEFAULT 0,
  `kills` INT UNSIGNED NOT NULL DEFAULT 0,
  `deaths` INT UNSIGNED NOT NULL DEFAULT 0,
  `fired` INT UNSIGNED NOT NULL DEFAULT 0,
  `hits` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`pid`,`id`),
  FOREIGN KEY(`pid`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `ip2nation`
--

CREATE TABLE `ip2nation` (
  `ip` INT UNSIGNED NOT NULL DEFAULT 0,
  `country` char(2) NOT NULL DEFAULT '',
  PRIMARY KEY(`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ip2nationcountries`
--

CREATE TABLE `ip2nationcountries` (
  `code` VARCHAR(4) NOT NULL DEFAULT '',
  `iso_code_2` VARCHAR(2) NOT NULL DEFAULT '',
  `iso_code_3` VARCHAR(3) DEFAULT '',
  `iso_country` VARCHAR(255) NOT NULL DEFAULT '',
  `country` VARCHAR(255) NOT NULL DEFAULT '',
  `lat` float NOT NULL DEFAULT 0,
  `lon` float NOT NULL DEFAULT 0,
  PRIMARY KEY(`code`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8;


-- --------------------------------------------------------
-- Create Views
-- --------------------------------------------------------

CREATE OR REPLACE VIEW `player_weapon_view` AS
  SELECT `id`, `pid`, `time`, `kills`, `deaths`, `fired`, `hits`, COALESCE((`hits` * 1.0) / `fired`, 0) AS `accuracy`
  FROM `player_weapon`;

-- --------------------------------------------------------
-- Create Procedures
-- --------------------------------------------------------
delimiter $$

CREATE PROCEDURE `create_player`(
  IN `playerName` VARCHAR(32),
  IN `playerPassword` VARCHAR(32), -- MD5 Hash
  IN `countryCode` VARCHAR(2),
  IN `ipAddress` VARCHAR(46),
  OUT `pid` INT
)
BEGIN
  SELECT COALESCE(max(id), 29000000) + 1 INTO pid FROM player;
  INSERT INTO player(`id`, `name`, `password`, `country`, `lastip`)
    VALUES(pid, playerName, playerPassword, countryCode, ipAddress);
  SELECT pid;
END $$

delimiter ;


-- --------------------------------------------------------
-- Insert Default Data
-- --------------------------------------------------------


--
-- Dumping data for table `unlock`
--
INSERT INTO `unlock` VALUES (11, 0, 'Chsht_protecta', 'Protecta shotgun with slugs');
INSERT INTO `unlock` VALUES (22, 1, 'Usrif_g3a3', 'H&K G3');
INSERT INTO `unlock` VALUES (33, 2, 'USSHT_Jackhammer', 'Jackhammer shotgun');
INSERT INTO `unlock` VALUES (44, 3, 'Usrif_sa80', 'SA-80');
INSERT INTO `unlock` VALUES (55, 4, 'Usrif_g36c', 'G36C');
INSERT INTO `unlock` VALUES (66, 5, 'RULMG_PKM', 'PKM');
INSERT INTO `unlock` VALUES (77, 6, 'USSNI_M95_Barret', 'Barret M82A2 (.50 cal rifle)');
INSERT INTO `unlock` VALUES (88, 1, 'sasrif_fn2000', 'FN2000');
INSERT INTO `unlock` VALUES (99, 2, 'sasrif_mp7', 'MP-7');
INSERT INTO `unlock` VALUES (111, 3, 'sasrif_g36e', 'G36E');
INSERT INTO `unlock` VALUES (222, 4, 'usrif_fnscarl', 'FN SCAR - L');
INSERT INTO `unlock` VALUES (333, 5, 'sasrif_mg36', 'MG36');
INSERT INTO `unlock` VALUES (444, 0, 'eurif_fnp90', 'P90');
INSERT INTO `unlock` VALUES (555, 6, 'gbrif_l96a1', 'L96A1');

--
-- Dumping data for table `army`
--
INSERT INTO `army` VALUES (0, 'U.S Marines', null);
INSERT INTO `army` VALUES (1, 'Middle Eastern Collation', null);
INSERT INTO `army` VALUES (2, 'Peoples Liberation Army', null);
INSERT INTO `army` VALUES (3, 'Navy Seals', null);
INSERT INTO `army` VALUES (4, 'SAS', null);
INSERT INTO `army` VALUES (5, 'SPETZNAS', null);
INSERT INTO `army` VALUES (6, 'MEC Special Forces', null);
INSERT INTO `army` VALUES (7, 'Rebels', null);
INSERT INTO `army` VALUES (8, 'Insurgents', null);
INSERT INTO `army` VALUES (9, 'Euro Forces', null);
INSERT INTO `army` VALUES (10, 'German Forces', null);
INSERT INTO `army` VALUES (11, 'Ukrainian Forces', null);
INSERT INTO `army` VALUES (12, 'United Nations', null);
INSERT INTO `army` VALUES (13, 'Canadian Forces', null);
INSERT INTO `army` VALUES (14, 'Blackwater', null);
INSERT INTO `army` VALUES (15, 'Taliban', null);
INSERT INTO `army` VALUES (16, 'Australian Forces', null);
INSERT INTO `army` VALUES (17, 'Russian Forces', null);
INSERT INTO `army` VALUES (18, 'British Forces', null);
INSERT INTO `army` VALUES (19, 'NATO Forces', null);
INSERT INTO `army` VALUES (20, 'ISIS', null);
INSERT INTO `army` VALUES (21, 'Iraqi Forces', null);
INSERT INTO `army` VALUES (22, 'U.S Marine Corps', null);
INSERT INTO `army` VALUES (23, 'Somalian Forces', null);
INSERT INTO `army` VALUES (24, 'U.S Army Rangers', null);

--
-- Dumping data for table `mapinfo`
--


--
-- Dumping data for table `_version`
--
INSERT INTO `_version`(`updateid`, `version`) VALUES (30000, '3.0.0');

INSERT INTO `server`(`ip`, `prefix`, `name`, `port`, `queryport`) VALUES ('127.0.0.1', 'w212', 'Local Server 1', 16567, 29900);
INSERT INTO `server`(`ip`, `prefix`, `name`, `port`, `queryport`) VALUES ('::1', 'w212', 'Local Server 2', 16567, 29900);

INSERT INTO `player`(`id`, `name`, `country`, `password`) VALUES (101249154, ' wilson212', 'US', '');