<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<!-- Apple iOS and Android stuff (do not remove) -->
	<meta name="apple-mobile-web-app-capable" content="no" />
	<meta name="apple-mobile-web-app-status-bar-style" content="black" />

	<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no,maximum-scale=1" />

	<!-- Required Stylesheets -->
	<link rel="icon" type="image/png" href="frontend/images/icons/bf2.png">
	<link rel="stylesheet" type="text/css" href="frontend/css/reset.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="frontend/css/text.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="frontend/css/fonts/ptsans/stylesheet.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="frontend/css/fluid.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="frontend/css/mws.style.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="frontend/css/icons/24x24.css" media="screen" />

	<!-- Plugin Stylesheets -->
	<link rel="stylesheet" type="text/css" href="frontend/plugins/tipsy/tipsy.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="frontend/plugins/jgrowl/jquery.jgrowl.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="frontend/css/jui/jquery.ui.css" media="screen" />

	<!-- Theme Stylesheet -->
	<link rel="stylesheet" type="text/css" href="frontend/css/bf2.theme.css" media="screen" />

	<!-- JavaScript Plugins -->
	<script type="text/javascript" src="frontend/js/jquery-1.7.1.min.js"></script>
	<script type="text/javascript" src="frontend/js/jquery.mousewheel.js"></script>
	<script type="text/javascript" src="frontend/js/jquery.form.js"></script>

	<!-- jQuery-UI Dependent Scripts -->
	<script type="text/javascript" src="frontend/js/jquery-ui.js"></script>
	<script type="text/javascript" src="frontend/js/jquery.ui.touch-punch.min.js"></script>

	<!-- Plugin Scripts -->
	<script type="text/javascript" src="frontend/plugins/jgrowl/jquery.jgrowl-min.js"></script>
	<script type="text/javascript" src="frontend/plugins/datatables/jquery.dataTables-min.js"></script>

	<!--[if lt IE 9]>
	<script type="text/javascript" src="frontend/plugins/excanvas.min.js"></script>
	<![endif]-->
	<script type="text/javascript" src="frontend/plugins/tipsy/jquery.tipsy-min.js"></script>
	<script type="text/javascript" src="frontend/plugins/placeholder/jquery.placeholder-min.js"></script>
	<script type="text/javascript" src="frontend/plugins/validate/jquery.validate-min.js"></script>

	<!-- Core Script -->
	<script type="text/javascript" src="frontend/js/mws.js"></script>
	
	{VIEW_CSS}
	{VIEW_JS}

	<title>Private Stats Admin</title>
</head>
<body>
	<!-- Header -->
	<div id="mws-header" class="clearfix">
    
    	<!-- Logo Container -->
    	<div id="mws-logo-container">
        
        	<!-- Logo Wrapper, images put within this wrapper will always be vertically centered -->
        	<div id="mws-logo-wrap">
            	<img id="logo" src="frontend/images/bf2logo.png" alt="BF2 Private Stats Admin" />
			</div>
        </div>
		
		<div id="title">Private Stats Admin</div>
		<div id="dbver">Code Version: <?php echo CODE_VER; ?> || Database Version: <?php echo DB_VER ?> </div>
        
    </div>
    
    <!-- Start Main Wrapper -->
    <div id="mws-wrapper">
    
    	<!-- Necessary markup, do not remove -->
		<div id="mws-sidebar-stitch"></div>
		<div id="mws-sidebar-bg"></div>
        
        <!-- Sidebar Wrapper -->
        <div id="mws-sidebar">
            
            <!-- Main Navigation -->
            <div id="mws-navigation">
            	<ul>
                	<?php build_navigation(); ?>
                </ul>
            </div>            
        </div>
        
        <!-- Main Container Start -->
        <div id="mws-container" class="clearfix">
			
			<!-- Inner Container Start -->
            <div class="container">
			<?php 
				// Process DB version messages
				if(DB_VER == '0.0.0') 
				{
					if($_GET['task'] !== 'installdb' && $_GET['task'] !== 'editconfig')
					{
						echo '<div class="alert global">Unable to establish a database connection. If you need to setup the ASP, 
						<a href="?task=installdb">Click Here to begin Installation</a></div>';
					}
				} 
				elseif(DB_VER !== CODE_VER)
				{
					echo '<div class="alert global">Database is outdated. Please <a href="?task=upgradedb">Click Here</a> to upgrade your database to the corrent version</div>';
				}
			?>