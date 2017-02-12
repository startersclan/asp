<?php
use System\Database;
use System\IO\Directory;
use System\IO\Path;
use System\Response;
use System\View;

class Home
{
	public function index()
	{
	    // Require database connection
        $pdo = Database::GetConnection('stats');
        if ($pdo === false)
        {
            Response::Redirect('./install');
            die;
        }

	    // Create view
        $View = new View('index', 'home');
        $View->set('php_version', PHP_VERSION);
        $View->set('server_name', php_uname('s'));
        $View->set('server_version', apache_get_version() );
        $View->set('db_version', $pdo->query('SELECT version()')->fetchColumn(0));

	    // Get database size
        $size = 0;
        $q = $pdo->query("SHOW TABLE STATUS");
        if ($q instanceof PDOStatement)
        {
            while ($row = $q->fetch())
            {
                $size += $row["Data_length"] + $row["Index_length"];
            }
        }
        $View->set('db_size', number_format($size / (1024*1024), 2));

        // Games Processed
        $rounds = $pdo->query('SELECT COUNT(id) FROM round_history')->fetchColumn();
        $View->set('num_rounds', number_format($rounds));

        // Failed count
        $path = Path::Combine(SYSTEM_PATH, 'snapshots', 'unprocessed');
        $count = count(Directory::GetFiles($path));
        $View->set('failed_snapshots', number_format($count));

        // Number of players
        $result = $pdo->query("SELECT COUNT(id) FROM player");
        $View->set('num_players', number_format($result->fetchColumn(0)));

        // Number of Active players
        $result = $pdo->query("SELECT COUNT(id) FROM player WHERE lastonline > ". (time() - (86400 * 7)));
        $View->set('num_active_players', number_format($result->fetchColumn(0)));

        // Set arrow direction (with leading space) for active player count
        $View->set('active_raise', ' down');

        // Number of Active servers
        $result = $pdo->query("SELECT COUNT(id) FROM server WHERE lastupdate > ". (time() - (86400 * 7)));
        $View->set('num_active_servers', number_format($result->fetchColumn(0)));

        // Attach chart plotting scripts
        $View->attachScript("./frontend/js/demo.js");
        $View->attachScript("./frontend/js/flot/jquery.flot.min.js");

        // Draw View
		$View->render();
	}
}