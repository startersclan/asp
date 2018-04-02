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
-- Alter these 2 views to include the Map Display Name
--
CREATE OR REPLACE VIEW `round_history_view` AS
  SELECT h.id AS `id`, mi.displayname AS `map`, h.time_end AS `round_end`, h.team1 AS `team1`, h.team2 AS `team2`, h.winner AS `winner`,
         h.pids1 + h.pids2 AS `players`, GREATEST(h.tickets1, h.tickets2) AS `tickets`, s.name AS `server`
  FROM `round` AS h
    LEFT JOIN map AS mi ON h.map_id = mi.id
    LEFT JOIN `server` AS s ON h.server_id = s.id;

CREATE OR REPLACE VIEW `player_history_view` AS
  SELECT ph.*, mi.name AS mapname, mi.displayname AS map_display_name, server.name AS name, rh.time_end, rh.pids1_end + rh.pids2_end AS `playerCount`
  FROM player_round_history AS ph
    LEFT JOIN round AS rh ON ph.round_id = rh.id
    LEFT JOIN server ON rh.server_id = server.id
    LEFT JOIN map AS mi ON rh.map_id = mi.id;

--
-- Always update version table!!!
--
INSERT INTO `_version`(`updateid`, `version`) VALUES (30010, '3.0.1');