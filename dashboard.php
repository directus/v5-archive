<?PHP
//////////////////////////////////////////////////////////////////////////////

require_once("inc/setup.php");

//////////////////////////////////////////////////////////////////////////////
// Make a backup

if($_POST['backup']){
	echo backup_database();
	die();
}

//////////////////////////////////////////////////////////////////////////////
// Get Backups

$backups = dir_list('inc/backups/');
rsort($backups, SORT_STRING);

//////////////////////////////////////////////////////////////////////////////
// Get popular items

$query = "SELECT `type`, `table`, `row`, COUNT(*) AS occurrences FROM `directus_activity` WHERE `table` != '' AND `row` != '' AND `table` != 'directus_users' AND `type` != 'error' GROUP BY `table`, `row` ORDER BY occurrences DESC LIMIT 5 ";
$sth = $dbh->query($query);
$popular_array = $sth->fetchAll();

//////////////////////////////////////////////////////////////////////////////		
// If ajaxing in endless page then only show rows, not all html

// Only admins see errors
$view_errors = ($cms_user["admin"] != '1')? "WHERE `active` = '1'" : '';

if($_POST['limit_start']){
	$limit_start = intval($_POST['limit_start']);
	$query = "SELECT * FROM directus_activity $view_errors ORDER BY `datetime` DESC LIMIT $limit_start, 100 ";
} else{
	$query = "SELECT * FROM directus_activity $view_errors ORDER BY `datetime` DESC LIMIT 0, 100 ";
}

$sth = $dbh->query($query);
$activity_array = $sth->fetchAll();
$activity_formatted = array();

foreach($activity_array as $activity){
	
	$temp["error"] = ($activity['type'] == 'error')? ' class="error"' : '';
	$temp["description"] = get_item_description($activity);
	$temp["user"] = get_username($activity['user']);
	$temp["datetime"] = date('M jS Y, g:i:s a', strtotime($activity['datetime']));
	$temp["datetime_readable"] = contextual_time(strtotime($activity['datetime']));
	
	if($activity['table'] == 'directus_media'){
		$temp["placard_class"] = 'media';
		$temp["placard_text"] = ucwords($activity['type']);
	} elseif($activity['type'] == 'edited' || $activity['type'] == 'added' || $activity['type'] == 'activated' || $activity['type'] == 'deactivated' || $activity['type'] == 'deleted' || $activity['type'] == 'reverted' || $activity['type'] == 'uploaded' || $activity['type'] == 'error'){
		$temp["placard_class"] = $activity['type'];
		$temp["placard_text"] = ucwords($activity['type']);
	} elseif($activity['type'] == 'backed up'){
		$temp["placard_class"] = 'system';
		$temp["placard_text"] = 'Back Up';
	} elseif($activity['type'] == 'swapped'){
		$temp["placard_class"] = 'swapped';
		$temp["placard_text"] = 'Swapped';
	} elseif($activity['type'] == 'installed'){
		$temp["placard_class"] = 'system';
		$temp["placard_text"] = 'Installed';
	} else {
		$temp["placard_class"] = 'default';
		$temp["placard_text"] = ucwords($activity['type']);
	}

	$activity_formatted[] = $temp;
	
}

// Only show activity items (AJAXing in more results)
if($_POST['limit_start']){
	activity_items();
	die();
}

function activity_items(){

	global $activity_formatted;

	foreach($activity_formatted as $activity){
		?>
		<tr>
			<td class="activity_action">
				<span class="activity <?PHP echo $activity['placard_class']; ?>"><?PHP echo $activity['placard_text']; ?></span>
			</td>
			<td class="activity_description">
				<div class="wrap">
					<a href="<?PHP echo $activity['description']['link']; ?>" <?PHP echo $activity['description']['attributes']; ?>><?PHP echo $activity['description']['link_text']; ?></a> <?PHP echo $activity['description']['text']; ?>
				</div>
			</td>
			<td class="activity_user text_right">
				by <?PHP echo $activity['user']; ?>
			</td>
			<td class="activity_date" title="<?PHP echo $activity['datetime']; ?>">
				<?PHP echo $activity['datetime_readable']; ?>
			</td>
		</tr>
		<?PHP
	}
}

//////////////////////////////////////////////////////////////////////////////

$cms_html_title = "Dashboard";
require_once("inc/header.php");

//////////////////////////////////////////////////////////////////////////////
?>

<h2>Dashboard</h2>

<hr class="chubby">

<div class="clearfix" style="position:relative;">	
	<div id="dashboard_modules">
		
		<div id="backups_module" class="item_module"> 
			<div class="item_module_title"> 
				Database Backup
			</div>
			<div class="item_module_box section"> 
				There are (<?PHP echo count($backups); ?>) backups
			</div>
			<div class="item_module_box"> 
				<input id="create_backup" class="button color pill" type="button" value="Create New Backup"> 
			</div> 
		</div>
		
		<div id="popular_items" class="item_module"> 
			<div class="item_module_title"> 
				Popular Items
			</div>
			<div class="item_module_box">
				<ul class="item_module_list">
				<?PHP
				if(count($popular_array) > 0){
					foreach($popular_array as $popular){
						$activity = get_item_description($popular);
						?>
						<li> 
							<a href="<?PHP echo $activity['link']; ?>" <?PHP echo $activity['attributes']; ?>><?PHP echo $activity['link_text']; ?></a> <?PHP echo $activity['text']; ?>
						</li>
						<?PHP
					}
				} else {
					?>
					<li>No popular items</li>
					<?PHP
				}
				?>
				</ul>
			</div>
		</div>
		
		<div id="directus_notifications" class="item_module"> 
			<div class="item_module_title"> 
				Directus Notifications
			</div>
			<?PHP
			// If we have the notifications
			if($_SESSION['remote_notifications']){
			
				// Loop over the array of notifications
				$remote_notifications_array = unserialize($_SESSION['remote_notifications']);
				if(count($remote_notifications_array) > 0){
					foreach($remote_notifications_array as $remote_notification){				
						?>
						<div class="item_module_box section" title="<?PHP echo $remote_notification['version'];?>"> 
							<span class="<?PHP echo ($remote_notification['category'])? $remote_notification['category'] : '';?>"><?PHP echo $remote_notification['message'];?></span>
							<span class="item_message_date"><?PHP echo contextual_time(strtotime($remote_notification['datetime']));?></span>
						</div>
						<?PHP
					}
				} else {
					?>
					<div class="item_module_box"> 
						There are currently no notifications
					</div>
					<?PHP
				}
			} else {
				?>
				<div class="item_module_box"> 
					Unable to retrieve Directus notifications
				</div>
				<?PHP
			}
			?>
		</div>
		
	</div>
	
	<div id="dashboard_activity">
		<h3>Activity</h3>
		<table id="activity">
			<tbody>
			
			<?PHP
			activity_items();
			?>
			
			</tbody>
		</table>
		
	</div>
</div>

<?PHP
require_once("inc/footer.php");
?>