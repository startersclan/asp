<?php
/*
    Copyright (C) 2006-2012  BF2Statistics

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Namespace
namespace System;

// No direct access
defined("BF2_ADMIN") or die("No Direct Access");

// Connect to the database
$connection = Database::GetConnection("stats");

// Prepare response
$Response = new AspResponse();
$Response->writeHeaderLine("ver", "now");
$Response->writeDataLine("0.1", time());
$Response->writeHeaderLine("id", "kit", "name", "descr");

// fetch all unlocks from the database
$stmt = $connection->query("SELECT `id`, `kit`, `name`, `desc` FROM `unlock` ORDER BY `id`");
while ($row = $stmt->fetch())
{
    $Response->writeDataLine($row['id'], $row['kit'], $row['name'], $row['desc']);
}

$Response->send();


/** Prepare output
$Response = new AspResponse();
$Response->writeHeaderLine("ver", "now");
$Response->writeDataLine("0.1", time());
$Response->writeHeaderLine("id", "kit", "name", "descr");
$Response->writeDataLine("11", "0", "Chsht_protecta", "Protecta shotgun with slugs");
$Response->writeDataLine("22", "1", "Usrif_g3a3", "H&K G3");
$Response->writeDataLine("33", "2", "USSHT_Jackhammer", "Jackhammer shotgun");
$Response->writeDataLine("44", "3", "Usrif_sa80", "SA-80");
$Response->writeDataLine("55", "4", "Usrif_g36c", "G36C");
$Response->writeDataLine("66", "5", "RULMG_PKM", "PKM");
$Response->writeDataLine("77", "6", "USSNI_M95_Barret", "Barret M82A2 (.50 cal rifle)");
$Response->writeDataLine("88", "1", "sasrif_fn2000", "FN2000");
$Response->writeDataLine("99", "2", "sasrif_mp7", "MP-7");
$Response->writeDataLine("111", "3", "sasrif_g36e", "G36E");
$Response->writeDataLine("222", "4", "usrif_fnscarl", "FN SCAR - L");
$Response->writeDataLine("333", "5", "sasrif_mg36", "MG36");
$Response->writeDataLine("444", "0", "eurif_fnp90", "P90");
$Response->writeDataLine("555", "6", "gbrif_l96a1", "L96A1");
$Response->send();
 */