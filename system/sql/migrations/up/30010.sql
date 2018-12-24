--
-- Remove procedures and views
--
DROP PROCEDURE IF EXISTS `create_player`;
DROP VIEW IF EXISTS `round_history_view`;
DROP VIEW IF EXISTS `player_history_view`;
DROP TRIGGER IF EXISTS `player_rank_change`;


--
-- Remove unused tables
--
DROP TABLE IF EXISTS `server_auth_ip`;
DROP TABLE IF EXISTS `ip2nationcountries`;
DROP TABLE IF EXISTS `ip2nation`;

--
-- Table Renames
--
ALTER TABLE `player_history` RENAME TO `player_round_history`;
ALTER TABLE `round_history` RENAME TO `round`;
ALTER TABLE `mapinfo` RENAME TO `map`;
ALTER TABLE `map` ADD COLUMN `displayname` VARCHAR(48) DEFAULT NULL AFTER `name`;
UPDATE `map` SET `displayname`=`name` WHERE `displayname` IS NULL;

--
-- Remove unused columns from the map table
--
ALTER TABLE map DROP COLUMN `score`;
ALTER TABLE map DROP COLUMN `kills`;
ALTER TABLE map DROP COLUMN `deaths`;
ALTER TABLE map DROP COLUMN `time`;
ALTER TABLE map DROP COLUMN `times`;
ALTER TABLE map DROP COLUMN `custom`;

--
-- Table structure for table `game_mod`
--

CREATE TABLE IF NOT EXISTS `game_mod` (
  `id` TINYINT UNSIGNED AUTO_INCREMENT,
  `name` VARCHAR(24) UNIQUE NOT NULL,
  `longname` VARCHAR(48) NOT NULL,
  `authorized` TINYINT(1) NOT NULL DEFAULT 1, -- Indicates whether we allow this mod
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `game_mode`
--

CREATE TABLE IF NOT EXISTS `game_mode` (
  `id` TINYINT UNSIGNED,
  `name` VARCHAR(48) UNIQUE NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `kit`
--

CREATE TABLE IF NOT EXISTS `rank` (
  `id` TINYINT UNSIGNED,
  `name` VARCHAR(32) NOT NULL,
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `stats_provider`
--

CREATE TABLE IF NOT EXISTS `stats_provider` (
  `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,     -- Public provider ID
  `auth_id` MEDIUMINT UNSIGNED NOT NULL UNIQUE,       -- Private auth id
  `auth_token` VARCHAR(16) NOT NULL,                  -- Private auth token
  `name` VARCHAR(100) DEFAULT NULL,                   -- Provider name
  `authorized` TINYINT(1) NOT NULL DEFAULT 0,         -- Auth Token is allowed to post stats data to the ASP
  `plasma` TINYINT(1) NOT NULL DEFAULT 0,             -- Plasma all of their servers?
  PRIMARY KEY(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `stats_provider_auth_ip`
--

CREATE TABLE IF NOT EXISTS `stats_provider_auth_ip` (
  `provider_id` SMALLINT UNSIGNED NOT NULL,           -- Provider ID
  `address` VARCHAR(50) NOT NULL DEFAULT '',          -- Authorized IP Address, length 46 + 4 for CIDR ranges
  PRIMARY KEY(`provider_id`, `address`),
  FOREIGN KEY(`provider_id`) REFERENCES stats_provider(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `player_army_history`
--

CREATE TABLE IF NOT EXISTS `player_army_history` (
  `player_id` INT UNSIGNED NOT NULL,
  `round_id` INT UNSIGNED NOT NULL,
  `army_id` TINYINT UNSIGNED NOT NULL,
  `time` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`player_id`,`round_id`,`army_id`),
  FOREIGN KEY(`player_id`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`round_id`) REFERENCES round(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`army_id`) REFERENCES army(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `player_kit_history`
--

CREATE TABLE IF NOT EXISTS `player_kit_history` (
  `player_id` INT UNSIGNED NOT NULL,
  `round_id` INT UNSIGNED NOT NULL,
  `kit_id` TINYINT UNSIGNED NOT NULL,
  `time` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `kills` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `deaths` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`player_id`,`round_id`,`kit_id`),
  FOREIGN KEY(`player_id`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`round_id`) REFERENCES round(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`kit_id`) REFERENCES kit(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `player_kit_history`
--

CREATE TABLE IF NOT EXISTS `player_kill_history` (
  `round_id` INT UNSIGNED NOT NULL,
  `attacker` INT UNSIGNED NOT NULL,
  `victim` INT UNSIGNED NOT NULL,
  `count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`attacker`,`round_id`,`victim`),
  FOREIGN KEY(`attacker`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`round_id`) REFERENCES round(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`victim`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `player_vehicle_history`
--

CREATE TABLE IF NOT EXISTS `player_vehicle_history` (
  `player_id` INT UNSIGNED NOT NULL,
  `round_id` INT UNSIGNED NOT NULL,
  `vehicle_id` TINYINT UNSIGNED NOT NULL,
  `time` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `kills` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `deaths` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `roadkills` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`player_id`,`round_id`,`vehicle_id`),
  FOREIGN KEY(`player_id`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`round_id`) REFERENCES round(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`vehicle_id`) REFERENCES vehicle(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `player_weapon_history`
--

CREATE TABLE IF NOT EXISTS `player_weapon_history` (
  `player_id` INT UNSIGNED NOT NULL,
  `round_id` INT UNSIGNED NOT NULL,
  `weapon_id` TINYINT UNSIGNED NOT NULL,
  `time` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `kills` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `deaths` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `fired` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `hits` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
  `deployed` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(`player_id`,`round_id`,`weapon_id`),
  FOREIGN KEY(`player_id`) REFERENCES player(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`round_id`) REFERENCES round(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY(`weapon_id`) REFERENCES weapon(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- New format for the `server` table
--
ALTER TABLE `server` DROP COLUMN `plasma`;
ALTER TABLE `server` DROP COLUMN `authorized`;
ALTER TABLE `server` CHANGE `port` `gameport` SMALLINT UNSIGNED DEFAULT 16567;
ALTER TABLE `server` ADD COLUMN `provider_id` SMALLINT UNSIGNED NOT NULL DEFAULT 1 AFTER `id`;

--
-- Insert default provider
-- Add Constraint to server
INSERT INTO `stats_provider` VALUES (1, 112960, '2GS61JLR2WQq2n6N', 'NAW Default Trusted Provider', 1, 0);
ALTER TABLE `server` ADD FOREIGN KEY(`provider_id`) REFERENCES stats_provider(`id`);

INSERT INTO `stats_provider_auth_ip` VALUES (1, '0.0.0.0/0');

--
-- Dumping data for table `rank`
--

INSERT INTO `rank` VALUES (0, 'Private');
INSERT INTO `rank` VALUES (1, 'Private First Class');
INSERT INTO `rank` VALUES (2, 'Lance Corporal');
INSERT INTO `rank` VALUES (3, 'Corporal');
INSERT INTO `rank` VALUES (4, 'Sergeant');
INSERT INTO `rank` VALUES (5, 'Staff Sergeant');
INSERT INTO `rank` VALUES (6, 'Gunnery Sergeant');
INSERT INTO `rank` VALUES (7, 'Master Sergeant');
INSERT INTO `rank` VALUES (8, 'First Sergeant');
INSERT INTO `rank` VALUES (9, 'Master Gunnery Sergeant');
INSERT INTO `rank` VALUES (10, 'Sergeant Major');
INSERT INTO `rank` VALUES (11, 'Sergeant Major of the Corp');
INSERT INTO `rank` VALUES (12, '2nd Lieutenant');
INSERT INTO `rank` VALUES (13, '1st Lieutenant');
INSERT INTO `rank` VALUES (14, 'Captain');
INSERT INTO `rank` VALUES (15, 'Major');
INSERT INTO `rank` VALUES (16, 'Lieutenant Colonel');
INSERT INTO `rank` VALUES (17, 'Colonel');
INSERT INTO `rank` VALUES (18, 'Brigadier General');
INSERT INTO `rank` VALUES (19, 'Major General');
INSERT INTO `rank` VALUES (20, 'Lieutenant General');
INSERT INTO `rank` VALUES (21, 'General');

--
-- Dumping data for table `game_mod`
--
INSERT INTO `game_mod`(`id`, `name`, `longname`, `authorized`) VALUES (1, 'bf2', 'Battlefield 2', 1);
INSERT INTO `game_mod`(`id`, `name`, `longname`, `authorized`) VALUES (2, 'xpack', 'Battlefield 2: Special Forces', 1);
INSERT INTO `game_mod`(`id`, `name`, `longname`, `authorized`) VALUES (3, 'aix2', 'Allied Intent Extended 2.0', 0);
INSERT INTO `game_mod`(`id`, `name`, `longname`, `authorized`) VALUES (4, 'naw', 'Nations At War', 0);

--
-- Dumping data for table `game_mode`
--
INSERT INTO `game_mode`(`id`, `name`) VALUES (0, 'Conquest');
INSERT INTO `game_mode`(`id`, `name`) VALUES (1, 'Single Player');
INSERT INTO `game_mode`(`id`, `name`) VALUES (2, 'Coop');
INSERT INTO `game_mode`(`id`, `name`) VALUES (99, 'Unknown');

--
-- Alter the Player Table
--
ALTER TABLE `player` CHANGE `rank` `rank_id` TINYINT UNSIGNED;
ALTER TABLE `player` CHANGE `ammos` `resupplies` MEDIUMINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player` CHANGE `rndscore` `bestscore` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player` ADD FOREIGN KEY(`rank_id`) REFERENCES `rank`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Alter the Player History Table
--
ALTER TABLE `player_round_history` DROP COLUMN `timestamp`;
ALTER TABLE `player_round_history` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;
ALTER TABLE `player_round_history` CHANGE `roundid` `round_id` INT UNSIGNED NOT NULL;
ALTER TABLE `player_round_history` CHANGE `team` `army_id` TINYINT UNSIGNED NOT NULL;
ALTER TABLE `player_round_history` CHANGE `rank` `rank_id` TINYINT UNSIGNED NOT NULL;
ALTER TABLE `player_round_history` ADD COLUMN `captures` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `neutralizes` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `captureassists` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `neutralizeassists` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `defends` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `heals` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `revives` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `resupplies` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `repairs` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `damageassists` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `targetassists` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `driverspecials` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `teamkills` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `teamdamage` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `teamvehicledamage` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `suicides` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `killstreak` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `deathstreak` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `cmdtime` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `sqltime` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `sqmtime` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `lwtime` SMALLINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `timepara` SMALLINT NOT NULL DEFAULT 0;	-- Time in parachute
ALTER TABLE `player_round_history` ADD COLUMN `completed` TINYINT(1) NOT NULL DEFAULT 0; -- Completed round?
ALTER TABLE `player_round_history` ADD COLUMN `banned` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD COLUMN `kicked` TINYINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `player_round_history` ADD FOREIGN KEY(`rank_id`) REFERENCES `rank`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Alter the Player Rank History Table
--
ALTER TABLE `player_rank_history` CHANGE `to_rank` `to_rank_id` TINYINT UNSIGNED NOT NULL;
ALTER TABLE `player_rank_history` CHANGE `from_rank` `from_rank_id` TINYINT UNSIGNED NOT NULL ;
ALTER TABLE `player_rank_history` ADD FOREIGN KEY(`to_rank_id`) REFERENCES `rank`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `player_rank_history` ADD FOREIGN KEY(`from_rank_id`) REFERENCES `rank`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Alter the Round History Table
--
ALTER TABLE `round` CHANGE `mapid` `map_id` SMALLINT UNSIGNED NOT NULL;
ALTER TABLE `round` CHANGE `serverid` `server_id` SMALLINT UNSIGNED NOT NULL;
ALTER TABLE `round` CHANGE `round_start` `time_start` INT UNSIGNED NOT NULL;
ALTER TABLE `round` CHANGE `round_end` `time_end` INT UNSIGNED NOT NULL;
ALTER TABLE `round` CHANGE `imported` `time_imported` INT UNSIGNED NOT NULL;
ALTER TABLE `round` CHANGE `gamemode` `gamemode_id` TINYINT UNSIGNED NOT NULL;
ALTER TABLE `round` CHANGE `team1` `team1_army_id` TINYINT UNSIGNED NOT NULL;
ALTER TABLE `round` CHANGE `team2` `team2_army_id` TINYINT UNSIGNED NOT NULL;
ALTER TABLE `round` DROP COLUMN `mod`;
ALTER TABLE `round` DROP COLUMN `pids1`;
ALTER TABLE `round` DROP COLUMN `pids1_end`;
ALTER TABLE `round` DROP COLUMN `pids2`;
ALTER TABLE `round` DROP COLUMN `pids2_end`;
ALTER TABLE `round` ADD COLUMN `mod_id` TINYINT UNSIGNED DEFAULT 4 AFTER `server_id`;
ALTER TABLE `round` ADD FOREIGN KEY(`mod_id`) REFERENCES game_mod(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `round` ADD FOREIGN KEY(`gamemode_id`) REFERENCES game_mode(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Alter Table player_army
--
ALTER TABLE `player_army` CHANGE `id` `army_id` TINYINT UNSIGNED NOT NULL;
ALTER TABLE `player_army` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

--
-- Alter Table player_award
--
ALTER TABLE `player_award` CHANGE `id` `award_id` MEDIUMINT UNSIGNED NOT NULL;
ALTER TABLE `player_award` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;
ALTER TABLE `player_award` CHANGE `roundid` `round_id` INT UNSIGNED NOT NULL;

--
-- Alter Table player_kit
--
ALTER TABLE `player_kit` CHANGE `id` `kit_id` TINYINT UNSIGNED NOT NULL;
ALTER TABLE `player_kit` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

--
-- Alter Table player_map
--
ALTER TABLE `player_map` CHANGE `mapid` `map_id` SMALLINT UNSIGNED NOT NULL;
ALTER TABLE `player_map` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

--
-- Alter Table player_unlock
--
ALTER TABLE `player_unlock` CHANGE `unlockid` `unlock_id` SMALLINT UNSIGNED NOT NULL;
ALTER TABLE `player_unlock` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

--
-- Alter Table player_weapon
--
ALTER TABLE `player_weapon` CHANGE `id` `weapon_id` TINYINT UNSIGNED NOT NULL;
ALTER TABLE `player_weapon` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;
ALTER TABLE `player_weapon` ADD COLUMN `deployed` SMALLINT UNSIGNED NOT NULL DEFAULT 0;

--
-- Alter Table player_vehicle
--
ALTER TABLE `player_vehicle` CHANGE `id` `vehicle_id` TINYINT UNSIGNED NOT NULL;
ALTER TABLE `player_vehicle` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

--
-- Alter Table unlock
--
ALTER TABLE `unlock` CHANGE `kit` `kit_id` TINYINT UNSIGNED NOT NULL;

--
-- Alter Table battlespy_report
--
ALTER TABLE `battlespy_report` CHANGE `roundid` `round_id` INT UNSIGNED NOT NULL;
ALTER TABLE `battlespy_report` CHANGE `serverid` `server_id` SMALLINT UNSIGNED NOT NULL;

--
-- Alter Table battlespy_message
--
ALTER TABLE `battlespy_message` CHANGE `reportid` `report_id` INT UNSIGNED NOT NULL;
ALTER TABLE `battlespy_message` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

-- --------------------------------------------------------
-- Create Views
-- --------------------------------------------------------

CREATE OR REPLACE VIEW `player_weapon_view` AS
  SELECT `weapon_id`, `player_id`, `time`, `kills`, `deaths`, `fired`, `hits`, COALESCE((`hits` * 1.0) / `fired`, 0) AS `accuracy`
  FROM `player_weapon`;

CREATE OR REPLACE VIEW `player_awards_view` AS
  SELECT a.award_id AS `id`, a.player_id AS `pid`, MAX(r.time_end) AS `earned`, MIN(r.time_end) AS `first`, COUNT(`level`) AS `level`
  FROM player_award AS a
    LEFT JOIN round AS r ON a.round_id = r.id
  GROUP BY a.player_id, a.award_id;

CREATE OR REPLACE VIEW `round_history_view` AS
  SELECT h.id AS `id`, mi.displayname AS `map`, h.time_end AS `round_end`, h.team1_army_id AS `team1`,
         h.team2_army_id AS `team2`, h.winner AS `winner`, s.name AS `server_name`, s.id AS `server_id`,
         GREATEST(h.tickets1, h.tickets2) AS `tickets`,
         (SELECT COUNT(*) FROM player_round_history AS prh WHERE prh.round_id = h.id) AS `players`
  FROM `round` AS h
    LEFT JOIN map AS mi ON h.map_id = mi.id
    LEFT JOIN `server` AS s ON h.server_id = s.id;

CREATE OR REPLACE VIEW `player_history_view` AS
  SELECT ph.*, mi.name AS mapname, mi.displayname AS map_display_name, server.name AS name, rh.time_end
  FROM player_round_history AS ph
    LEFT JOIN round AS rh ON ph.round_id = rh.id
    LEFT JOIN server ON rh.server_id = server.id
    LEFT JOIN map AS mi ON rh.map_id = mi.id;

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
    INSERT INTO player(`id`, `name`, `password`, `country`, `lastip`, `rank_id`)
    VALUES(pid, playerName, playerPassword, countryCode, ipAddress, 0);
    SELECT pid;
  END $$

# Insert row into `player_rank_history` on rank change
CREATE TRIGGER `player_rank_change` AFTER UPDATE ON `player`
  FOR EACH ROW BEGIN
  IF new.rank_id != old.rank_id THEN
    REPLACE INTO player_rank_history VALUES (new.id, new.rank_id, old.rank_id, UNIX_TIMESTAMP());
  END IF;
END $$


delimiter ;

--
-- Always update version table!!!
--
-- INSERT INTO `_version`(`updateid`, `version`) VALUES (30010, '3.0.1');