<?php
/*
	Copyright (C) 2006-2013  BF2Statistics

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
// No direct access
if(!defined("BF2_ADMIN"))
    die("No Direct Access");

// Prepare output
$Response = new System\AspResponse();

// Make sure we have a type, and its valid
$type = (isset($_GET['type'])) ? $_GET['type'] : false;
if (!$type) 
{
    $Response->responseError(true);
    $Response->writeHeaderLine("asof", "err");
    $Response->writeDataLine(time(), "Invalid Syntax!");
    $Response->send();
}
else
{
    // Connect to the database
    $connection = System\Database::GetConnection("stats");

    // Prepare our output header
    $Response->writeHeaderLine("size", "asof");

	$id  = (isset($_GET['id'])) ? $_GET['id'] : false;
	$pid = (isset($_GET['pid'])) ? intval($_GET['pid']) : false;

	// Optional parameters
	$after  = (isset($_GET['after'])) ? intval($_GET['after']) : 0;
	$before = (isset($_GET['before'])) ? intval($_GET['before']) : 0;
	$pos    = (isset($_GET['pos'])) ? intval($_GET['pos']) : 1;
	$min    = ($pos - 1) - $before;
	$max    = $after + 1;
	$out    = "";
	
	if ($type == 'score')
	{
		if ($id == 'overall')
		{
			$result = $connection->query("SELECT COUNT(id) FROM player WHERE score > 0");
            $Response->writeDataLine($result->fetchColumn(), time());
            $Response->writeHeaderLine("n", "pid", "nick", "score", "totaltime", "playerrank", "countrycode");
			
			if(!$pid)
			{
				$query = "SELECT id, name, rank, country, time, score FROM player WHERE score > 0
				    ORDER BY score DESC, name DESC LIMIT ". $min .", ". $max;
				$result = $connection->query($query);
                if($result instanceof PDOStatement)
                {
                    while($row = $result->fetch())
                    {
                        $Response->writeDataLine(
                            $pos++,
                            $row['id'],
                            trim($row['name']),
                            $row['score'],
                            $row['time'],
                            $row['rank'],
                            strtoupper($row['country'])
                        );
                    }
                }
			}
			else
			{
				$query = "SELECT id, name, rank, country, time, score FROM player WHERE id = {$pid}";
				$result = $connection->query($query);
                if($result instanceof PDOStatement && ($row = $result->fetch()))
                {
                    $num = intval($connection->query("SELECT COUNT(id) FROM player WHERE score > {$row['score']}")->fetchColumn()) + 1;
                    $Response->writeDataLine(
                        $num,
                        $row['id'],
                        trim($row['name']),
                        $row['score'],
                        $row['time'],
                        $row['rank'],
                        strtoupper($row['country'])
                    );
                }
			}
		}
		elseif ($id == 'commander')
		{
			$result = $connection->query("SELECT COUNT(id) FROM player WHERE cmdscore > 0");
            $Response->writeDataLine($result->fetchColumn(), time());
            $Response->writeHeaderLine("n", "pid", "nick", "coscore", "cotime", "playerrank", "countrycode");
			
			if(!$pid)
			{
				$query = "SELECT id, name, rank, country, cmdtime, cmdscore FROM player WHERE cmdscore > 0"
                    ." ORDER BY cmdscore DESC, name DESC LIMIT {$min}, {$max}";
				$result = $connection->query($query);
                if($result instanceof PDOStatement)
                {
                    while($row = $result->fetch())
                    {
                        $Response->writeDataLine(
                            $pos++,
                            $row['id'],
                            trim($row['name']),
                            $row['cmdscore'],
                            $row['cmdtime'],
                            $row['rank'],
                            strtoupper($row['country'])
                        );
                    }
				}
			}
			else
			{
				$query = "SELECT id, name, rank, country, cmdtime, cmdscore FROM player WHERE id = {$pid}";
				$result = $connection->query($query);
                if($result instanceof PDOStatement && ($row = $result->fetch()))
                {
                    $num = intval($connection->query("SELECT COUNT(id) FROM player WHERE cmdscore > {$row['cmdscore']}")->fetchColumn()) + 1;
                    $Response->writeDataLine(
                        $num,
                        $row['id'],
                        trim($row['name']),
                        $row['cmdscore'],
                        $row['cmdtime'],
                        $row['rank'],
                        strtoupper($row['country'])
                    );
				}
			}
		}
		elseif ($id ==  'team')
		{
			$result = $connection->query("SELECT COUNT(id) FROM player WHERE teamscore > 0");
            $Response->writeDataLine($result->fetchColumn(), time());
            $Response->writeHeaderLine("n", "pid", "nick", "teamscore", "totaltime", "playerrank", "countrycode");
			
			if(!$pid)
			{
				$query = "SELECT id, name, rank, country, time, teamscore FROM player WHERE teamscore > 0
				    ORDER BY teamscore DESC, name DESC LIMIT ". $min .", ". $max;
				$result = $connection->query($query);
                if($result instanceof PDOStatement)
                {
                    while($row = $result->fetch())
                    {
                        $Response->writeDataLine(
                            $pos++,
                            $row['id'],
                            trim($row['name']),
                            $row['teamscore'],
                            $row['time'],
                            $row['rank'],
                            strtoupper($row['country'])
                        );
                    }
				}
			}
			else
			{
				$query = "SELECT id, name, rank, country, time, teamscore FROM player WHERE id = {$pid}";
				$result = $connection->query($query);
                if($result instanceof PDOStatement && ($row = $result->fetch()))
                {
                    $num = intval($connection->query("SELECT COUNT(id) FROM player WHERE teamscore > {$row['teamscore']}")->fetchColumn()) + 1;
                    $Response->writeDataLine(
                        $num,
                        $row['id'],
                        trim($row['name']),
                        $row['teamscore'],
                        $row['time'],
                        $row['rank'],
                        strtoupper($row['country'])
                    );
                }
			}
		}
		elseif ($id == 'combat')
		{
			$result = $connection->query("SELECT COUNT(id) FROM player WHERE skillscore > 0");
            $Response->writeDataLine($result->fetchColumn(), time());
            $Response->writeHeaderLine("n", "pid", "nick", "score", "totalkills", "totaltime", "playerrank", "countrycode");
			
			if(!$pid)
			{
				$query = "SELECT id, name, rank, country, time, kills, skillscore FROM player WHERE skillscore > 0
				    ORDER BY skillscore DESC, name DESC LIMIT ". $min .", ". $max;
				$result = $connection->query($query);
                if($result instanceof PDOStatement)
                {
                    while($row = $result->fetch())
                    {
                        $Response->writeDataLine(
                            $pos++,
                            $row['id'],
                            trim($row['name']),
                            $row['skillscore'],
                            $row['kills'],
                            $row['time'],
                            $row['rank'],
                            strtoupper($row['country'])
                        );
                    }
				}
			}
			else
			{
				$query = "SELECT id, name, rank, country, time, kills, skillscore FROM player WHERE id = {$pid}";
				$result = $connection->query($query);
                if($result instanceof PDOStatement && ($row = $result->fetch()))
                {
                    $num = intval($connection->query("SELECT COUNT(id) FROM player WHERE skillscore > {$row['skillscore']}")->fetchColumn()) + 1;
                    $Response->writeDataLine(
                        $num,
                        $row['id'],
                        trim($row['name']),
                        $row['skillscore'],
                        $row['kills'],
                        $row['time'],
                        $row['rank'],
                        strtoupper($row['country'])
                    );
                }
			}
		}
	}
	# Need weekly score calculations!
	elseif ($type == 'risingstar')
	{
		$query = "SELECT COUNT(DISTINCT(id)) FROM player_history WHERE score > 0 AND timestamp >= (UNIX_TIMESTAMP() - (60*60*24*7))";
		$result = $connection->query($query);
        $Response->writeDataLine($result->fetchColumn(), time());
        $Response->writeHeaderLine("n", "pid", "nick", "weeklyscore", "totaltime", "date", "playerrank", "countrycode");
		
		if(!$pid)
		{
			$query = "SELECT p.id, p.name, p.rank, p.country, p.time, sum(h.score) as weeklyscore, p.joined
				FROM player AS p JOIN player_history AS h ON p.id = h.id
				WHERE h.score > 0 AND h.timestamp >= (UNIX_TIMESTAMP() - (60*60*24*7))
				GROUP BY p.id
				ORDER BY weeklyscore DESC, name DESC
				LIMIT ". $min .", ". $max;
			$result = $connection->query($query);
            if($result instanceof PDOStatement)
            {
                while($row = $result->fetch())
                {
                    $Response->writeDataLine(
                        $pos++,
                        $row['id'],
                        trim($row['name']),
                        $row['weeklyscore'],
                        $row['time'],
                        date('m/d/y h:i:00 A', $row['joined']),
                        $row['rank'],
                        strtoupper($row['country'])
                    );
                } 
			}
		}
		else
		{
			$query = "SELECT p.id, p.name, p.rank, p.country, p.time, sum(h.score) as weeklyscore, p.joined
				FROM player AS p JOIN player_history AS h ON p.id = h.id
				WHERE h.score > 0 AND h.timestamp >= (UNIX_TIMESTAMP() - (60*60*24*7))
				GROUP BY p.id
				ORDER BY weeklyscore DESC, name DESC";
			$result = $connection->query($query);
            if($result instanceof PDOStatement)
            {
                while($row = $result->fetch())
                {
                    if($row['id'] == $pid)
                    {
                        $Response->writeDataLine(
                            $pos++,
                            $row['id'],
                            trim($row['name']),
                            $row['weeklyscore'],
                            $row['time'],
                            date('m/d/y h:i:00 A', $row['joined']),
                            $row['rank'],
                            strtoupper($row['country'])
                        );
                        break;
                    }
                    $pos++;
                }
			}
		}
	}
	elseif ($type == 'kit')
	{
		$result = $connection->query("SELECT COUNT(id) FROM kits WHERE kills{$id} > 0");
        $Response->writeDataLine($result->fetchColumn(), time());
        $Response->writeHeaderLine("n", "pid", "nick", "killswith", "deathsby", "timeplayed", "playerrank", "countrycode");
		
		if(!$pid)
		{
			$query = "SELECT player.id AS plid, name, rank, country, kills{$id} AS kills, deaths{$id} AS deaths,
			    time{$id} AS time FROM player NATURAL JOIN kits WHERE kills{$id} > 0
			    ORDER BY kills{$id} DESC, name DESC LIMIT ". $min .", ". $max;
			$result = $connection->query($query);
            if($result instanceof PDOStatement)
            {
                while($row = $result->fetch())
                {
                    $Response->writeDataLine(
                        $pos++,
                        $row['plid'],
                        trim($row['name']),
                        $row['kills'],
                        $row['deaths'],
                        $row['time'],
                        $row['rank'],
                        strtoupper($row['country'])
                    );
                }
			}
		}
		else
		{
			$query = "SELECT player.id AS plid, name, rank, country, kills{$id} AS kills, deaths{$id} AS deaths,
			    time{$id} AS time FROM player NATURAL JOIN kits WHERE kills{$id} > 0
			    ORDER BY kills{$id} DESC, name DESC";
			$result = $connection->query($query);
            if($result instanceof PDOStatement)
            {
                while($row = $result->fetch())
                {
                    if($row['plid'] == $pid)
                    {
                        $Response->writeDataLine(
                            $pos++,
                            $row['plid'],
                            trim($row['name']),
                            $row['kills'],
                            $row['deaths'],
                            $row['time'],
                            $row['rank'],
                            strtoupper($row['country'])
                        );
                        break;
                    }
                    $pos++;
                }
			}
		}
	}
	elseif ($type == 'vehicle')
	{
		$result = $connection->query("SELECT COUNT(id) FROM vehicles WHERE kills{$id} > 0");
        $Response->writeDataLine($result->fetchColumn(), time());
        $Response->writeHeaderLine("n", "pid", "nick", "killswith", "deathsby", "timeused", "playerrank", "countrycode");
		
		if(!$pid)
		{
			$query = "SELECT player.id AS plid, name, rank, country, kills{$id} AS kills, deaths{$id} AS deaths,
			    time{$id} AS time FROM player NATURAL JOIN vehicles WHERE kills{$id} > 0
			    ORDER BY kills{$id} DESC, name DESC LIMIT ". $min .", ". $max;
			$result = $connection->query($query);
            if($result instanceof PDOStatement)
            {
                while($row = $result->fetch())
                {
                    $Response->writeDataLine(
                        $pos++,
                        $row['plid'],
                        trim($row['name']),
                        $row['kills'],
                        $row['deaths'],
                        $row['time'],
                        $row['rank'],
                        strtoupper($row['country'])
                    );
                }
			}
		}
		else
		{
			$query = "SELECT player.id AS plid, name, rank, country, kills{$id} AS kills, deaths{$id} AS deaths,
			    time{$id} AS time FROM player NATURAL JOIN vehicles WHERE kills{$id} > 0
			    ORDER BY kills{$id} DESC, name DESC";
			$result = $connection->query($query);
            if($result instanceof PDOStatement)
            {
                while($row = $result->fetch())
                {
                    if($row['plid'] == $pid)
                    {
                        $Response->writeDataLine(
                            $pos++,
                            $row['plid'],
                            trim($row['name']),
                            $row['kills'],
                            $row['deaths'],
                            $row['time'],
                            $row['rank'],
                            strtoupper($row['country'])
                        );
                        break;
                    }
                    $pos++;
                }
			}	
		}
	}
	elseif ($type == 'weapon')
	{
		$result = $connection->query("SELECT COUNT(id) FROM weapons WHERE kills{$id} > 0");
        $Response->writeDataLine($result->fetchColumn(), time());
        # NOTE: EA typo (deathsby=detahsby)
        $Response->writeHeaderLine("n", "pid", "nick", "killswith", "detahsby", "timeused", "accuracy", "playerrank", "countrycode");
        
		if (!$pid)
		{
			$query = "SELECT player.id AS plid, name, rank, country, kills{$id} AS kills, deaths{$id} AS deaths,
			    time{$id} AS time, hit{$id} AS hit, fired{$id} AS fired FROM player
			    NATURAL JOIN weapons WHERE kills{$id} > 0 ORDER BY kills{$id} DESC, name DESC LIMIT ". $min .", ". $max;
			$result = $connection->query($query);
            if($result instanceof PDOStatement)
            {
                while($row = $result->fetch())
                {
                    $Response->writeDataLine(
                        $pos++,
                        $row['plid'],
                        trim($row['name']),
                        $row['kills'],
                        $row['deaths'],
                        $row['time'],
                        @number_format(($row['hit'] / $row['fired']) * 100),
                        $row['rank'],
                        strtoupper($row['country'])
                    );
                }
			}
		}
		else
		{
			$query = "SELECT player.id AS plid, name, rank, country, kills{$id} AS kills, deaths{$id} AS deaths,
			    time{$id} AS time, hit{$id} AS hit, fired{$id} AS fired FROM player NATURAL JOIN weapons
			    WHERE kills{$id} > 0 ORDER BY kills{$id} DESC, name DESC";
			$result = $connection->query($query);
            if($result instanceof PDOStatement)
            {
                while($row = $result->fetch())
                {
                    if($row['plid'] == $pid)
                    {
                        $Response->writeDataLine(
                            $pos++,
                            $row['plid'],
                            trim($row['name']),
                            $row['kills'],
                            $row['deaths'],
                            $row['time'],
                            @number_format(($row['hit'] / $row['fired']) * 100),
                            $row['rank'],
                            strtoupper($row['country'])
                        );
                        break;
                    }
                    $pos++;
                }
			}
		}
	}
	else 
	{
		print 'Unknown type!';
	}

	// Send Output
    $Response->send();
}