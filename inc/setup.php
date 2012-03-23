<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get the time of page load start for timing/optimizing code and start error tracking



$optimize_time_start = microtime(true);
$optimize_time_check = array();
$alert = array();



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Database connection


if(!file_exists('inc/config.php') && file_exists('install.php')){
	header("Location: install.php"); 
	die(); 
}

require_once("config.php");



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Constants



// Timezones and datetimes
if(function_exists("date_default_timezone_set")){
	date_default_timezone_set("GMT");
}

define("CMS_TIME_RAW", time()-date("Z",time()));
define("CMS_TIME", date("Y-m-d H:i:s", time()-date("Z",time())));

// Get page information
$directus_path_array = parse_url($directus_path);

define("CMS_INSTALL_PATH", $directus_path);																								// http://domain.com/folder/directus_folder/
define("CMS_DOMAIN", $directus_path_array['host']);																						// domain.com
define("CMS_PATH", $directus_path_array['path']);																						// /folder/directus_folder/
define("CMS_PAGE_URL", "http" . ((!empty($_SERVER['HTTPS'])) ? "s" : "") . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);	// http://domain.com/folder/directus_folder/file.php?query=string
define("CMS_PAGE_PATH", dirname(CMS_PAGE_URL) . '/');																					// http://domain.com/folder/directus_folder/
define("CMS_PAGE_PATH_FILE", $_SERVER['PHP_SELF']);																						// /folder/directus_folder/file.php
define("CMS_PAGE_FILE", basename(CMS_PAGE_PATH_FILE));																					// file.php
define("CMS_PAGE_QUERYSTRING", (($_SERVER['QUERY_STRING'])? "?" . $_SERVER['QUERY_STRING'] : ""));										// ?query=string
define("BASE_PATH", substr(realpath(dirname(__FILE__)), 0, -3));																		// /a1/b2/c3/domains/domain.com/html/folder/directus_folder/

// Uncomment to see paths displayed (DEV ONLY)
// $alert[] = '<b>CMS_INSTALL_PATH:</b> '.CMS_INSTALL_PATH.'<br><b>CMS_DOMAIN:</b> '.CMS_DOMAIN.'<br><b>CMS_PATH:</b> '.CMS_PATH.'<br><b>CMS_PAGE_URL:</b> '.CMS_PAGE_URL.'<br><b>CMS_PAGE_PATH:</b> '.CMS_PAGE_PATH.'<br><b>CMS_PAGE_PATH_FILE:</b> '.CMS_PAGE_PATH_FILE.'<br><b>CMS_PAGE_FILE:</b> '.CMS_PAGE_FILE.'<br><b>CMS_PAGE_QUERYSTRING:</b> '.CMS_PAGE_QUERYSTRING.'<br><b>BASE_PATH:</b> '.BASE_PATH;

// Maximums and other static variables
define("MAX_TABLE_ITEMS", 10000);
define("MAX_TABLE_REORDERABLE_ITEMS", 2000);
define("MAX_MEDIA_ITEMS", 1000);



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



// Check if we were able to connect to the database, loggoff if not
if(!$server_success){ 
	if($db_database == '' || file_exists('install.php')){
		header("Location: install.php"); 
		die(); 
	} else {
		$_SESSION['alert'] = "error_server"; 
		header("Location: ".CMS_INSTALL_PATH."inc/logoff.php"); 
		die(); 
	}
}



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Remove Magic Quotes if present (which it hopfully is not!)



if(get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Load all the Directus settings into an array



foreach($dbh->query("SELECT * FROM `directus_settings` WHERE `active` = '1' ORDER BY `type` ASC ") as $row){
	if($row["option"]){
		if($row["option_2"]){
			$settings[$row["type"]][$row["option"]][$row["value"]][$row["option_2"]] = $row["value_2"];
		} else {
			$settings[$row["type"]][$row["option"]] = $row["value"];
		}
	} else {
		$settings[$row["type"]][] = $row["value"];
	}
}



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Defaults



$settings['table_hidden'] 			= ($settings['table_hidden'])? 					$settings['table_hidden']: 					array();					// Array of hidden tables
$settings['table_single'] 			= ($settings['table_single'])? 					$settings['table_single']: 					array();					// Array of tables with only one item
$settings['table_inactive_default'] = ($settings['table_inactive_default'])? 		$settings['table_inactive_default']: 		array();					// Array of tables with only one item				
$settings['field_format'] 			= ($settings['field_format'])? 					$settings['field_format']: 					array();					// Array of formats of fields
$settings['field_required'] 		= ($settings['field_required'])? 				$settings['field_required']: 				array();					// Array of required fields
$settings['field_locked'] 			= ($settings['field_locked'])? 					$settings['field_locked']: 					array();					// Array of locked/disabled fields

$settings['cms']['version'] 		= "5.1.b";
$settings['cms']['site_name'] 		= ($settings['cms']['site_name'])? 				$settings['cms']['site_name']: 				"Your Name Here";			// Website Name
$settings['cms']['site_url'] 		= ($settings['cms']['site_url'])? 				$settings['cms']['site_url']: 				"#";						// Website URL
$settings['cms']['cms_color'] 		= ($settings['cms']['cms_color'])? 				$settings['cms']['cms_color']: 				"green";					// CMS Color
$settings['cms']['cookie_life'] 	= ($settings['cms']['cookie_life'])? 			$settings['cms']['cookie_life']: 			43200;						// 30 days
$settings['cms']['idle_logoff_min'] = ($settings['cms']['idle_logoff_min'] !== 0)? 	$settings['cms']['idle_logoff_min']: 		120;						// 2 hours
$settings['cms']['media_filename'] 	= ($settings['cms']['media_filename'])? 		$settings['cms']['media_filename']: 		'unique';					// unique, sequential, original
$settings['cms']['large_uploads'] 	= ($settings['cms']['large_uploads'])? 			$settings['cms']['large_uploads']: 			true;						// [true|false] increases upload size
$settings['cms']['media_path'] 		= ($settings['cms']['media_path'])? 			$settings['cms']['media_path']: 			"media/files/";				// Path to where media is stored (777)
$settings['cms']['thumb_path'] 		= ($settings['cms']['thumb_path'])? 			$settings['cms']['thumb_path']: 			"./media/cms_thumbs/"; 		// ./media/cms_thumbs/
$settings['cms']['thumb_quality'] 	= ($settings['cms']['thumb_quality'])? 			$settings['cms']['thumb_quality']: 			"100";						// 1 - 100
$settings['image_autothumb'] 		= ($settings['image_autothumb'])? 				$settings['image_autothumb']: 				array();					// Array of auto thumbnails


// Set memory limits
if($settings['cms']['large_uploads']){
	ini_set("memory_limit","100M");
	ini_set("max_execution_time","600");
	ini_set("max_input_time","-1");
	ini_set("post_max_size","100M");
	ini_set("upload_max_filesize","100M");
}


// Session Variables
// ALERT
// CMS_USER_ID
// CMS_PASS
// CMS_LAST_PAGE (Can this be stateless?)
// USERS_ONLINE
// USER_IDLE
// DUPLICATE_USER
// REMOTE_NOTIFICATIONS
// SETTINGS_OPEN_TABLE



// Stop here if we're on the logoff.php page
if(CMS_PAGE_FILE == "logoff.php"){ return; }



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Load in all the Directus functions (a version of this file is available as a bootstrap use in your site)



require_once("functions.php");



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Security: list all non-secure pages and files here
// Do not require JUST security.php on other pages... resets user datetime (missing variables from setup.php)



if(CMS_PAGE_FILE == "login.php" || CMS_PAGE_FILE == "alert.php" || CMS_PAGE_FILE == "styles.php" || CMS_PAGE_FILE == "thumbnail.php"){
	return;
} else {
	require_once("security.php");
}



// Stop here if we're on AJAX pages
//if(CMS_PAGE_FILE == "logoff.php"){ return; }



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Check for messages from Directus mothership (Once per session)



if(!$_SESSION['remote_notifications']){
	$timeout = stream_context_create(array('http' => array('timeout' => 3)));
	$remote_versions_data = @file_get_contents('http://getdirectus.com/remote_notifications.php?version='.$settings['cms']['version'], 0, $timeout);
	if($remote_versions_data){
		$_SESSION['remote_notifications'] = $remote_versions_data;
	}
}



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get all plugins



$plugin_on = false;
$plugins_check = dir_list(BASE_PATH . 'plugins', $plugins = true);
rsort($plugins_check, SORT_STRING); // Use alpha variable in function?

$plugins = array();
foreach($plugins_check as $plugin){
	$present_files = dir_list(BASE_PATH . 'plugins/'.$plugin);
	
	// Check to see if this plugin is the active plugin
	if(strpos(CMS_PAGE_PATH, 'plugins/'.$plugin) !== false){
		$plugin_on = $plugin;
	}
	
	// Check for required pages
	if(in_array('index.php', $present_files)){
		$plugins[] = $plugin;
	}
}



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get tables



$tables = get_tables();
$visible_tables = array();

// If table is not hidden and user has access to table, all or is admin OR ALL IS TRUE
foreach($tables as $table){			
	if((strpos($cms_user["view"],',' . $table . ',') !== false || $cms_user["view"] == 'all' || $cms_user["admin"] == '1') && !in_array($table,$settings['table_hidden'])){
		$visible_tables[] = $table;
	}
}



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Media setup



$sth = $dbh->query("SELECT COUNT(*) FROM `directus_media` WHERE `active` = '1' ");
$media_total = $sth->fetchColumn();

// If there are a lot of media items then find a range that's more managable
if(!$_GET['range'] && $media_total > MAX_MEDIA_ITEMS){
	// Does a year limit us enough?
	$sth = $dbh->query("SELECT COUNT(*) FROM `directus_media` WHERE `active` = '1' AND `uploaded` >= DATE_SUB(NOW(), INTERVAL 1 YEAR)  ");
	$media_total_test = $sth->fetchColumn();
	if($media_total_test < MAX_MEDIA_ITEMS){
		$_GET['range'] = 'year';
	} else {
		// Does a month limit us enough?
		$sth = $dbh->query("SELECT COUNT(*) FROM `directus_media` WHERE `active` = '1' AND `uploaded` >= DATE_SUB(NOW(), INTERVAL 1 MONTH)  ");
		$media_total_test = $sth->fetchColumn();
		if($media_total_test < MAX_MEDIA_ITEMS){
			$_GET['range'] = 'month';
		} else {
			// A week will have to do
			$_GET['range'] = 'week';
		}
	}
}



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Messaging setup



$messages_query = "
select
    parent_message.*,
    coalesce(reply_summary.num_replies, 0) as num_replies,
    last_reply_message.datetime as reply_datetime,
    (parent_message.archived LIKE '%,{$cms_user['id']},%') AS message_archive,
    (parent_message.viewed   LIKE '%,{$cms_user['id']},%') AS message_viewed,
    reply_summary.unread_replies,
    coalesce(last_reply_message.datetime, parent_message.datetime) AS last_datetime, 
    coalesce(last_reply_message.message,  parent_message.message)  AS last_message,
    coalesce(last_reply_message.from,     parent_message.from)     AS last_from  
from
    directus_messages as parent_message
    left join (
        select
            reply as parent_id,
            max(id) as last_reply_id,
            count(*) as num_replies,
            sum(viewed not like '%,{$cms_user['id']},%') AS unread_replies
        from
            directus_messages
        where
            reply <> 0 and
            active = 1
        group by
            reply
    ) as reply_summary on reply_summary.parent_id = parent_message.id
    left join directus_messages as last_reply_message on last_reply_message.id = reply_summary.last_reply_id
where
    parent_message.reply = 0 and
    parent_message.active = 1 and
    (parent_message.to like '%,{$cms_user['id']},%' or parent_message.to = 'all' or parent_message.from = '{$cms_user['id']}')
order by
    last_datetime desc;";



$messages = array();
$unread_messages_total = 0;

foreach($dbh->query($messages_query) as $row_messages){
	
	$messages[] = $row_messages;

	if($row_messages['message_archive'] == 0 && $row_messages['message_viewed'] == 0 || $row_messages['unread_replies'] > 0){
		$unread_messages_total++;
	}
}


		
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>