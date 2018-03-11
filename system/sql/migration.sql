ALTER TABLE `mapinfo` RENAME TO `map`;
ALTER TABLE `round_history` RENAME TO `round`;

ALTER TABLE `player_army` MODIFY `id` TINYINT UNSIGNED NOT NULL AFTER `pid`;
ALTER TABLE `player_army` CHANGE `id` `army_id` TINYINT UNSIGNED NOT NULL;
ALTER TABLE `player_army` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

ALTER TABLE `player_award` MODIFY `id` MEDIUMINT UNSIGNED NOT NULL AFTER `pid`;
ALTER TABLE `player_award` CHANGE `id` `award_id` MEDIUMINT UNSIGNED NOT NULL;
ALTER TABLE `player_award` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;
ALTER TABLE `player_award` CHANGE `roundid` `round_id` INT UNSIGNED NOT NULL;

ALTER TABLE `player_map` CHANGE `mapid` `map_id` SMALLINT UNSIGNED NOT NULL;
ALTER TABLE `player_map` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

ALTER TABLE `player_kit` MODIFY `id` TINYINT UNSIGNED NOT NULL AFTER `pid`;
ALTER TABLE `player_kit` CHANGE `id` `kit_id` TINYINT UNSIGNED NOT NULL;
ALTER TABLE `player_kit` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

ALTER TABLE `player_history` CHANGE `roundid` `round_id` INT UNSIGNED NOT NULL;
ALTER TABLE `player_history` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

ALTER TABLE `player_rank_history` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

ALTER TABLE `player_unlock` MODIFY `unlockid` SMALLINT UNSIGNED NOT NULL AFTER `pid`;
ALTER TABLE `player_unlock` CHANGE `unlockid` `unlock_id` SMALLINT UNSIGNED NOT NULL;
ALTER TABLE `player_unlock` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

ALTER TABLE `player_vehicle` MODIFY `id` TINYINT UNSIGNED NOT NULL AFTER `pid`;
ALTER TABLE `player_vehicle` CHANGE `id` `vehicle_id` TINYINT UNSIGNED NOT NULL;
ALTER TABLE `player_vehicle` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

ALTER TABLE `player_weapon` MODIFY `id` TINYINT UNSIGNED NOT NULL AFTER `pid`;
ALTER TABLE `player_weapon` CHANGE `id` `weapon_id` TINYINT UNSIGNED NOT NULL;
ALTER TABLE `player_weapon` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

ALTER TABLE `risingstar` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

ALTER TABLE `battlespy_report` CHANGE `serverid` `server_id` SMALLINT UNSIGNED NOT NULL;
ALTER TABLE `battlespy_report` CHANGE `roundid` `round_id` INT UNSIGNED NOT NULL;

ALTER TABLE `battlespy_message` CHANGE `reportid` `report_id` INT UNSIGNED NOT NULL;
ALTER TABLE `battlespy_message` CHANGE `pid` `player_id` INT UNSIGNED NOT NULL;

ALTER TABLE `unlock` CHANGE `kit` `kit_id` TINYINT UNSIGNED NOT NULL;