<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once("inc/setup.php");

//////////////////////////////////////////////////////////////////////////////
// Make a backup

if($_POST['backup']){
	$backup_file = 'inc/backups/' . date("Y-m-d_H-i-s_") . str_replace(" ", "-", strtolower($cms_user['first_name'].'-'.$cms_user['last_name'])) . '.sql';
	$output = system("mysqldump -h$db_server -u$db_username -p$db_password $db_database > $backup_file");
	if ($output !== false) {
		echo 'success';
		$filesize = filesize($backup_file);
		if(insert_activity($table = $backup_file, $row = $filesize, $type = 'backed up', $sql = '')){
			$_SESSION['alert'] = "backup";
		}
	} else {
		echo 'backup_error';
	}
	die();
}

//////////////////////////////////////////////////////////////////////////////
// Get activity item's main description

function get_item_description($activity){
	
	global $dbh;
	global $settings;

	if($activity['type'] == 'backed up'){
		?><a href="<?PHP echo $activity['table'];?>" target="_blank">File</a> - <?PHP echo byte_convert($activity['row']);?><?PHP echo (file_exists($activity['table']))?'':' - <span class="warning">Missing File!</span>';?><?PHP
	} elseif($activity['type'] == 'error'){
		$view_error = (true)? ellipses($activity['sql']) : $activity['sql'];
		return '<a href="#" class="dialog_note" title="Error Detail" message="<b>User:</b> '.get_username($activity['user']).'<br><b>Page:</b> '.$activity['table'].'<br><b>Time:</b> '.date('M jS Y, g:i:s a', strtotime($activity['datetime'])).'<br><br>'.str_replace('"', '\"',strip_tags($activity['sql'])).'">View Error</a>: ' . $view_error;
	} elseif($activity['type'] == 'installed'){
		return 'Directus install';
	} elseif($activity['table'] == 'directus_users'){
		return '<a href="users.php">' . $activity['sql'] . '</a> has been added to Directus Users';
	} elseif($activity['table'] == 'directus_media'){
		// Get name of media
		$sth = $dbh->prepare("SELECT * FROM `directus_media` WHERE `id` = :id ");
		$sth->bindParam(':id', $activity['row']);
		$sth->execute();
		if($row = $sth->fetch()){
			$title = $row['title'];
		}
		$title = ($title)? ellipses($title, 20) : '<i>No title</i>';
		$return = '<a href="#" class="open_media" media_id="'.$activity['row'].'">' . $title . '</a> within Directus Media';
		$return .= ($activity['sql'] == 'batch')? ' - <b>Batch upload</b>':'';
		return $return;
	} elseif($activity['table'] && $activity['row']){
		$first_field = get_primary_field_value($activity['table'], $activity['row']);
		return '<a href="edit.php?table=' . $activity['table'] . '&item=' . $activity['row'] . '" title="' . ellipses(str_replace('"','\"',$first_field), 200) . '">' . ellipses($first_field, 20) . '</a> within ' . uc_table($activity['table']);		
	}
}

//////////////////////////////////////////////////////////////////////////////
// Loop through array of activity and create table rows

function get_activity($activity_array){
	foreach($activity_array as $activity){
		?>
		<tr<?PHP echo ($activity['type'] == 'error')? ' class="error"' : ''; ?>>
			<td class="activity_action">
				<?PHP 
				// no need for if's, give activity a default class and set based on type
				if($activity['table'] == 'directus_media'){
					echo '<span class="activity media">'.ucwords($activity['type']).'</span>';
				} elseif($activity['type'] == 'edited'){
					echo '<span class="activity edited">Edited</span>';
				} elseif($activity['type'] == 'added'){
					echo '<span class="activity added">Added</span>';
				} elseif($activity['type'] == 'activated'){
					echo '<span class="activity activated">Activated</span>';
				} elseif($activity['type'] == 'deactivated'){
					echo '<span class="activity deactivated">Deactivated</span>';
				} elseif($activity['type'] == 'deleted'){
					echo '<span class="activity deleted">Deleted</span>';
				} elseif($activity['type'] == 'backed up'){
					echo '<span class="activity backedup">Back Up</span>';
				} elseif($activity['type'] == 'reverted'){
					echo '<span class="activity reverted">Reverted</span>';
				} elseif($activity['type'] == 'uploaded'){
					echo '<span class="activity uploaded">Uploaded</span>';
				} elseif($activity['type'] == 'swapped'){
					echo '<span class="activity swapped">Swapped</span>';
				} elseif($activity['type'] == 'installed'){
					echo '<span class="activity backedup">Installed</span>';
				} elseif($activity['type'] == 'error'){
					echo '<span class="activity error">Error</span>';
				} else {
					echo '<span class="activity default">'.ucwords($activity['type']).'</span>';
				}
				?>
			</td>
			<td class="activity_description">
				<div class="wrap">
				<?PHP
				echo get_item_description($activity);
				?>
				</div>
			</td>
			<td class="activity_user text_right">
				by <?PHP echo get_username($activity['user']);?>
			</td>
			<td class="activity_date" title="<?PHP echo date('M jS Y, g:i:s a', strtotime($activity['datetime']));?>">
				<?PHP echo contextual_time(strtotime($activity['datetime']));?>
			</td>
		</tr>
		<?PHP
	}
}

//////////////////////////////////////////////////////////////////////////////		
// If ajaxing in endless page then only show rows, not all html

// Only admins see errors
$view_errors = ($cms_user["admin"] != '1')? "WHERE `active` = '1'" : '';

if($_POST['limit_start']){
	$limit_start = intval($_POST['limit_start']);
	$query = "SELECT * FROM directus_activity $view_errors ORDER BY `datetime` DESC LIMIT $limit_start, 100 ";
	$sth = $dbh->query($query);
	// Reminder: This is how to debug MySQL with PDO
	if ($sth === false){
		print_r($dbh->errorInfo());
	}
	$activity_array = $sth->fetchAll();
	get_activity($activity_array);
	die();
} else{
	$query = "SELECT * FROM directus_activity $view_errors ORDER BY `datetime` DESC LIMIT 0, 100 ";
	$sth = $dbh->query($query);
	$activity_array = $sth->fetchAll();
}

//////////////////////////////////////////////////////////////////////////////
// Get Backups

$backups = dir_list('inc/backups/');
rsort($backups, SORT_STRING);

//////////////////////////////////////////////////////////////////////////////

$cms_html_title = "Dashboard";
require_once("inc/header.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>

<h2>Dashboard</h2>

<hr class="chubby">

<div class="clearfix" style="position:relative;">	
	<div id="dashboard_modules">
		
		<!--
		<div id="export_tables" class="item_module"> 
			<div class="item_module_title"> 
				Export Database
			</div>
			<div class="item_module_box section"> 
				<select>
					<option>Select Table</option>
				</select> 
			</div>
			<div class="item_module_box"> 
				<input id="" class="button color" type="button" value="Export"> 
			</div> 
		</div>
		-->
		
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
				$sth = $dbh->query("SELECT `type`, `table`, `row`, COUNT(*) AS occurrences FROM `directus_activity` WHERE `table` != '' AND `row` != '' AND `table` != 'directus_users' AND `type` != 'error' GROUP BY `table`, `row` ORDER BY occurrences DESC LIMIT 5 ");
				while($popular = $sth->fetch()){
					?>
					<li> 
						<?PHP 
						echo get_item_description($popular);
						?>
					</li>
					<?PHP
					$popular_results = true;
				}
				if(!$popular_results){
					?><li>No popular items</li><?PHP
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
				$remote_versions = unserialize($_SESSION['remote_notifications']);
				if(count($remote_versions) > 0){
					foreach($remote_versions as $remote_version){				
						?>
						<div class="item_module_box section" title="<?PHP echo $remote_version['version'];?>"> 
							<span class="<?PHP echo ($remote_version['category'])? $remote_version['category'] : '';?>"><?PHP echo $remote_version['message'];?></span>
							<span class="item_message_date"><?PHP echo contextual_time(strtotime($remote_version['datetime']));?></span>
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
			//////////////////////////////////////////////////////////////////////////////
			
			get_activity($activity_array);
			
			//////////////////////////////////////////////////////////////////////////////
			?>
			</tbody>
		</table>
		
	</div>
</div>

<?PHP
require_once("inc/footer.php");
?>