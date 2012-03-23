<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// An array of the tables to install

$install_sql['directus_media'] = "CREATE TABLE IF NOT EXISTS `directus_media` (
  `id` int(10) NOT NULL auto_increment,
  `active` tinyint(1) NOT NULL default '1',
  `user` varchar(255) NOT NULL default '',
  `uploaded` datetime NOT NULL default '0000-00-00 00:00:00',
  `title` varchar(255) NOT NULL default '',
  `source` varchar(255) NOT NULL default '',
  `file_name` varchar(255) NOT NULL default '',
  `type` varchar(50) NOT NULL default '',
  `extension` varchar(10) NOT NULL default '',
  `caption` text NOT NULL,
  `location` varchar(255) NOT NULL default '',
  `tags` varchar(255) NOT NULL default '',
  `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
  `width` int(5) NOT NULL default '0',
  `height` int(5) NOT NULL default '0',
  `file_size` int(20) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";



$install_sql['directus_messages'] = "CREATE TABLE IF NOT EXISTS `directus_messages` (
  `id` int(10) NOT NULL auto_increment,
  `active` tinyint(1) NOT NULL default '1',
  `subject` varchar(255) NOT NULL default '',
  `message` text NOT NULL,
  `datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `reply` int(10) NOT NULL default '0',
  `from` int(10) NOT NULL default '0',
  `to` varchar(255) NOT NULL default '',
  `viewed` varchar(255) NOT NULL default ',',
  `archived` varchar(255) NOT NULL default ',',
  `table` varchar(255) NOT NULL default '',
  `row` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";



$install_sql['directus_preferences'] = "CREATE TABLE IF NOT EXISTS `directus_preferences` (
  `id` int(10) NOT NULL auto_increment,
  `user` int(10) NOT NULL default '0',
  `type` varchar(250) NOT NULL default '',
  `name` varchar(250) NOT NULL default '',
  `value` varchar(250) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";



$install_sql['directus_activity'] = "CREATE TABLE IF NOT EXISTS `directus_activity` (
  `id` int(10) NOT NULL auto_increment,
  `active` tinyint(1) NOT NULL default '1',
  `table` varchar(100) NOT NULL default '',
  `row` varchar(100) NOT NULL default '',
  `type` varchar(100) NOT NULL default '',
  `datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `user` int(10) NOT NULL default '0',
  `sql` longtext NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";



$install_sql['directus_settings'] = "CREATE TABLE IF NOT EXISTS `directus_settings` (
  `id` int(10) NOT NULL auto_increment,
  `active` tinyint(1) NOT NULL default '1',
  `type` varchar(255) NOT NULL default '',
  `option` varchar(255) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  `option_2` varchar(255) NOT NULL default '',
  `value_2` tinytext NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";



$install_sql['directus_users'] = "CREATE TABLE IF NOT EXISTS `directus_users` (
  `id` tinyint(10) NOT NULL auto_increment,
  `active` tinyint(1) NOT NULL default '1',
  `first_name` varchar(50) NOT NULL default '',
  `last_name` varchar(50) NOT NULL default '',
  `password` varchar(255) NOT NULL default '',
  `token` varchar(255) NOT NULL default '',
  `reset_token` varchar(255) NOT NULL default '',
  `reset_expiration` datetime NOT NULL default '0000-00-00 00:00:00',
  `email` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `admin` tinyint(1) NOT NULL default '0',
  `media` tinyint(1) NOT NULL default '1',
  `notes` tinyint(1) NOT NULL default '1',
  `editable` tinyint(1) NOT NULL default '1',
  `email_messages` tinyint(1) NOT NULL default '1',
  `view` text NOT NULL,
  `add` text NOT NULL,
  `edit` text NOT NULL,
  `reorder` text NOT NULL,
  `delete` text NOT NULL,
  `last_login` datetime NOT NULL default '0000-00-00 00:00:00',
  `last_page` varchar(255) NOT NULL default '',
  `ip` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";


$install_sql['demo_table'] = "CREATE TABLE `demo_table` (
  `id` int(10) NOT NULL auto_increment,
  `active` tinyint(1) NOT NULL default '1',
  `sort` int(10) NOT NULL default '0',
  `text_field` varchar(255) NOT NULL default '',
  `text` text NOT NULL,
  `checkbox` tinyint(1) NOT NULL default '0',
  `date` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Save terms acceptance as cookie

if($_POST['terms'] == 'accepted'){
	setcookie("terms", "accepted", time()+36000);
	header( 'location: install.php' );
}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Attempt to set requirements (Server API: apache can use htaccess / cgi can use php.ini)

ini_set('session.auto_start', 0);

// Reverse the effects of Magic Quotes
@ini_set('magic_quotes_runtime', 0);
@ini_set('magic_quotes_sybase', 0);
if( in_array( strtolower( ini_get( 'magic_quotes_gpc' ) ), array( '1', 'on' ) ) ){
	$_POST = array_map( 'stripslashes', $_POST );
	$_GET = array_map( 'stripslashes', $_GET );
	$_COOKIE = array_map( 'stripslashes', $_COOKIE );
}

// Trim all POSTs (doesn't work with arrays)
$_POST = array_map( 'trim', $_POST );

// Get this path
$directus_path = dirname("http" . ((!empty($_SERVER['HTTPS'])) ? "s" : "") . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']) . '/';

// Set the timezone for datetimes
if(function_exists('date_default_timezone_set')){
	date_default_timezone_set( 'UTC' );
}

// Error logging
$errors = array();


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Run the server requirements test on every page!

// Check PHP version: 
if(version_compare(phpversion(), '5.1.0', '<')){ $errors[] = '<b>PHP 5.1 or greater</b> (You: PHP '. phpversion() .')'; }

// Check MySQL exists: 
if(!extension_loaded('mysql')){ $errors[] = '<b>MySQL</b> - You don\'t seem to have MySQL installed'; } 	// Can't check version since we arent connected yet

// Check if file uploads are on
if(!ini_get('file_uploads')){ $errors[] = '<b>File Uploads</b> - You don\'t seem to have file uploads enabled'; } 	// Entry can be set in php.ini or httpd.conf

// Check session autostart
if(ini_get('session.auto_start')){ $errors[] = '<b>Session Auto Start</b> - This needs to be off'; } 	// Entry can be set anywhere

// Check register globals
if(ini_get('register_globals')){ $errors[] = '<b>Register Globals</b> - This needs to be off'; }	// Entry can be set in php.ini, .htaccess or httpd.conf

// Check Safe Mode
if(ini_get('safe_mode')){ $errors[] = '<b>Safe Mode</b> - This needs to be off'; } 	// Entry can be set in php.ini or httpd.conf

// Check Magic Quotes
//if(ini_get('magic_quotes_gpc')){ $errors[] = '<b>Magic Quotes</b> - This needs to be off'.get_magic_quotes_gpc(); } 	// Unsure how to turn off since server API (CGI or Apache) is unknown

// Check GD Library
if(!extension_loaded('gd')){ $errors[] = '<b>GD Library</b> - You\'ll need this for media'; }

// Check cURL
if(!function_exists('curl_init')){ $errors[] = '<b>cURL</b> - You\'ll need this for media'; }


// Set permissions (attempt)
@chmod("inc/config.php", 0755);
@chmod("inc/backups/", 0755);
@chmod("media/cms_thumbs/", 0755);
@chmod("media/temp/", 0755);
@chmod("media/users/", 0755);
if(file_exists('../media/files/')){
	@chmod("../media/files/", 0755);
} else {
	@mkdir("../media/files/", 0755, true);
	@chmod("../media/files/", 0755);
}


// Check permissions
if(!is_writable('media/temp/')){ $errors[] = '<b><u>directus</u>/media/temp/ Folder</b> - Must be writable'; }
if(!is_writable('media/cms_thumbs/')){ $errors[] = '<b><u>directus</u>/media/cms_thumbs/ Folder</b> - Must be writable'; }
if(!is_writable('media/users/')){ $errors[] = '<b><u>directus</u>/media/users/ Folder</b> - Must be writable'; }
if(!is_writable('inc/backups/')){ $errors[] = '<b><u>directus</u>/inc/backups/ Folder</b> - Must be writable'; }


// Did the server pass all the requirements?
$meets_requirements = (count($errors) == 0)? true : false;


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Run the database test and/or connect to database

if($meets_requirements && $_COOKIE['terms'] == 'accepted'){
	
	// Check if config file already exists
	if(file_exists("inc/config.php")){
		
		// Connect to existing config file
		require_once("inc/config.php");
		
		// Get the values from the existing config file
		$config_data = file_get_contents("inc/config.php");
		$config_array = explode('"',$config_data);
		$db_server = $config_array[1];
		$db_username = $config_array[3];
		$db_password = $config_array[5];
		$db_database = $config_array[7];
		$db_prefix = $config_array[9];
		
	}
	
	
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// If server fails let's set it up!
	
	if(!$server_success){
		
		// Get variables from user
		if($_POST['save_config']){
			$db_server = addslashes($_POST['db_server']);
			$db_username = addslashes($_POST['db_username']);
			$db_password = addslashes($_POST['db_password']);		
			$db_database = addslashes($_POST['db_database']);
			$db_prefix = addslashes($_POST['db_prefix']);
		}
		
		// Test server and database connection
		try{
			$dbh = new PDO("mysql:host=$db_server;dbname=$db_database;charset=UTF8", $db_username, $db_password);
			$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );
		} catch(PDOException $e) {
			// Log the error
			@file_put_contents(realpath(dirname(__FILE__)) . '/inc/directus_log.txt', date("Y-m-d H:i:s", time()-date("Z",time())) . ' - INSTALL: ' . $e->getMessage() . "\n", FILE_APPEND);
			$errors[] = "Couldn't connect to server";
		}
		
		if($db_server == ""){
			$errors[] = "Please enter a database host";
		}
		
		if($db_username == ""){
			$errors[] = "Please enter a database username";
		}
		
		if($db_database == ""){
			$errors[] = "Please enter a database name";
		}
		
		// If connection worked, let's save it
		if(count($errors) == 0){
			
			// Create random session name
		    srand((double)microtime()*1000000);
		    $i = 0;
		    $session_key = '';
		    while ($i < 4) {
		        $num = rand() % 33;
		        $tmp = substr("ABCDEFGHIJKMNOPQRSTUVWXYZ023456789", $num, 1);
		        $session_key = $session_key . $tmp;
		        $i++;
		    }
    
			//////////////////////////////////////////////////////////////////////////////
			// The new config file content (could be \ns but this is easier to read)
			
			$install_config = '<?PHP
$db_server = "' . $db_server . '";
$db_username = "' . $db_username . '";
$db_password = "' . $db_password . '";		
$db_database = "' . $db_database . '";
$db_prefix = "' . $db_prefix . '";

$directus_path = "' . $directus_path . '";

$cms_debug = false;

session_name("DIRECTUS_'.$session_key.'");
session_start();
header("Content-Type: text/html; charset=utf-8");

try{
$dbh = new PDO("mysql:host=$db_server;dbname=$db_database;charset=UTF8", $db_username, $db_password);
$dbh->exec("SET CHARACTER SET utf8");
$dbh->query("SET NAMES utf8");

if($cms_debug){
	$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );	// Dev
} else {
	$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT );	// Live
}

// Tell other files we have connected
$server_success = true;

} catch(PDOException $e) {
// Log the error
file_put_contents(substr(realpath(dirname(__FILE__)), 0, -3) . "inc/directus_log.txt", date("Y-m-d H:i:s", time()-date("Z",time())) . " - " . $e->getMessage() . "\n", FILE_APPEND);
}

?>';
			
			//////////////////////////////////////////////////////////////////////////////
			// Save the details into the config file
			
			if(!file_put_contents("inc/config.php", $install_config)){
				$errors[] = "Couldn't save your config file, ensure directus/inc/ write permissions"; 
			} else {
				$server_success = true;
			}
			
			//////////////////////////////////////////////////////////////////////////////
			// Update the CHARSET for the database to UTF8
			
			$dbh->query("alter database $db_database charset=utf8 COLLATE utf8_general_ci");
			
		}
	}



	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// If server doesn't fail then let's add to it
	
	if($server_success){
	
		// Check if tables exist and are empty, otherwise create them
		foreach($install_sql as $key => $value){
			
			// Check if table already exists
			$sth = $dbh->query("SHOW TABLES LIKE '$key'");
			if($sth->rowCount() > 0){
				
				// Check if table has rows already
				$sth_inner = $dbh->query("SELECT * FROM `$key`");
				$rows = $sth_inner->rowCount();
				if($rows>0){
					//$s = ($rows==1)?'':'s';
					//$errors[] = "<b>$key</b> already exists with $rows item$s";
				}
			} else {
				
				// Add the demo table only if the user wants it
				if($key != "demo_table" || $_POST['demo_table']){
					// Add the table
					if(!$dbh->query($value)){
						$errors[] = "<b>$key</b> could not be installed";
					}
				}
			}
		}
		
		
		// If we now have all the tables we can continue
		if(count($errors) == 0){
			
			$requires_settings = false;
			
			// Check if there is at least a full admin and basic settings
			$sth_inner = $dbh->query("SELECT * FROM `directus_settings` WHERE `active` = '1' AND `type` = 'cms' AND (`option` = 'site_name' OR `option` = 'site_url') ");
			$rows = $sth_inner->rowCount();
			if($rows < 2){
				$requires_settings = true;
			}
			
			$sth_inner = $dbh->query("SELECT * FROM `directus_users` WHERE `active` = '1' AND `admin` = '1' AND `email` != '' AND `password` != '' ");
			$rows = $sth_inner->rowCount();
			if($rows == 0){
				$requires_settings = true;
			}
			
			
			
			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// Requires Settings
			
			if($requires_settings && $_POST['save_info']){
				
				// Validate input
				if(!$_POST['site_name'] || !$_POST['site_url']){
					$errors[] = "Site name and URL are required";
				}
				
				if(!$_POST["first_name"] || !preg_match("/^[A-Za-z]+(?:[ -][A-Za-z]+)*$/", $_POST["first_name"])){
					$errors[] = 'First name is required';
				}
				if(!$_POST["last_name"] || !preg_match("/^[A-Za-z]+(?:[ -][A-Za-z]+)*$/", $_POST["last_name"])){
					$errors[] = 'Last name is required';
				}
				if($_POST["password"] != $_POST["password_confirm"]){
					$errors[] = 'Passwords must match';
				}
				if(!$_POST["password"] || strlen($_POST["password"]) < 3){ // !preg_match("/^[A-Za-z0-9_@!#$%^&*]{3,}$/", $_POST["password"])
					$errors[] = 'Password must be at least 3 characters';
				}
				if(!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $_POST["email"])){
					$errors[] = 'Email is not a valid address';
				}
				
				if(count($errors) == 0){
				
					require_once("inc/functions.php");
				
					//////////////////////////////////////////////////////////////////////////////
					// Save basic settings (WILL HAVE TO SAVE SETTINGS PAGE AT LEAST ONCE TO FINISH INSTALL)
				
					if(!$dbh->query("UPDATE `directus_settings` SET `active` = active+2 ")){
						$errors[] = "Couldn't backup settings";
					}
					
					$sth = $dbh->prepare("INSERT INTO `directus_settings` (`type`, `option`, `value`, `option_2`, `value_2`) VALUES ('cms', 'site_name', :site_name, '', ''), ('cms', 'site_url', :site_url, '', '') ");
					$sth->bindParam(':site_name', $_POST['site_name']);
					$sth->bindParam(':site_url', $_POST["site_url"]);
					if(!$sth->execute()){ 
						$errors[] = "Couldn't save settings";
					}
					
					//////////////////////////////////////////////////////////////////////////////
					// Add first user to database
					
					if(!$dbh->query("TRUNCATE `directus_users` ")){
						$errors[] = "Couldn't reset users";
					}
					
					$hasher = new PasswordHash(8, FALSE);
					
					$sth = $dbh->prepare("INSERT INTO `directus_users` (`id`, `active`, `first_name`, `last_name`, `password`, `email`, `description`, `admin`, `media`, `notes`, `editable`, `email_messages`, `view`, `add`, `edit`, `reorder`, `delete`, `last_login`, `last_page`, `ip`) VALUES (1, 1, :first_name, :last_name, :password, :email, 'Admin', 1, 1, 1, 1, 1, 'all', 'all', 'all', 'all', 'all', '', '', '') ");
					$sth->bindParam(':first_name', $_POST['first_name']);
					$sth->bindParam(':last_name', $_POST['last_name']);
					$sth->bindParam(':password', $hasher->HashPassword($_POST["password"]));
					$sth->bindParam(':email', $_POST["email"]);
					if(!$sth->execute()){
						$errors[] = "Couldn't save user";
					}
					
					//////////////////////////////////////////////////////////////////////////////
					// Add install date to activity
					
					$sth = $dbh->prepare("INSERT INTO directus_activity (`active`,`type`, `datetime`, `user`) VALUES ('1', 'installed', :datetime, '1') ");
					$sth->bindValue(':datetime', date("Y-m-d H:i:s", time()-date("Z",time())) );
					if(!$sth->execute()){
						$errors[] = "Couldn't save activity";
					}
					
					
					if(count($errors) == 0){
						//////////////////////////////////////////////////////////////////////////////
						// Send account creation email here
						
						$body = "Congratulations on installing Directus!\n\nPassword for ".$_POST["first_name"]." ".$_POST["last_name"].":\n".$_POST["password"]."\n\nLogin for ".addslashes($_POST['site_name']).":\n".$directus_path."\n\n\n--\nDirectus";
						
						$sent = send_email($subject = "Directus Setup Complete!", $body, $to = $_POST['email'], $from = false, $bcc = false);
						
						if(!$sent) {
							$errors[] = "Error sending setup email";
						}
						
						// Everything is all set!
						$requires_settings = false;
						
					}
					
				} // End of "Input Validation"
			} // End of "Requires Settings"
		} // End of "Tables All Exist"
	} // End of "Server Doesn't Fail"
} // End of "Meet Requirements"



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en-US">

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />

	<title>Directus &mdash; Install</title>
	
	<link rel="shortcut icon" href="<?PHP echo $directus_path;?>media/site/favicon.ico">
	
	<script type="text/javascript" src="inc/js/jquery.js"></script>
	<script type="text/javascript" src="inc/js/jquery-ui.js"></script>
	<script type="text/javascript" src="inc/js/directus.js"></script>
	
	<script>
		$(document).ready(function(){
			
			$('#install_terms').change(function(){
				if($(this).attr('checked')){
					$("#install_terms_button").addClass('color').removeClass('disabled');
				} else {
					$("#install_terms_button").addClass('disabled').removeClass('color');
				}
			});
			
			$("#install_terms_button").click(function(event){
				if( $('#install_terms').is(":checked") ){
					$("#terms_form").submit();
				} else {
					alert('You must agree to the terms of this agreement to continue');
				}
				return false;
			});
			
			$('#try_again').click(function(event){
				window.location.reload(true);
				return false;
			});
			
			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// Check password strength
			
			$('#check_strength').keyup(function(e) {
				var strongRegex = new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$", "g");
				var mediumRegex = new RegExp("^(?=.{7,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$", "g");
				var enoughRegex = new RegExp("(?=.{7,}).*", "g");
				if (false == enoughRegex.test($(this).val())) {
					$('#password_strength').attr('class', 'weak');
					$('#password_strength').html('More Characters');
				} else if (strongRegex.test($(this).val())) {
					$('#password_strength').attr('class', 'strong');
					$('#password_strength').html('Strong');
				} else if (mediumRegex.test($(this).val())) {
					$('#password_strength').attr('class', 'medium');
					$('#password_strength').html('Medium');
				} else {
					$('#password_strength').attr('class', 'weak');
					$('#password_strength').html('Weak');
				}
				return true;
			});
			
			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// Check that passwords match
			
			$('#check_strength, #password_confirm').keyup(function(e) {
				if($('#password_confirm').val() != ""){
					if($('#check_strength').val() != $('#password_confirm').val()){
						$('#password_match').text("Passwords do not match");
						$('#password_match').attr('class', 'no_match');
					} else {
						$('#password_match').text("Password confirmed");
						$('#password_match').attr('class', 'match');
					}
				} else {
					$('#password_match').text("");
					$('#password_match').attr('class', '');
				}
				return true;
			});
			
		});
	</script>
	
	<link rel="stylesheet" href="inc/css/directus.css" type="text/css" media="screen" title="" charset="utf-8">
	<link rel="stylesheet" href="inc/css/cms_colors/green.css" type="text/css" media="screen" title="" charset="utf-8">
	
	<style type="text/css">
	
		body,html {
			background: #ededed;
		}
	
		#page_install {
			display: block;
			background: #fff;
			padding: 24px;
			border: 1px solid #c9c9c9;
			border-radius: 4px;
			-webkit-border-radius: 4px;
			-moz-border-radius: 4px;
			box-shadow: rgba(0, 0, 0, 0.25) 0px 0px 9px;
			-webkit-box-shadow: rgba(0, 0, 0, 0.25) 0px 0px 9px;
			-moz-box-shadow: rgba(0, 0, 0, 0.25) 0px 0px 9px;
			width: 700px;
			margin: 48px auto;
		}
		
		.server_errors {
			list-style: none;
			padding: 0;
			margin-bottom: 18px;
		}
		
		.server_errors li {
			padding: 4px 7px;
			border-radius: 2px;
			-webkit-border-radius: 2px;
			-moz-border-radius: 2px;
			margin: 0 0 4px 0;
			background: #fbe3e4;
			color: #8a1f11;
		}
		
		.large {
			margin-bottom: 18px;
		}
				
		#install_logo {
			height: 121px;
			text-indent: -9999px;
			background: url(media/site/install_logo.jpg) no-repeat 0px 0px;
		}
		
		.install_table {
			width: 100%;
			padding-bottom: 18px;;
		}
				
		.install_table tr td {
			background: #f8f8f8;
			padding: 3px 9px;
			border-bottom: 4px solid #fff;
			font-size: 11px;
		}
		
		.install_table tr td input[type="text"],
		.install_table tr td input[type="password"]{
			width: 95%;
		}
		
		.weak, .medium, .strong, .match, .no_match {
			padding: 4px 7px;
			border-radius: 2px;
			-webkit-border-radius: 2px;
			-moz-border-radius: 2px;
		}
		
		.weak, .no_match {
			background: #fbe3e4;
			color: #8a1f11;
		}
		
		.medium {
			background: #fff6bf;
			color: #514721;
		}
		
		.strong, .match {
			background: #e6efc2;
			color: #264409;
		}
		
		.directus_terms {
			overflow: auto;
			height: 320px;
			background-color: #fff;
			border: 1px solid #dcdcdc;
			-webkit-border-radius: 2px;
			-moz-border-radius: 2px;
			border-radius: 2px;
			padding: 9px;
			margin-bottom: 18px;
			font-size: 12px;
		}
		
		.directus_terms p {
			margin-bottom: 30px;
		}
		
		.directus_terms ul {
			margin-bottom: 30px;
			margin-left: 40px;
		}
		
		.directus_terms_agree {
			margin-bottom: 18px;
		}
				
	
	</style>
	
</head>

<body>
	
	<div id="page_install">
		
		<h1 id="install_logo" title=":<?PHP echo $_COOKIE['terms']; ?>">Directus</h1>
		<hr>
		
		<?PHP
		if($_COOKIE['terms'] != 'accepted'){
			?>
			<h2>License Agreement</h2>
			
			<p class="large">
				You must accept the terms of this agreement before continuing with the installation.
			</p>
			
			<div class="directus_terms" dir="ltr">
				
				<h3>GNU GENERAL PUBLIC LICENSE</h3>
				<p>Version 3, 29 June 2007<br>
				Copyright &copy; 2007 Free Software Foundation, Inc.
				<a href="http://fsf.org/">http://fsf.org/</a></p>
				
				<p>Everyone is permitted to copy and distribute verbatim copies
				of this license document,<br> but changing it is not allowed.</p>
				
				<h3><a name="preamble"></a>Preamble</h3>
				
				<p>The GNU General Public License is a free, copyleft license for
				software and other kinds of works.</p>
				
				<p>The licenses for most software and other practical works are designed
				to take away your freedom to share and change the works.  By contrast,
				the GNU General Public License is intended to guarantee your freedom to
				share and change all versions of a program--to make sure it remains free
				software for all its users.  We, the Free Software Foundation, use the
				GNU General Public License for most of our software; it applies also to
				any other work released this way by its authors.  You can apply it to
				your programs, too.</p>
				
				<p>When we speak of free software, we are referring to freedom, not
				price.  Our General Public Licenses are designed to make sure that you
				have the freedom to distribute copies of free software (and charge for
				them if you wish), that you receive source code or can get it if you
				want it, that you can change the software or use pieces of it in new
				free programs, and that you know you can do these things.</p>
				
				<p>To protect your rights, we need to prevent others from denying you
				these rights or asking you to surrender the rights.  Therefore, you have
				certain responsibilities if you distribute copies of the software, or if
				you modify it: responsibilities to respect the freedom of others.</p>
				
				<p>For example, if you distribute copies of such a program, whether
				gratis or for a fee, you must pass on to the recipients the same
				freedoms that you received.  You must make sure that they, too, receive
				or can get the source code.  And you must show them these terms so they
				know their rights.</p>
				
				<p>Developers that use the GNU GPL protect your rights with two steps:
				(1) assert copyright on the software, and (2) offer you this License
				giving you legal permission to copy, distribute and/or modify it.</p>
				
				<p>For the developers' and authors' protection, the GPL clearly explains
				that there is no warranty for this free software.  For both users' and
				authors' sake, the GPL requires that modified versions be marked as
				changed, so that their problems will not be attributed erroneously to
				authors of previous versions.</p>
				
				<p>Some devices are designed to deny users access to install or run
				modified versions of the software inside them, although the manufacturer
				can do so.  This is fundamentally incompatible with the aim of
				protecting users' freedom to change the software.  The systematic
				pattern of such abuse occurs in the area of products for individuals to
				use, which is precisely where it is most unacceptable.  Therefore, we
				have designed this version of the GPL to prohibit the practice for those
				products.  If such problems arise substantially in other domains, we
				stand ready to extend this provision to those domains in future versions
				of the GPL, as needed to protect the freedom of users.</p>
				
				<p>Finally, every program is threatened constantly by software patents.
				States should not allow patents to restrict development and use of
				software on general-purpose computers, but in those that do, we wish to
				avoid the special danger that patents applied to a free program could
				make it effectively proprietary.  To prevent this, the GPL assures that
				patents cannot be used to render the program non-free.</p>
				
				<p>The precise terms and conditions for copying, distribution and
				modification follow.</p>
				
				<h3><a name="terms"></a>TERMS AND CONDITIONS</h3>
				
				<h4><a name="section0"></a>0. Definitions.</h4>
				
				<p>&ldquo;This License&rdquo; refers to version 3 of the GNU General Public License.</p>
				
				<p>&ldquo;Copyright&rdquo; also means copyright-like laws that apply to other kinds of
				works, such as semiconductor masks.</p>
				 
				<p>&ldquo;The Program&rdquo; refers to any copyrightable work licensed under this
				License.  Each licensee is addressed as &ldquo;you&rdquo;.  &ldquo;Licensees&rdquo; and
				&ldquo;recipients&rdquo; may be individuals or organizations.</p>
				
				<p>To &ldquo;modify&rdquo; a work means to copy from or adapt all or part of the work
				in a fashion requiring copyright permission, other than the making of an
				exact copy.  The resulting work is called a &ldquo;modified version&rdquo; of the
				earlier work or a work &ldquo;based on&rdquo; the earlier work.</p>
				
				<p>A &ldquo;covered work&rdquo; means either the unmodified Program or a work based
				on the Program.</p>
				
				<p>To &ldquo;propagate&rdquo; a work means to do anything with it that, without
				permission, would make you directly or secondarily liable for
				infringement under applicable copyright law, except executing it on a
				computer or modifying a private copy.  Propagation includes copying,
				distribution (with or without modification), making available to the
				public, and in some countries other activities as well.</p>
				
				<p>To &ldquo;convey&rdquo; a work means any kind of propagation that enables other
				parties to make or receive copies.  Mere interaction with a user through
				a computer network, with no transfer of a copy, is not conveying.</p>
				
				<p>An interactive user interface displays &ldquo;Appropriate Legal Notices&rdquo;
				to the extent that it includes a convenient and prominently visible
				feature that (1) displays an appropriate copyright notice, and (2)
				tells the user that there is no warranty for the work (except to the
				extent that warranties are provided), that licensees may convey the
				work under this License, and how to view a copy of this License.  If
				the interface presents a list of user commands or options, such as a
				menu, a prominent item in the list meets this criterion.</p>
				
				<h4><a name="section1"></a>1. Source Code.</h4>
				
				<p>The &ldquo;source code&rdquo; for a work means the preferred form of the work
				for making modifications to it.  &ldquo;Object code&rdquo; means any non-source
				form of a work.</p>
				
				<p>A &ldquo;Standard Interface&rdquo; means an interface that either is an official
				standard defined by a recognized standards body, or, in the case of
				interfaces specified for a particular programming language, one that
				is widely used among developers working in that language.</p>
				
				<p>The &ldquo;System Libraries&rdquo; of an executable work include anything, other
				than the work as a whole, that (a) is included in the normal form of
				packaging a Major Component, but which is not part of that Major
				Component, and (b) serves only to enable use of the work with that
				Major Component, or to implement a Standard Interface for which an
				implementation is available to the public in source code form.  A
				&ldquo;Major Component&rdquo;, in this context, means a major essential component
				(kernel, window system, and so on) of the specific operating system
				(if any) on which the executable work runs, or a compiler used to
				produce the work, or an object code interpreter used to run it.</p>
				
				<p>The &ldquo;Corresponding Source&rdquo; for a work in object code form means all
				the source code needed to generate, install, and (for an executable
				work) run the object code and to modify the work, including scripts to
				control those activities.  However, it does not include the work's
				System Libraries, or general-purpose tools or generally available free
				programs which are used unmodified in performing those activities but
				which are not part of the work.  For example, Corresponding Source
				includes interface definition files associated with source files for
				the work, and the source code for shared libraries and dynamically
				linked subprograms that the work is specifically designed to require,
				such as by intimate data communication or control flow between those
				subprograms and other parts of the work.</p>
				
				<p>The Corresponding Source need not include anything that users
				can regenerate automatically from other parts of the Corresponding
				Source.</p>
				
				<p>The Corresponding Source for a work in source code form is that
				same work.</p>
				
				<h4><a name="section2"></a>2. Basic Permissions.</h4>
				
				<p>All rights granted under this License are granted for the term of
				copyright on the Program, and are irrevocable provided the stated
				conditions are met.  This License explicitly affirms your unlimited
				permission to run the unmodified Program.  The output from running a
				covered work is covered by this License only if the output, given its
				content, constitutes a covered work.  This License acknowledges your
				rights of fair use or other equivalent, as provided by copyright law.</p>
				
				<p>You may make, run and propagate covered works that you do not
				convey, without conditions so long as your license otherwise remains
				in force.  You may convey covered works to others for the sole purpose
				of having them make modifications exclusively for you, or provide you
				with facilities for running those works, provided that you comply with
				the terms of this License in conveying all material for which you do
				not control copyright.  Those thus making or running the covered works
				for you must do so exclusively on your behalf, under your direction
				and control, on terms that prohibit them from making any copies of
				your copyrighted material outside their relationship with you.</p>
				
				<p>Conveying under any other circumstances is permitted solely under
				the conditions stated below.  Sublicensing is not allowed; section 10
				makes it unnecessary.</p>
				
				<h4><a name="section3"></a>3. Protecting Users' Legal Rights From Anti-Circumvention Law.</h4>
				
				<p>No covered work shall be deemed part of an effective technological
				measure under any applicable law fulfilling obligations under article
				11 of the WIPO copyright treaty adopted on 20 December 1996, or
				similar laws prohibiting or restricting circumvention of such
				measures.</p>
				
				<p>When you convey a covered work, you waive any legal power to forbid
				circumvention of technological measures to the extent such circumvention
				is effected by exercising rights under this License with respect to
				the covered work, and you disclaim any intention to limit operation or
				modification of the work as a means of enforcing, against the work's
				users, your or third parties' legal rights to forbid circumvention of
				technological measures.</p>
				
				<h4><a name="section4"></a>4. Conveying Verbatim Copies.</h4>
				
				<p>You may convey verbatim copies of the Program's source code as you
				receive it, in any medium, provided that you conspicuously and
				appropriately publish on each copy an appropriate copyright notice;
				keep intact all notices stating that this License and any
				non-permissive terms added in accord with section 7 apply to the code;
				keep intact all notices of the absence of any warranty; and give all
				recipients a copy of this License along with the Program.</p>
				
				<p>You may charge any price or no price for each copy that you convey,
				and you may offer support or warranty protection for a fee.</p>
				
				<h4><a name="section5"></a>5. Conveying Modified Source Versions.</h4>
				
				<p>You may convey a work based on the Program, or the modifications to
				produce it from the Program, in the form of source code under the
				terms of section 4, provided that you also meet all of these conditions:</p>
				
				<ul>
				<li>a) The work must carry prominent notices stating that you modified
				    it, and giving a relevant date.</li>
				
				<li>b) The work must carry prominent notices stating that it is
				    released under this License and any conditions added under section
				    7.  This requirement modifies the requirement in section 4 to
				    &ldquo;keep intact all notices&rdquo;.</li>
				
				<li>c) You must license the entire work, as a whole, under this
				    License to anyone who comes into possession of a copy.  This
				    License will therefore apply, along with any applicable section 7
				    additional terms, to the whole of the work, and all its parts,
				    regardless of how they are packaged.  This License gives no
				    permission to license the work in any other way, but it does not
				    invalidate such permission if you have separately received it.</li>
				
				<li>d) If the work has interactive user interfaces, each must display
				    Appropriate Legal Notices; however, if the Program has interactive
				    interfaces that do not display Appropriate Legal Notices, your
				    work need not make them do so.</li>
				</ul>
				
				<p>A compilation of a covered work with other separate and independent
				works, which are not by their nature extensions of the covered work,
				and which are not combined with it such as to form a larger program,
				in or on a volume of a storage or distribution medium, is called an
				&ldquo;aggregate&rdquo; if the compilation and its resulting copyright are not
				used to limit the access or legal rights of the compilation's users
				beyond what the individual works permit.  Inclusion of a covered work
				in an aggregate does not cause this License to apply to the other
				parts of the aggregate.</p>
				
				<h4><a name="section6"></a>6. Conveying Non-Source Forms.</h4>
				
				<p>You may convey a covered work in object code form under the terms
				of sections 4 and 5, provided that you also convey the
				machine-readable Corresponding Source under the terms of this License,
				in one of these ways:</p>
				
				<ul>
				<li>a) Convey the object code in, or embodied in, a physical product
				    (including a physical distribution medium), accompanied by the
				    Corresponding Source fixed on a durable physical medium
				    customarily used for software interchange.</li>
				
				<li>b) Convey the object code in, or embodied in, a physical product
				    (including a physical distribution medium), accompanied by a
				    written offer, valid for at least three years and valid for as
				    long as you offer spare parts or customer support for that product
				    model, to give anyone who possesses the object code either (1) a
				    copy of the Corresponding Source for all the software in the
				    product that is covered by this License, on a durable physical
				    medium customarily used for software interchange, for a price no
				    more than your reasonable cost of physically performing this
				    conveying of source, or (2) access to copy the
				    Corresponding Source from a network server at no charge.</li>
				
				<li>c) Convey individual copies of the object code with a copy of the
				    written offer to provide the Corresponding Source.  This
				    alternative is allowed only occasionally and noncommercially, and
				    only if you received the object code with such an offer, in accord
				    with subsection 6b.</li>
				
				<li>d) Convey the object code by offering access from a designated
				    place (gratis or for a charge), and offer equivalent access to the
				    Corresponding Source in the same way through the same place at no
				    further charge.  You need not require recipients to copy the
				    Corresponding Source along with the object code.  If the place to
				    copy the object code is a network server, the Corresponding Source
				    may be on a different server (operated by you or a third party)
				    that supports equivalent copying facilities, provided you maintain
				    clear directions next to the object code saying where to find the
				    Corresponding Source.  Regardless of what server hosts the
				    Corresponding Source, you remain obligated to ensure that it is
				    available for as long as needed to satisfy these requirements.</li>
				
				<li>e) Convey the object code using peer-to-peer transmission, provided
				    you inform other peers where the object code and Corresponding
				    Source of the work are being offered to the general public at no
				    charge under subsection 6d.</li>
				</ul>
				
				<p>A separable portion of the object code, whose source code is excluded
				from the Corresponding Source as a System Library, need not be
				included in conveying the object code work.</p>
				
				<p>A &ldquo;User Product&rdquo; is either (1) a &ldquo;consumer product&rdquo;, which means any
				tangible personal property which is normally used for personal, family,
				or household purposes, or (2) anything designed or sold for incorporation
				into a dwelling.  In determining whether a product is a consumer product,
				doubtful cases shall be resolved in favor of coverage.  For a particular
				product received by a particular user, &ldquo;normally used&rdquo; refers to a
				typical or common use of that class of product, regardless of the status
				of the particular user or of the way in which the particular user
				actually uses, or expects or is expected to use, the product.  A product
				is a consumer product regardless of whether the product has substantial
				commercial, industrial or non-consumer uses, unless such uses represent
				the only significant mode of use of the product.</p>
				
				<p>&ldquo;Installation Information&rdquo; for a User Product means any methods,
				procedures, authorization keys, or other information required to install
				and execute modified versions of a covered work in that User Product from
				a modified version of its Corresponding Source.  The information must
				suffice to ensure that the continued functioning of the modified object
				code is in no case prevented or interfered with solely because
				modification has been made.</p>
				
				<p>If you convey an object code work under this section in, or with, or
				specifically for use in, a User Product, and the conveying occurs as
				part of a transaction in which the right of possession and use of the
				User Product is transferred to the recipient in perpetuity or for a
				fixed term (regardless of how the transaction is characterized), the
				Corresponding Source conveyed under this section must be accompanied
				by the Installation Information.  But this requirement does not apply
				if neither you nor any third party retains the ability to install
				modified object code on the User Product (for example, the work has
				been installed in ROM).</p>
				
				<p>The requirement to provide Installation Information does not include a
				requirement to continue to provide support service, warranty, or updates
				for a work that has been modified or installed by the recipient, or for
				the User Product in which it has been modified or installed.  Access to a
				network may be denied when the modification itself materially and
				adversely affects the operation of the network or violates the rules and
				protocols for communication across the network.</p>
				
				<p>Corresponding Source conveyed, and Installation Information provided,
				in accord with this section must be in a format that is publicly
				documented (and with an implementation available to the public in
				source code form), and must require no special password or key for
				unpacking, reading or copying.</p>
				
				<h4><a name="section7"></a>7. Additional Terms.</h4>
				
				<p>&ldquo;Additional permissions&rdquo; are terms that supplement the terms of this
				License by making exceptions from one or more of its conditions.
				Additional permissions that are applicable to the entire Program shall
				be treated as though they were included in this License, to the extent
				that they are valid under applicable law.  If additional permissions
				apply only to part of the Program, that part may be used separately
				under those permissions, but the entire Program remains governed by
				this License without regard to the additional permissions.</p>
				
				<p>When you convey a copy of a covered work, you may at your option
				remove any additional permissions from that copy, or from any part of
				it.  (Additional permissions may be written to require their own
				removal in certain cases when you modify the work.)  You may place
				additional permissions on material, added by you to a covered work,
				for which you have or can give appropriate copyright permission.</p>
				
				<p>Notwithstanding any other provision of this License, for material you
				add to a covered work, you may (if authorized by the copyright holders of
				that material) supplement the terms of this License with terms:</p>
				
				<ul>
				<li>a) Disclaiming warranty or limiting liability differently from the
				    terms of sections 15 and 16 of this License; or</li>
				
				<li>b) Requiring preservation of specified reasonable legal notices or
				    author attributions in that material or in the Appropriate Legal
				    Notices displayed by works containing it; or</li>
				
				<li>c) Prohibiting misrepresentation of the origin of that material, or
				    requiring that modified versions of such material be marked in
				    reasonable ways as different from the original version; or</li>
				
				<li>d) Limiting the use for publicity purposes of names of licensors or
				    authors of the material; or</li>
				
				<li>e) Declining to grant rights under trademark law for use of some
				    trade names, trademarks, or service marks; or</li>
				
				<li>f) Requiring indemnification of licensors and authors of that
				    material by anyone who conveys the material (or modified versions of
				    it) with contractual assumptions of liability to the recipient, for
				    any liability that these contractual assumptions directly impose on
				    those licensors and authors.</li>
				</ul>
				
				<p>All other non-permissive additional terms are considered &ldquo;further
				restrictions&rdquo; within the meaning of section 10.  If the Program as you
				received it, or any part of it, contains a notice stating that it is
				governed by this License along with a term that is a further
				restriction, you may remove that term.  If a license document contains
				a further restriction but permits relicensing or conveying under this
				License, you may add to a covered work material governed by the terms
				of that license document, provided that the further restriction does
				not survive such relicensing or conveying.</p>
				
				<p>If you add terms to a covered work in accord with this section, you
				must place, in the relevant source files, a statement of the
				additional terms that apply to those files, or a notice indicating
				where to find the applicable terms.</p>
				
				<p>Additional terms, permissive or non-permissive, may be stated in the
				form of a separately written license, or stated as exceptions;
				the above requirements apply either way.</p>
				
				<h4><a name="section8"></a>8. Termination.</h4>
				
				<p>You may not propagate or modify a covered work except as expressly
				provided under this License.  Any attempt otherwise to propagate or
				modify it is void, and will automatically terminate your rights under
				this License (including any patent licenses granted under the third
				paragraph of section 11).</p>
				
				<p>However, if you cease all violation of this License, then your
				license from a particular copyright holder is reinstated (a)
				provisionally, unless and until the copyright holder explicitly and
				finally terminates your license, and (b) permanently, if the copyright
				holder fails to notify you of the violation by some reasonable means
				prior to 60 days after the cessation.</p>
				
				<p>Moreover, your license from a particular copyright holder is
				reinstated permanently if the copyright holder notifies you of the
				violation by some reasonable means, this is the first time you have
				received notice of violation of this License (for any work) from that
				copyright holder, and you cure the violation prior to 30 days after
				your receipt of the notice.</p>
				
				<p>Termination of your rights under this section does not terminate the
				licenses of parties who have received copies or rights from you under
				this License.  If your rights have been terminated and not permanently
				reinstated, you do not qualify to receive new licenses for the same
				material under section 10.</p>
				
				<h4><a name="section9"></a>9. Acceptance Not Required for Having Copies.</h4>
				
				<p>You are not required to accept this License in order to receive or
				run a copy of the Program.  Ancillary propagation of a covered work
				occurring solely as a consequence of using peer-to-peer transmission
				to receive a copy likewise does not require acceptance.  However,
				nothing other than this License grants you permission to propagate or
				modify any covered work.  These actions infringe copyright if you do
				not accept this License.  Therefore, by modifying or propagating a
				covered work, you indicate your acceptance of this License to do so.</p>
				
				<h4><a name="section10"></a>10. Automatic Licensing of Downstream Recipients.</h4>
				
				<p>Each time you convey a covered work, the recipient automatically
				receives a license from the original licensors, to run, modify and
				propagate that work, subject to this License.  You are not responsible
				for enforcing compliance by third parties with this License.</p>
				
				<p>An &ldquo;entity transaction&rdquo; is a transaction transferring control of an
				organization, or substantially all assets of one, or subdividing an
				organization, or merging organizations.  If propagation of a covered
				work results from an entity transaction, each party to that
				transaction who receives a copy of the work also receives whatever
				licenses to the work the party's predecessor in interest had or could
				give under the previous paragraph, plus a right to possession of the
				Corresponding Source of the work from the predecessor in interest, if
				the predecessor has it or can get it with reasonable efforts.</p>
				
				<p>You may not impose any further restrictions on the exercise of the
				rights granted or affirmed under this License.  For example, you may
				not impose a license fee, royalty, or other charge for exercise of
				rights granted under this License, and you may not initiate litigation
				(including a cross-claim or counterclaim in a lawsuit) alleging that
				any patent claim is infringed by making, using, selling, offering for
				sale, or importing the Program or any portion of it.</p>
				
				<h4><a name="section11"></a>11. Patents.</h4>
				
				<p>A &ldquo;contributor&rdquo; is a copyright holder who authorizes use under this
				License of the Program or a work on which the Program is based.  The
				work thus licensed is called the contributor's &ldquo;contributor version&rdquo;.</p>
				
				<p>A contributor's &ldquo;essential patent claims&rdquo; are all patent claims
				owned or controlled by the contributor, whether already acquired or
				hereafter acquired, that would be infringed by some manner, permitted
				by this License, of making, using, or selling its contributor version,
				but do not include claims that would be infringed only as a
				consequence of further modification of the contributor version.  For
				purposes of this definition, &ldquo;control&rdquo; includes the right to grant
				patent sublicenses in a manner consistent with the requirements of
				this License.</p>
				
				<p>Each contributor grants you a non-exclusive, worldwide, royalty-free
				patent license under the contributor's essential patent claims, to
				make, use, sell, offer for sale, import and otherwise run, modify and
				propagate the contents of its contributor version.</p>
				
				<p>In the following three paragraphs, a &ldquo;patent license&rdquo; is any express
				agreement or commitment, however denominated, not to enforce a patent
				(such as an express permission to practice a patent or covenant not to
				sue for patent infringement).  To &ldquo;grant&rdquo; such a patent license to a
				party means to make such an agreement or commitment not to enforce a
				patent against the party.</p>
				
				<p>If you convey a covered work, knowingly relying on a patent license,
				and the Corresponding Source of the work is not available for anyone
				to copy, free of charge and under the terms of this License, through a
				publicly available network server or other readily accessible means,
				then you must either (1) cause the Corresponding Source to be so
				available, or (2) arrange to deprive yourself of the benefit of the
				patent license for this particular work, or (3) arrange, in a manner
				consistent with the requirements of this License, to extend the patent
				license to downstream recipients.  &ldquo;Knowingly relying&rdquo; means you have
				actual knowledge that, but for the patent license, your conveying the
				covered work in a country, or your recipient's use of the covered work
				in a country, would infringe one or more identifiable patents in that
				country that you have reason to believe are valid.</p>
				  
				<p>If, pursuant to or in connection with a single transaction or
				arrangement, you convey, or propagate by procuring conveyance of, a
				covered work, and grant a patent license to some of the parties
				receiving the covered work authorizing them to use, propagate, modify
				or convey a specific copy of the covered work, then the patent license
				you grant is automatically extended to all recipients of the covered
				work and works based on it.</p>
				
				<p>A patent license is &ldquo;discriminatory&rdquo; if it does not include within
				the scope of its coverage, prohibits the exercise of, or is
				conditioned on the non-exercise of one or more of the rights that are
				specifically granted under this License.  You may not convey a covered
				work if you are a party to an arrangement with a third party that is
				in the business of distributing software, under which you make payment
				to the third party based on the extent of your activity of conveying
				the work, and under which the third party grants, to any of the
				parties who would receive the covered work from you, a discriminatory
				patent license (a) in connection with copies of the covered work
				conveyed by you (or copies made from those copies), or (b) primarily
				for and in connection with specific products or compilations that
				contain the covered work, unless you entered into that arrangement,
				or that patent license was granted, prior to 28 March 2007.</p>
				
				<p>Nothing in this License shall be construed as excluding or limiting
				any implied license or other defenses to infringement that may
				otherwise be available to you under applicable patent law.</p>
				
				<h4><a name="section12"></a>12. No Surrender of Others' Freedom.</h4>
				
				<p>If conditions are imposed on you (whether by court order, agreement or
				otherwise) that contradict the conditions of this License, they do not
				excuse you from the conditions of this License.  If you cannot convey a
				covered work so as to satisfy simultaneously your obligations under this
				License and any other pertinent obligations, then as a consequence you may
				not convey it at all.  For example, if you agree to terms that obligate you
				to collect a royalty for further conveying from those to whom you convey
				the Program, the only way you could satisfy both those terms and this
				License would be to refrain entirely from conveying the Program.</p>
				
				<h4><a name="section13"></a>13. Use with the GNU Affero General Public License.</h4>
				
				<p>Notwithstanding any other provision of this License, you have
				permission to link or combine any covered work with a work licensed
				under version 3 of the GNU Affero General Public License into a single
				combined work, and to convey the resulting work.  The terms of this
				License will continue to apply to the part which is the covered work,
				but the special requirements of the GNU Affero General Public License,
				section 13, concerning interaction through a network will apply to the
				combination as such.</p>
				
				<h4><a name="section14"></a>14. Revised Versions of this License.</h4>
				
				<p>The Free Software Foundation may publish revised and/or new versions of
				the GNU General Public License from time to time.  Such new versions will
				be similar in spirit to the present version, but may differ in detail to
				address new problems or concerns.</p>
				
				<p>Each version is given a distinguishing version number.  If the
				Program specifies that a certain numbered version of the GNU General
				Public License &ldquo;or any later version&rdquo; applies to it, you have the
				option of following the terms and conditions either of that numbered
				version or of any later version published by the Free Software
				Foundation.  If the Program does not specify a version number of the
				GNU General Public License, you may choose any version ever published
				by the Free Software Foundation.</p>
				
				<p>If the Program specifies that a proxy can decide which future
				versions of the GNU General Public License can be used, that proxy's
				public statement of acceptance of a version permanently authorizes you
				to choose that version for the Program.</p>
				
				<p>Later license versions may give you additional or different
				permissions.  However, no additional obligations are imposed on any
				author or copyright holder as a result of your choosing to follow a
				later version.</p>
				
				<h4><a name="section15"></a>15. Disclaimer of Warranty.</h4>
				
				<p>THERE IS NO WARRANTY FOR THE PROGRAM, TO THE EXTENT PERMITTED BY
				APPLICABLE LAW.  EXCEPT WHEN OTHERWISE STATED IN WRITING THE COPYRIGHT
				HOLDERS AND/OR OTHER PARTIES PROVIDE THE PROGRAM &ldquo;AS IS&rdquo; WITHOUT WARRANTY
				OF ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO,
				THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
				PURPOSE.  THE ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM
				IS WITH YOU.  SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF
				ALL NECESSARY SERVICING, REPAIR OR CORRECTION.</p>
				
				<h4><a name="section16"></a>16. Limitation of Liability.</h4>
				
				<p>IN NO EVENT UNLESS REQUIRED BY APPLICABLE LAW OR AGREED TO IN WRITING
				WILL ANY COPYRIGHT HOLDER, OR ANY OTHER PARTY WHO MODIFIES AND/OR CONVEYS
				THE PROGRAM AS PERMITTED ABOVE, BE LIABLE TO YOU FOR DAMAGES, INCLUDING ANY
				GENERAL, SPECIAL, INCIDENTAL OR CONSEQUENTIAL DAMAGES ARISING OUT OF THE
				USE OR INABILITY TO USE THE PROGRAM (INCLUDING BUT NOT LIMITED TO LOSS OF
				DATA OR DATA BEING RENDERED INACCURATE OR LOSSES SUSTAINED BY YOU OR THIRD
				PARTIES OR A FAILURE OF THE PROGRAM TO OPERATE WITH ANY OTHER PROGRAMS),
				EVEN IF SUCH HOLDER OR OTHER PARTY HAS BEEN ADVISED OF THE POSSIBILITY OF
				SUCH DAMAGES.</p>
				
				<h4><a name="section17"></a>17. Interpretation of Sections 15 and 16.</h4>
				
				<p>If the disclaimer of warranty and limitation of liability provided
				above cannot be given local legal effect according to their terms,
				reviewing courts shall apply local law that most closely approximates
				an absolute waiver of all civil liability in connection with the
				Program, unless a warranty or assumption of liability accompanies a
				copy of the Program in return for a fee.</p>
				
			</div>
			
			<div class="directus_terms_agree">
				<input id="install_terms" type="checkbox" />
				<label class="normal" for="install_terms">I hereby agree to the terms of this agreement</label>
			</div>
			
			<div>
				<form action="install.php" id="terms_form" method="post" accept-charset="utf-8">				
					<input type="hidden" name="terms" value="accepted">
					<input id="install_terms_button" class="button big disabled" type="button" value="Continue" />
				</form>
			</div>
			<?PHP
		} elseif(!$meets_requirements){
			?>
			<h2>Hold on!</h2>
			<p class="large">
				Before installing you'll need to set up your server. The following settings are required:
			</p>
			<ul class="server_errors">
				<?PHP
				foreach($errors as $key => $value){
					echo "<li>$value</li>";
				}
				?>
			</ul>
			<div>
				<input class="button big color" type="button" value="Try Again" id="try_again" />
			</div>
			<?PHP
			
			
			
		} elseif(!$server_success) {
			if(count($errors) == 0 || (!file_exists("inc/config.php") && !$_POST['save_config'])){
				?>
				<h2>Awesome!</h2>
				<p class="large">
					Your server has all the required magic to run Directus! Enter in your database connection details below. If you're not sure about these, contact your host.
				</p>
				<?PHP
			} else {
				?>
				<h2>Blast!</h2>
				<ul class="server_errors">
					<?PHP
					foreach($errors as $value){
						?>
						<li><?PHP echo $value;?></li>
						<?PHP
					}
					?>
				</ul>
				<p class="large">
					Please verify the information below. If you still need help, please consult the <a href="#">Directus Help Docs</a>.
				</p>
				<?PHP
			}
			?>
			<form name="save_config_form" action="install.php" method="post">
				
				<table class="install_table">
					<tr>
						<td width="20%"><label class="primary" <?PHP echo ($_GET['test_database'] && !$conn)?'class="red"':'';?> for="db_server">Database Host</label></td>
						<td width="40%"><input name="db_server" class="text" type="text" value="<?PHP echo ($_POST['db_server'])? $_POST['db_server'] : (($db_server)? $db_server : 'localhost');?>"></td>
						<td width="40%">If localhost doesn't work, contact your host.</td>
					</tr>
					<tr>
						<td width="20%"><label class="primary" <?PHP echo ($_GET['test_database'] && !$conn)?'class="red"':'';?> for="db_username">Username</label></td>
						<td width="40%"><input name="db_username" class="text" type="text" value="<?PHP echo ($_POST['db_username'])? $_POST['db_username'] : (($db_username)? $db_username : '');?>"></td>
						<td width="40%">Your database username.</td>
					</tr>
					<tr>
						<td width="20%"><label class="primary" <?PHP echo ($_GET['test_database'] && !$conn)?'class="red"':'';?> for="db_password">Password</label></td>
						<td width="40%"><input name="db_password" class="text" type="text" value="<?PHP echo ($_POST['db_password'])? $_POST['db_password'] : (($db_password)? $db_password : '');?>"></td>
						<td width="40%">Your database password. <em>Optional</em></td>
					</tr>
					<tr>
						<td width="20%"><label class="primary" <?PHP echo ($_GET['test_database'] && $conn && !$db)?'class="red"':'';?> for="db_database">Database Name</label></td>
						<td width="40%"><input name="db_database" class="text" type="text" value="<?PHP echo ($_POST['db_database'])? $_POST['db_database'] : (($db_database)? $db_database : '');?>"></td>
						<td width="40%">The name of the database where you want to install Directus.</td>
					</tr>
					<tr>
						<td width="20%"><label class="primary" for="db_prefix">Table Prefix</label></td>
						<td width="40%"><input name="db_prefix" class="text" type="text" value="<?PHP echo ($_POST['db_prefix'])? $_POST['db_prefix'] : (($db_prefix)? $db_prefix : '');?>"></td>
						<td width="40%">If sharing this database you may want to add a prefix. <em>Optional</em></td>
					</tr>
					<tr>
						<td width="20%"><label class="primary" for="demo_table">Demo Table</label></td>
						<td width="40%"><input name="demo_table" class="text" type="checkbox" value="true" <?PHP echo ($_POST['demo_table'])? 'checked="checked"' : ''; ?>></td>
						<td width="40%">Adds a table within Directus containing core fields and several field types. <em>Optional</em></td>
					</tr>
				</table>
				
				<div>
					<input type="hidden" name="save_config" value="true">
					<input class="button big color" type="submit" value="Continue" />
				</div>
				
			</form>
			<?PHP
			
			
			
		} elseif($requires_settings || count($errors) != 0) {
			if(count($errors) == 0){
				?>
				<h2>Excellent!</h2>
				<p class="large">
					Directus can now communicate with your database! Please enter the following site information and create your first administrator. Don't worry, you can always change these settings later.
				</p>
				<?PHP
			} else {
				?>
				<h2>Uh oh!</h2>
				<ul class="server_errors">
					<?PHP
					foreach($errors as $value){
						?>
						<li><?PHP echo $value;?></li>
						<?PHP
					}
					?>
				</ul>
				<p class="large">
					Please verify the information below. If you still need help, please consult the <a href="#">Directus Help Docs</a>.
				</p>
				<?PHP
			}
			?>
			<form name="save_info_form" action="install.php" method="post">
			
				<table class="install_table">
					<tr>
						<td width="20%"><label class="primary" for="site_name">Site Name</label></td>
						<td width="40%"><input name="site_name" class="text" type="text" value="<?PHP echo $_POST['site_name'];?>"></td>
						<td width="40%"></td>
					</tr>
					<tr>
						<td width="20%"><label class="primary" for="site_url">Site URL</label></td>
						<td width="40%"><input name="site_url" class="text" type="text" value="<?PHP echo ($_POST['site_url'])? $_POST['site_url'] : "http" . ((!empty($_SERVER['HTTPS'])) ? "s" : "") . "://" . $_SERVER['SERVER_NAME'];?>"></td>
						<td width="40%"></td>
					</tr>
					<tr>
						<td width="20%"><label class="primary" for="first_name">First Name</label></td>
						<td width="40%"><input name="first_name" class="text" type="text" value="<?PHP echo $_POST['first_name'];?>"></td>
						<td width="40%"></td>
					</tr>
					<tr>
						<td width="20%"><label class="primary" for="last_name">Last Name</label></td>
						<td width="40%"><input name="last_name" class="text" type="text" value="<?PHP echo $_POST['last_name'];?>"></td>
						<td width="40%"></td>
					</tr>
					<tr>
						<td width="20%"><label class="primary" for="email">Email Address</label></td>
						<td width="40%"><input name="email" class="text" type="text" value="<?PHP echo $_POST['email'];?>"></td>
						<td width="40%">Double-check your email address before continuing.</td>
					</tr>
					<tr>
						<td width="20%"><label class="primary" for="password">Password</label></td>
						<td width="40%"><input name="password" id="check_strength" class="text" type="password" value="<?PHP echo $_POST['password'];?>"></td>
						<td width="40%"><span id="password_strength"></span></td>
					</tr>
					<tr>
						<td width="20%"><label class="primary" for="password_confirm">Confirm Password</label></td>
						<td width="40%"><input name="password_confirm" id="password_confirm" class="text" type="password" value="<?PHP echo $_POST['password_confirm'];?>"></td>
						<td width="40%"><span id="password_match"></span></td>
					</tr>
				</table>
			
				<div>
					<input type="hidden" name="save_info" value="true">
					<input class="button big color" type="submit" value="Finish" />
				</div>
				
			</form>
			<?PHP
			
			
			
		} else {
			?>
			<h2>Congratulations!</h2>
			<p class="large">
				Directus has been installed. You can now login with the email and password you specified.
			</p>
			<div>
				<input class="button big color" type="button" value="Login to Directus" onClick="window.location='login.php'" />
			</div>
			
			<?PHP
		}
		?>
	
	</div>
	
</body>
</html>