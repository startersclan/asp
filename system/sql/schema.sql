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
DROP VIEW IF EXISTS `player_awards_view`;
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
DROP TABLE IF EXISTS `weapon`;
DROP TABLE IF EXISTS `vehicle`;
DROP TABLE IF EXISTS `unlock`;
DROP TABLE IF EXISTS `round_history`;
DROP TABLE IF EXISTS `server`;
DROP TABLE IF EXISTS `mapinfo`;
DROP TABLE IF EXISTS `kit`;
DROP TABLE IF EXISTS `award`;
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
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `award`
--

CREATE TABLE `award` (
  `id` MEDIUMINT UNSIGNED,              -- Award id, as defined in the medal_data.py
  `code` VARCHAR(6) UNIQUE NOT NULL,    -- Snapshot award short name, case sensitive
  `name` VARCHAR(64) NOT NULL,          -- Full name of the award, human readable
  `type` TINYINT NOT NULL,              -- 0 = ribbon, 1 = Badge, 2 = medal
  `backend` TINYINT NOT NULL DEFAULT 0, -- Bool: Awarded in the ASP snapshot processor?
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `kit`
--

CREATE TABLE `kit` (
  `id` TINYINT UNSIGNED,
  `name` VARCHAR(32) NOT NULL,
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
  `authorized` TINYINT(1) NOT NULL DEFAULT 1, -- Servers are allowed to post snapshots
  `lastupdate` INT NOT NULL DEFAULT 0,
  PRIMARY KEY(`id`),
  CONSTRAINT `ip-port-unq` UNIQUE (`ip`, `port`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `round_history`
--

CREATE TABLE `round_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `mapid` SMALLINT UNSIGNED NOT NULL,
  `serverid` SMALLINT UNSIGNED NOT NULL,
  `round_start` INT UNSIGNED NOT NULL,
  `round_end` INT UNSIGNED NOT NULL,
  `imported` INT UNSIGNED NOT NULL,
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
  `id` SMALLINT UNSIGNED,
  `kit` TINYINT UNSIGNED NOT NULL,
  `name` VARCHAR(32) NOT NULL,
  `desc` VARCHAR(64) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY(`kit`) REFERENCES kit(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `vehicle`
--

CREATE TABLE `vehicle` (
  `id` TINYINT UNSIGNED,
  `name` VARCHAR(32) NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `weapon`
--

CREATE TABLE `weapon` (
  `id` TINYINT UNSIGNED,
  `name` VARCHAR(32) NOT NULL,
  PRIMARY KEY(`id`)
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
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(32) UNIQUE NOT NULL,
  `password` VARCHAR(32) NOT NULL,
  `country` CHAR(2) NOT NULL DEFAULT 'xx',
  `lastip` VARCHAR(15) NOT NULL DEFAULT '0.0.0.0',
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

ALTER TABLE player AUTO_INCREMENT=2900000;

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
  FOREIGN KEY(`id`) REFERENCES army(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY(`pid`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `player_award`
--

CREATE TABLE `player_award` (
  `id` MEDIUMINT UNSIGNED NOT NULL,   -- Award ID
  `pid` INT UNSIGNED NOT NULL,        -- Player ID
  `roundid` INT UNSIGNED NOT NULL,    -- The round this award was earned in
  `level` TINYINT UNSIGNED NOT NULL DEFAULT 1, -- Badges ONLY, 1 = bronze, 2 = silver, 3 = gold
  PRIMARY KEY(`pid`, `id`, `roundid`, `level`),
  FOREIGN KEY(`pid`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`id`) REFERENCES award(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY(`roundid`) REFERENCES round_history(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE INDEX `idx_player_awards` ON player_award(`pid`, `id`);

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
  FOREIGN KEY(`pid`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`id`) REFERENCES kit(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
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
  `unlockid` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY(`pid`,`unlockid`),
  FOREIGN KEY(`pid`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`unlockid`) REFERENCES `unlock`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET utf8;

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
  FOREIGN KEY(`pid`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`id`) REFERENCES vehicle(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
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
  FOREIGN KEY(`pid`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`id`) REFERENCES weapon(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------
-- Create Views
-- --------------------------------------------------------

CREATE OR REPLACE VIEW `player_weapon_view` AS
  SELECT `id`, `pid`, `time`, `kills`, `deaths`, `fired`, `hits`, COALESCE((`hits` * 1.0) / `fired`, 0) AS `accuracy`
  FROM `player_weapon`;

CREATE OR REPLACE VIEW `player_awards_view` AS
  SELECT a.id AS `id`, a.pid AS `pid`, MAX(r.round_end) AS `earned`, MIN(r.round_end) AS `first`, COUNT(`level`) AS `level`
  FROM player_award AS a
    JOIN round_history AS r ON a.roundid = r.id
  GROUP BY a.pid, a.id;

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
  -- SELECT COALESCE(max(id), 29000000) + 1 INTO pid FROM player;
  INSERT INTO player(`id`, `name`, `password`, `country`, `lastip`)
    VALUES(pid, playerName, playerPassword, countryCode, ipAddress);
  SELECT pid;
END $$

delimiter ;


-- --------------------------------------------------------
-- Insert Default Data
-- --------------------------------------------------------

--
-- Dumping data for table `army`
--

INSERT INTO `army` VALUES (0, 'U.S Marines');
INSERT INTO `army` VALUES (1, 'Middle Eastern Collation');
INSERT INTO `army` VALUES (2, 'Peoples Liberation Army');
INSERT INTO `army` VALUES (3, 'Navy Seals');
INSERT INTO `army` VALUES (4, 'SAS');
INSERT INTO `army` VALUES (5, 'SPETZNAS');
INSERT INTO `army` VALUES (6, 'MEC Special Forces');
INSERT INTO `army` VALUES (7, 'Rebels');
INSERT INTO `army` VALUES (8, 'Insurgents');
INSERT INTO `army` VALUES (9, 'Euro Forces');
INSERT INTO `army` VALUES (10, 'German Forces');
INSERT INTO `army` VALUES (11, 'Ukrainian Forces');
INSERT INTO `army` VALUES (12, 'United Nations');
INSERT INTO `army` VALUES (13, 'Canadian Forces');
INSERT INTO `army` VALUES (14, 'Blackwater');
INSERT INTO `army` VALUES (15, 'Taliban');
INSERT INTO `army` VALUES (16, 'Australian Forces');
INSERT INTO `army` VALUES (17, 'Russian Forces');
INSERT INTO `army` VALUES (18, 'British Forces');
INSERT INTO `army` VALUES (19, 'NATO Forces');
INSERT INTO `army` VALUES (20, 'ISIS');
INSERT INTO `army` VALUES (21, 'Iraqi Forces');
INSERT INTO `army` VALUES (22, 'U.S Marine Corps');
INSERT INTO `army` VALUES (23, 'Somalian Forces');
INSERT INTO `army` VALUES (24, 'U.S Army Rangers');

--
-- Dumping data for table `award`
--

INSERT INTO `award` VALUES (1031406, 'kcb', 'Knife Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1031619, 'pcb', 'Pistol Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1031119, 'Acb', 'Assault Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1031120, 'Atcb', 'Anti-Tank Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1031109, 'Sncb', 'Sniper Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1031115, 'Socb', 'Spec Ops Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1031121, 'Sucb', 'Support Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1031105, 'Ecb', 'Engineer Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1031113, 'Mcb', 'Medic Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1032415, 'Eob', 'Explosive Ordinance Badge', 1, 0);
INSERT INTO `award` VALUES (1190601, 'Fab', 'First Aid Badge', 1, 0);
INSERT INTO `award` VALUES (1190507, 'Eb', 'Engineer Badge', 1, 0);
INSERT INTO `award` VALUES (1191819, 'Rb', 'Resupply Badge', 1, 0);
INSERT INTO `award` VALUES (1190304, 'Cb', 'Command Badge', 1, 0);
INSERT INTO `award` VALUES (1220118, 'Ab', 'Armour Badge', 1, 0);
INSERT INTO `award` VALUES (1222016, 'Tb', 'Transport Badge', 1, 0);
INSERT INTO `award` VALUES (1220803, 'Hb', 'Helicopter Badge', 1, 0);
INSERT INTO `award` VALUES (1220122, 'Avb', 'Aviator Badge', 1, 0);
INSERT INTO `award` VALUES (1220104, 'adb', 'Air Defence Badge', 1, 0);
INSERT INTO `award` VALUES (1031923, 'Swb', 'Ground Defence Badge', 1, 0);
INSERT INTO `award` VALUES (1261119, 'X1Acb', 'SF Assault Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1261120, 'X1Atcb', 'SF Anti-Tank Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1261109, 'X1Sncb', 'SF Sniper Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1261115, 'X1Socb', 'SF Spec Ops Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1261121, 'X1Sucb', 'SF Support Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1261105, 'X1Ecb', 'SF Engineer Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1261113, 'X1Mcb', 'SF Medic Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1260602, 'X1fbb', 'SF Tactical Support Combat Badge', 1, 0);
INSERT INTO `award` VALUES (1260708, 'X1ghb', 'Grappling Hook Usage', 1, 0);
INSERT INTO `award` VALUES (1262612, 'X1zlb', 'Zip Line Usage', 1, 0);

INSERT INTO `award` VALUES (3240301, 'Car', 'Combat Action Ribbon', 0, 0);
INSERT INTO `award` VALUES (3211305, 'Mur', 'Meritorious Unit Ribbon', 0, 0);
INSERT INTO `award` VALUES (3150914, 'Ior', 'Infantry Officer Ribbon', 0, 0);
INSERT INTO `award` VALUES (3151920, 'Sor', 'Staff Officer Ribbon', 0, 0);
INSERT INTO `award` VALUES (3190409, 'Dsr', 'Distinguished Service Ribbon', 0, 0);
INSERT INTO `award` VALUES (3242303, 'Wcr', 'War College Ribbon', 0, 0);
INSERT INTO `award` VALUES (3212201, 'Vur', 'Valorous Unit Ribbon', 0, 0);
INSERT INTO `award` VALUES (3241213, 'Lmr', 'Legion of Merit Ribbon', 0, 0);
INSERT INTO `award` VALUES (3190318, 'Csr', 'Crew Service Ribbon', 0, 0);
INSERT INTO `award` VALUES (3190118, 'Arr', 'Armoured Service Ribbon', 0, 0);
INSERT INTO `award` VALUES (3190105, 'Aer', 'Aerial Service Ribbon', 0, 0);
INSERT INTO `award` VALUES (3190803, 'Hsr', 'Helicopter Service Ribbon', 0, 0);
INSERT INTO `award` VALUES (3040109, 'Adr', 'Air-Defence Ribbon', 0, 0);
INSERT INTO `award` VALUES (3040718, 'Gdr', 'Ground Defence Ribbon', 0, 0);
INSERT INTO `award` VALUES (3240102, 'Ar', 'Airborne Ribbon', 0, 0);
INSERT INTO `award` VALUES (3240703, 'gcr', 'Good Conduct Ribbon', 0, 0);
INSERT INTO `award` VALUES (3260318, 'X1Csr', 'SF Crew Service Ribbon', 0, 0);
INSERT INTO `award` VALUES (3260118, 'X1Arr', 'SF Armoured Service Ribbon', 0, 0);
INSERT INTO `award` VALUES (3260105, 'X1Aer', 'SF Aerial Service Ribbon', 0, 0);
INSERT INTO `award` VALUES (3260803, 'X1Hsr', 'SF Helicopter Service Ribbon', 0, 0);

INSERT INTO `award` VALUES (2191608, 'ph', 'Purple Heart', 2, 0);
INSERT INTO `award` VALUES (2191319, 'msm', 'Meritorious Service Medal', 2, 0);
INSERT INTO `award` VALUES (2190303, 'Cam', 'Combat Action Medal', 2, 0);
INSERT INTO `award` VALUES (2190309, 'Acm', 'Air Combat Medal', 2, 0);
INSERT INTO `award` VALUES (2190318, 'Arm', 'Armour Combat Medal', 2, 0);
INSERT INTO `award` VALUES (2190308, 'Hcm', 'Helicopter Combat Medal', 2, 0);
INSERT INTO `award` VALUES (2190703, 'gcm', 'Good Conduct Medal', 2, 0);
INSERT INTO `award` VALUES (2020903, 'Cim', 'Combat Infantry Medal', 2, 0);
INSERT INTO `award` VALUES (2020913, 'Mim', 'Marksman Infantry Medal', 2, 0);
INSERT INTO `award` VALUES (2020919, 'Sim', 'Sharpshooter Infantry Medal', 2, 0);
INSERT INTO `award` VALUES (2021322, 'Mvm', 'Medal of Valour', 2, 0);
INSERT INTO `award` VALUES (2020419, 'Dsm', 'Distinguished Service Medal', 2, 0);

INSERT INTO `award` VALUES (2051907, 'erg', 'End of Round Gold Star', 2, 0);
INSERT INTO `award` VALUES (2051919, 'ers', 'End of Round Silver Star', 2, 0);
INSERT INTO `award` VALUES (2051902, 'erb', 'End of Round Bronze Star', 2, 0);

--
-- Backend Awards
--

INSERT INTO `award` VALUES (3191305, 'Msr', 'Mid-East Service Ribbon', 0, 1);
INSERT INTO `award` VALUES (3190605, 'Fsr', 'Far-East Service Ribbon', 0, 1);
INSERT INTO `award` VALUES (2021403, 'Ncm', 'Navy Cross', 2, 1);
INSERT INTO `award` VALUES (2020719, 'Gsm', 'Golden Scimitar', 2, 1);
INSERT INTO `award` VALUES (2021613, 'pmm', 'People''s Medallion', 2, 1);
INSERT INTO `award` VALUES (2270521, 'Eun', 'European Union Special Service Medal', 2, 1);
INSERT INTO `award` VALUES (3270519, 'Esr', 'European Union Service Ribbon', 0, 1);
INSERT INTO `award` VALUES (3271401, 'Nas', 'North American Service Ribbon', 0, 1);
INSERT INTO `award` VALUES (2261913, 'X1Nsm', 'Navy Seal Special Service Medal', 2, 1);
INSERT INTO `award` VALUES (2261919, 'X1Ssm', 'SAS Special Service Medal', 2, 1);
INSERT INTO `award` VALUES (2261613, 'X1Spm', 'SPETZ Special Service Medal', 2, 1);
INSERT INTO `award` VALUES (2261303, 'X1Mcm', 'MECSF Special Service Medal', 2, 1);
INSERT INTO `award` VALUES (2261802, 'X1Rbm', 'Rebel Special Service Medal', 2, 1);
INSERT INTO `award` VALUES (2260914, 'X1Inm', 'Insurgent Special Service Medal', 2, 1);
INSERT INTO `award` VALUES (3261919, 'X1Nss', 'Navy Seal Service Ribbon', 0, 1);
INSERT INTO `award` VALUES (3261901, 'X1Sas', 'SAS Service Ribbon', 0, 1);
INSERT INTO `award` VALUES (3261819, 'X1Rsz', 'SPETZNAS Service Ribbon', 0, 1);
INSERT INTO `award` VALUES (3261319, 'X1Msf', 'MECSF Service Ribbon', 0, 1);
INSERT INTO `award` VALUES (3261805, 'X1Reb', 'Rebel Service Ribbon', 0, 1);
INSERT INTO `award` VALUES (3260914, 'X1Ins', 'Insurgent Service Ribbon', 0, 1);

--
-- Dumping data for table `kit`
--

INSERT INTO `kit` VALUES (0, 'Anti-Tank');
INSERT INTO `kit` VALUES (1, 'Assault');
INSERT INTO `kit` VALUES (2, 'Engineer');
INSERT INTO `kit` VALUES (3, 'Medic');
INSERT INTO `kit` VALUES (4, 'Special Ops');
INSERT INTO `kit` VALUES (5, 'Support');
INSERT INTO `kit` VALUES (6, 'Sniper');

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
-- Dumping data for table `vehicle`
--

INSERT INTO `vehicle` VALUES (0, 'Armor');
INSERT INTO `vehicle` VALUES (1, 'Aviator');
INSERT INTO `vehicle` VALUES (2, 'Air Defense');
INSERT INTO `vehicle` VALUES (3, 'Helicopter');
INSERT INTO `vehicle` VALUES (4, 'Transport');
INSERT INTO `vehicle` VALUES (5, 'Artillery');
INSERT INTO `vehicle` VALUES (6, 'Ground Defense');

--
-- Dumping data for table `weapon`
--

INSERT INTO `weapon` VALUES (0, 'Assault Rifle');
INSERT INTO `weapon` VALUES (1, 'Assault Grenade');
INSERT INTO `weapon` VALUES (2, 'Carbine');
INSERT INTO `weapon` VALUES (3, 'Light Machine Gun');
INSERT INTO `weapon` VALUES (4, 'Sniper Rifle');
INSERT INTO `weapon` VALUES (5, 'Pistol');
INSERT INTO `weapon` VALUES (6, 'Anti-Tank / Anti-Air');
INSERT INTO `weapon` VALUES (7, 'Sub Machine Gun');
INSERT INTO `weapon` VALUES (8, 'Shotgun');
INSERT INTO `weapon` VALUES (9, 'Knife');
INSERT INTO `weapon` VALUES (10, 'Defibrillator');
INSERT INTO `weapon` VALUES (11, 'C4');
INSERT INTO `weapon` VALUES (12, 'Hand Grenade');
INSERT INTO `weapon` VALUES (13, 'Claymore');
INSERT INTO `weapon` VALUES (14, 'Anti-Tank Mine');
INSERT INTO `weapon` VALUES (15, 'Grappling Hook');
INSERT INTO `weapon` VALUES (16, 'Zipline');
INSERT INTO `weapon` VALUES (17, 'Tactical');

--
-- Dumping data for table `_version`
--
INSERT INTO `_version`(`updateid`, `version`) VALUES (30000, '3.0.0');

INSERT INTO `server`(`ip`, `prefix`, `name`, `port`, `queryport`) VALUES ('127.0.0.1', 'w212', 'Local Server 1', 16567, 29900);
INSERT INTO `server`(`ip`, `prefix`, `name`, `port`, `queryport`) VALUES ('::1', 'w212', 'Local Server 2', 16567, 29900);

INSERT INTO `player`(`id`, `name`, `country`, `password`) VALUES (101249154, ' wilson212', 'US', '');