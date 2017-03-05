<?php
/*
    Copyright (C) 2006-2017  BF2Statistics

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