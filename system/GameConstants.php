<?php
/**
 * BF2Statistics ASP Management Asp
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

/**
 * Declare some constants
 *
 * These NUM_* constants should match the "NUM_*_TYPES" in the constants.py, with
 * a few exceptions as listed below.
 *
 * NOTE: For NUM_VEHICLES, only include a count up to the number sent in the snapshot.
 * By default, this number is only the first 7 vehicle types. VEHICLE_TYPE_SOLDIER,
 * VEHICLE_TYPE_NIGHTVISION, and VEHICLE_TYPE_GASMASK are not sent in snapshot. Do not
 * include VEHICLE_TYPE_PARACHUTE in the count either, as this is processed separately.
 *
 * NOTE: For NUM_WEAPONS, don't forget that NUM 9 is skipped in the constants.py!
 * Do not include the following weapon types in the count:
 *   - WEAPON_TYPE_TARGETING
 *   - WEAPON_TYPE_GRAPPLINGHOOK
 *   - WEAPON_TYPE_ZIPLINE
 *   - WEAPON_TYPE_TACTICAL
 */
define("NUM_ARMIES", 25);
define("NUM_KITS", 7);
define("NUM_VEHICLES", 7); // ** See Note Above
define("NUM_WEAPONS", 15); // ** See Note Above

/**
 * Defines the Weapon ID's of explosives in the DATABASE,
 * not the constants.py
 *
 * WEAPON_TYPE_C4, WEAPON_TYPE_CLAYMORE, WEAPON_TYPE_ATMINE
 *
 * Weapon Map in the database
 *
 * WEAPON_TYPE_ASSAULT         = 0
 * WEAPON_TYPE_ASSAULTGRN      = 1
 * WEAPON_TYPE_CARBINE         = 2
 * WEAPON_TYPE_LMG             = 3
 * WEAPON_TYPE_SNIPER          = 4
 * WEAPON_TYPE_PISTOL          = 5
 * WEAPON_TYPE_ATAA            = 6
 * WEAPON_TYPE_SMG             = 7
 * WEAPON_TYPE_SHOTGUN         = 8
 * WEAPON_TYPE_KNIFE           = 9
 * WEAPON_TYPE_SHOCKPAD        = 10
 * WEAPON_TYPE_C4              = 11
 * WEAPON_TYPE_HANDGRENADE     = 12
 * WEAPON_TYPE_CLAYMORE        = 13
 * WEAPON_TYPE_ATMINE          = 14
 * WEAPON_TYPE_TARGETING       = 15
 * WEAPON_TYPE_GRAPPLINGHOOK   = 16
 * WEAPON_TYPE_ZIPLINE         = 17
 * WEAPON_TYPE_TACTICAL        = 18
 */
const EXPLOSIVE_IDS = [11, 13, 14];