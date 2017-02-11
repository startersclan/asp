<?php
/**
 * BF2Statistics ASP Management Asp
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

namespace System;


use System\IO\Path;

class BackendAwardData
{
    /**
     * An array of all awards
     */
    public static $Awards = array(
        // Badges
        "kcb" => 1031406,   // Knife Combat Badge
        "pcb" => 1031619,   // Pistol Combat Badge
        "Acb" => 1031119,   // Assault Combat Badge
        "Atcb" => 1031120,  // Assault Combat Badge
        "Sncb" => 1031109,  // Sniper Combat Badge
        "Socb" => 1031115,  // Special Ops Combat Badge
        "Sucb" => 1031121,  // Support Combat Badge
        "Ecb" => 1031105,   // Engineer Combat Badge
        "Mcb" => 1031113,   // Medic Combat Badge
        "Eob" => 1032415,   // Explosive Ordinance Badge
        "Fab" => 1190601,   // First Aid Badge
        "Eb" => 1190507,    // Engineer Repair Badge
        "Rb" => 1191819,    // Resupply Badge
        "Cb" => 1190304,    // Command Badge
        "Ab" => 1220118,    // Armor Badge
        "Tb" => 1222016,    // Transport Badge
        "Hb" => 1220803,    // Helicopter Badge
        "Avb" => 1220122,   // Aviators Badge
        "adb" => 1220104,   // Air Defense Badge
        "Swb" => 1031923,   // Ground Defense Badge

        // Xpack Badges
        "X1Acb" => 1261119,     // Assault Specialist
        "X1Atcb" => 1261120,    // AntiTank Specialist
        "X1Sncb" => 1261109,    // Sniper Specialist
        "X1Socb" => 1261115,    // Special Ops Specialist
        "X1Sucb" => 1261121,    // Support Specialist
        "X1Ecb" => 1261105,     // Engineer Specialist
        "X1Mcb" => 1261113,     // Medical Specialist
        "X1fbb" => 1260602,     // Tactical Support Specialist
        "X1ghb" => 1260708,     // Grappling Hook Specialist
        "X1zlb" => 1262612,     // Zipline Specialist

        // Medals
        "ph" => 2191608,    // Purple Heart
        "Msm" => 2191319,   // Meritorious Service Medal
        "Cam" => 2190303,   // Combat Action Medal
        "Acm" => 2190309,   // Aviator Combat Medal
        "Arm" => 2190318,   // Armored Combat Medal
        "Hcm" => 2190308,   // Helicopter Combat Medal
        "gcm" => 2190703,   // Good Conduct Medal
        "Cim" => 2020903,   // Combat Infantry Medal
        "Mim" => 2020913,   // Marksman Infantry Medal
        "Sim" => 2020919,   // Sharpshooter Infantry Medal
        "Mvn" => 2021322,   // Medal of Valor
        "Dsm" => 2020419,   // Distinguished Service Medal
        "pmm" => 2021613,   // Peoples Medallion

        // Round Medals
        "erg" => 2051907,    // End of Round Gold
        "ers" => 2051919,    // End of Round Silver
        "erb" => 2051902,    // End of Round Bronze

        // Ribbons
        "Car" => 3240301,   // Combat Action Ribbon
        "Mur" => 3211305,   // Meritorious Unit Ribbon
        "Ior" => 3150914,   // Infantry Officer Ribbon
        "Sor" => 3151920,   // Staff Officer Ribbon
        "Dsr" => 3190409,   // Distingusihed Service Ribbon
        "Wcr" => 3242303,   // War College Ribbon
        "Vur" => 3212201,   // Valorous Unit Ribbon
        "Lmr" => 3241213,   // Legion Of Merrit
        "Csr" => 3190318,   // Crew Service Ribbon
        "Arr" => 3190118,   // Armored Ribbon
        "Aer" => 3190105,   // Aviator Ribbon
        "Hsr" => 3190803,   // Helicopter Service Ribbon
        "Adr" => 3040109,   // Airdefense Service Ribbon
        "Gdr" => 3040718,   // Ground Defense Service Ribbon
        "Ar" => 3240102,    // Airborne Ribbon
        "gcr" => 3240703,   // Good Conduct Ribbon

        // Xpack Ribbons
        "X1Csr" => 3260318,     // Crew Service Ribbon
        "X1Arr" =>3260118,     // Armored Service
        "X1Aer" => 3260105,     // Ariel Service
        "X1Hsr" => 3260803,     // Helo Specialist
    );

    /**
     * @var BackendAward[]
     */
    public static $BackendAwards = array();

    public static function Load()
    {
        if (empty(self::$BackendAwards))
        {
            /** @noinspection PhpIncludeInspection */
            self::$BackendAwards = include Path::Combine(SYSTEM_PATH, "config", "backendAwards.php");
        }
    }
}