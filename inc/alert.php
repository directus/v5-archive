<?PHP
//////////////////////////////////////////////////////////////////////////////
// Setup without saving location

$setup_ajax = true;
require_once("setup.php");

//////////////////////////////////////////////////////////////////////////////
// Alerts are stored as CSV (COMBINE THESE TWO?)

$alerts = ($_POST['alert'])? explode(',', $_POST['alert']) : $alert;

if($_SESSION['alert']){
	array_merge( explode(',', $_SESSION['alert']), $alerts);
	
	// Clear session so alert doesn't repeat
	unset($_SESSION['alert']);
}

//////////////////////////////////////////////////////////////////////////////
// Types

$alert_message = array();
//$alert_message['activity'] = array(); // Done with JS to be immediate
$alert_message['attention'] = array();
$alert_message['error'] = array();
$alert_message['info'] = array();
$alert_message['success'] = array();
$alert_message['users'] = array();

//////////////////////////////////////////////////////////////////////////////

if(is_array($alerts) && count($alerts) > 0){
	foreach( (array) $alerts as $alert){
		
		// Actions
		if($alert == 'added'){
			$alert_message['success'][] = 'Added';
			
		} elseif($alert == 'saved'){
			//$alert_message['success'][] = 'Saved';
			
		} elseif($alert == 'updated'){
			$alert_message['success'][] = 'Updated';
			
		} elseif($alert == 'removed'){
			$alert_message['success'][] = 'Removed';
			
		} elseif(substr($alert, 0, 15) == 'item_activated_'){
			$items = intval(substr($alert, 15));
			$temp_s = ($items == 1)?'':'s';
			//$alert_message['success'][] = "$items items$temp_s activated";
			
		} elseif(substr($alert, 0, 13) == 'item_deleted_'){
			$items = intval(substr($alert, 13));
			$temp_s = ($items == 1)?'':'s';
			//$alert_message['success'][] = "$items items$temp_s deleted";
			
		} elseif(substr($alert, 0, 17) == 'item_deactivated_'){
			$items = intval(substr($alert, 17));
			$temp_s = ($items == 1)?'':'s';
			//$alert_message['success'][] = "$items items$temp_s deactivated";
			
		
		
		
		
		
		
		
		// Permissions
		} elseif($alert == 'permissions_exist'){
			$alert_message['attention'][] = 'That item does not exist';
			
		} elseif($alert == 'permissions_view'){
			$alert_message['attention'][] = 'You do not have permission to view that';
			
		} elseif($alert == 'permissions_edit'){
			$alert_message['attention'][] = 'You do not have permission to edit that';
			
		} elseif($alert == 'permissions_delete'){
			$alert_message['attention'][] = 'You do not have permission to delete that';
		
		} elseif(substr($alert, 0, 14) == 'no_such_table_'){
			$alert_message['attention'][] = 'No such table: "'.substr($alert, 14).'"';				
		
		
		
		
		
		// Media
		} elseif($alert == 'error_media_update'){
			$alert_message['error'][] = 'Error updating media';
			
		} elseif($alert == 'media_swapped'){
			$alert_message['success'][] = 'Media swapped';
			
		} elseif($alert == 'media_added'){
			//$alert_message['success'][] = 'Media added';
			
		} elseif($alert == 'error_swapping_media'){
			$alert_message['error'][] = 'Error swapping media';
			
		} elseif($alert == 'error_adding_media'){
			$alert_message['error'][] = 'Error adding media';
			
		} elseif($alert == 'batch_upload_complete'){
			//$alert_message['success'][] = 'Batch upload complete';
			
		} elseif($alert == 'thumb_required_width'){
			$alert_message['attention'][] = 'Please enter a width';
			
		} elseif($alert == 'thumb_required_height'){
			$alert_message['attention'][] = 'Please enter a height';
			
			
			
			
			
			
		// Messages
		} elseif($alert == 'messages_read_already'){
			$alert_message['attention'][] = 'All messages have been read';
			
		} elseif($alert == 'messages_read_error'){
			$alert_message['error'][] = 'Error reading message';
		
		} elseif($alert == 'error_restore_message'){
			$alert_message['error'][] = 'Error restoring message';
			
		} elseif($alert == 'message_restored'){
			$alert_message['success'][] = 'Message restored';
			
		} elseif($alert == 'error_archive_message'){
			$alert_message['error'][] = 'Error archiving message';
			
		} elseif($alert == 'message_archived'){
			$alert_message['success'][] = 'Message archived';
			
		} elseif($alert == 'reply_sent'){
			//$alert_message['success'][] = 'Reply sent';
			
		} elseif($alert == 'message_sent'){
			//$alert_message['success'][] = 'Message sent';
			
		} elseif($alert == 'allow_reply'){
			$alert_message['attention'][] = 'You can\'t reply to this item';
			
		} elseif($alert == 'allow_archive'){
			$alert_message['attention'][] = 'You can\'t archive this item';
			
		} elseif(substr($alert, 0, 16) == 'marked_messages_'){
			$marked_messages = intval(substr($alert, 16));
			$temp_s = ($marked_messages == 1)?'':'s';
			$alert_message['success'][] = "Marked $marked_messages message$temp_s as read";
		
		} elseif($alert == 'message_to_required'){
			$alert_message['attention'][] = 'Please select at least one user to receive this message';
			
		} elseif($alert == 'message_subject_required'){
			$alert_message['attention'][] = 'Please enter a subject';
			
		} elseif($alert == 'message_message_required'){
			$alert_message['attention'][] = 'Please enter a message';
		
		
		
		
		
		
		
		// Errors
		} elseif($alert == 'error_sorting'){
			$alert_message['error'][] = 'Error sorting';
			
		} elseif(substr($alert, 0, 16) == 'items_reordered_'){
			$item_count = intval(substr($alert, 16));
			$temp_s = ($item_count == 1)?'':'s';
			//$alert_message['success'][] = "Reordered $item_count item$temp_s";
		
		} elseif($alert == 'all_activity_visible'){
			$alert_message['success'][] = 'You\'ve reached the beginning';
			
		} elseif($alert == 'header_limit'){
			$alert_message['attention'][] = 'You may only have 8 headers on at once';
			
		} elseif($alert == 'status_change_items_required'){
			$alert_message['attention'][] = 'Please check at least one item below';
			
		} elseif($alert == 'text_format_required_selection'){
			$alert_message['attention'][] = 'You must first select some text below';
			
		} elseif($alert == 'not_supported'){
			$alert_message['attention'][] = 'This feature is not supported by your browser';
		
		
		
		
		
		
		// Edit page
		} elseif($alert == 'invalid_revert'){
			$alert_message['error'][] = 'Invalid revision';
			
		} elseif($alert == 'error_revert'){
			$alert_message['error'][] = 'Error reverting';
			
		} elseif($alert == 'success_revert'){
			//$alert_message['success'][] = 'Item reverted';
		
		} elseif(substr($alert, 0, 15) == 'required_field_'){
			$alert_message['attention'][] = '<b>'.substr($alert, 15).'</b> is required';
		
		
		
		
		
		
		
		// Users
		} elseif($alert == 'password_sent'){
			$alert_message['success'][] = 'Instructions on resetting your password have been sent to your email';
			
		} elseif($alert == 'no_such_user'){
			$alert_message['error'][] = 'No such user';
			
		} elseif(substr($alert, 0, 13) == 'no_such_user_'){
			$alert_message['error'][] = 'No such user: "'.substr($alert, 13).'"';
			
		} elseif($alert == 'user_see'){
			$alert_message['error'][] = 'You aren\'t allowed to edit this user';
			
		} elseif($alert == 'user_save_error'){
			$alert_message['error'][] = 'Error saving user';
			
		} elseif(substr($alert, 0, 8) == 'user_on_'){
			$alert_message['users'][] = substr($alert, 8).' has logged onto Directus';
			
		} elseif(substr($alert, 0, 9) == 'user_off_'){
			$alert_message['users'][] = substr($alert, 9).' has logged off Directus';	
			
		} elseif($alert == 'duplicate_user'){
			$alert_message['users'][] = 'This user is logged in at another location... you\'ve been warned';
			
		} elseif(substr($alert, 0, 17) == 'user_double_edit_'){
			$alert_message['users'][] = substr($alert, 17).' is currently editing this item. Directus recommends waiting until this user is done otherwise changes may be overwritten.';
			
		} elseif($alert == 'suicide'){
			$alert_message['attention'][] = "You are removing YOURSELF from Directus! You will be logged out and denied further access";
		
		} elseif($alert == 'murder'){
			$alert_message['attention'][] = 'This user will be logged out and denied further access to Directus';
			
		} elseif($alert == 'first_name'){
			$alert_message['attention'][] = 'First Name must be at least 2 characters. You can use letters, spaces and dashes. Must start and end with a letter and can\'t have two spaces or dashes in a row';
		
		} elseif($alert == 'last_name'){
			$alert_message['attention'][] = 'Last Name must be at least 2 characters. You can use letters, spaces and dashes. Must start and end with a letter and can\'t have two spaces or dashes in a row';
		
		} elseif($alert == 'passwords_match'){
			$alert_message['attention'][] = 'Passwords do not match';
		
		} elseif($alert == 'password_format'){
			$alert_message['attention'][] = 'Password must be at least 3 characters';
		
		} elseif($alert == 'email_format'){
			$alert_message['attention'][] = 'Email is not a valid address';
			
		} elseif(substr($alert, 0, 13) == 'email_format_'){
			$alert_message['attention'][] = '<b>'.substr($alert, 13).'</b> is not a valid address';
		
		} elseif(substr($alert, 0, 13) == 'email_in_use_'){
			$alert_message['attention'][] = 'Email is already in use! <a href="user_settings.php?u='.substr($alert, 13).'">View '.$cms_all_users[substr($alert, 13)]['username'].'</a>';
			
		} elseif($alert == 'stay_logged_in'){
			$alert_message['users'][] = "<input type='button' id='stay_logged_in' value='Stay logged in? 30'>";
			$_SESSION['alert'] = 'timeout';
			$persistent = true;
		
		
		
		
		
		// System
		} elseif($alert == 'general_error'){
			$alert_message['error'][] = 'Error, try again in a moment';
			
		} elseif($alert == 'security'){
			$alert_message['error'][] = 'Access denied, please log in';
			
		} elseif($alert == 'error_server'){
			$alert_message['attention'][] = 'Directus can\'t access your server! If this is your first time running Directus for this site you need to run the installation <a href="install.php">here</a>, otherwise please contact your technical support';
			
		} elseif($alert == 'error_connection'){ 
			$alert_message['attention'][] = 'Directus can\'t communicate with your server! This issue could be caused by site maintenance or other issues. If you continue to receive this message please contact your technical support. Leaving this page might cause you to lose any unsaved changes';
		
		} elseif($alert == 'backup'){
			//$alert_message['success'][] = 'Backup created';
			
		} elseif($alert == 'backup_error'){
			$alert_message['error'][] = 'Error creating backup';
			
		} elseif($alert == 'timeout'){
			$alert_message['attention'][] = 'You have been logged out due to inactivity';
			
		} elseif($alert == 'virgin'){
			$alert_message['info'][] = 'Welcome! If this is your first time using Directus feel free to visit our <a href="http://getdirectus.com/support/#use" target="_blank">website to learn more</a> or click anywhere to close this message';
			
		} elseif(substr($alert, 0, 27) == 'email_fail_forgot_password_'){
			$alert_message['error'][] = 'Please try again later: '.substr($alert, 27);
		
		} elseif($alert == 'no_user_email'){
			$alert_message['attention'][] = 'Sorry, we don\'t have an email address for this user';
		
		} elseif(substr($alert, 0, 14) == 'password_fail_'){
			$alert_message['error'][] = 'Password incorrect'; // .substr($alert, 14)
			
		} elseif($alert == 'password_fail'){
			$alert_message['error'][] = 'Password incorrect';
			
		} elseif($alert == 'reset_expired'){
			$alert_message['error'][] = 'Password reset token is no longer valid';
			
		} elseif($alert == 'password_required'){
			$alert_message['attention'][] = 'Please fill in a password';
		
		} elseif(substr($alert, 0, 11) == 'email_fail_'){
			$alert_message['error'][] = 'Email incorrect: '.substr($alert, 11);
			
		} elseif($alert == 'email_required'){
			$alert_message['attention'][] = 'Please fill in your email';
		
		} elseif(substr($alert, 0, 20) == 'login_user_disabled_'){
			$alert_message['error'][] = 'User disabled: '.substr($alert, 20);
		
		} elseif($alert == 'remove_install'){
			$alert_message['error'][] = 'Your install file is still present. For security reasons you should delete this file as soon as possible';
			
		} elseif($alert == 'installer_removed'){
			$alert_message['success'][] = 'Installer removed';
			
		} elseif($alert == 'installer_remove_manual'){
			$alert_message['error'][] = 'Installer not removed, please manually remove this file';
				
		
			
			
			
			
		// Preferences
		} elseif($alert == 'preference_not_saved'){
			$alert_message['error'][] = 'Preference not saved';
			
		} elseif($alert == 'preference_saved'){
			$alert_message['success'][] = 'Preference saved';
			
		} elseif($alert == 'preference_not_added'){
			$alert_message['error'][] = 'Preference not added';
			
		} elseif($alert == 'preference_added'){
			$alert_message['success'][] = 'Preference added';
			
			
			
			
			
			
			
		// Settings
		} elseif($alert == 'error_save_settings'){
			$alert_message['error'][] = 'Error saving settings, backups should be available in your database';
			
		} elseif($alert == 'error_remove_settings'){
			$alert_message['error'][] = 'Error removing old settings';
			
		} elseif($alert == 'error_backup_settings'){
			$alert_message['error'][] = 'Error backing up old settings';
			
		} elseif($alert == 'error_cleaning_settings'){
			$alert_message['error'][] = 'Error cleaning up settings';
			
		} elseif($alert == 'error_resetting_settings'){
			$alert_message['error'][] = 'Error cleaning up settings table';
		
		} elseif($alert == 'success_add_table'){
			$alert_message['success'][] = 'Table added';
			
		} elseif($alert == 'error_add_table'){
			$alert_message['error'][] = 'Error adding table';
		
		} elseif($alert == 'success_add_field'){
			$alert_message['success'][] = 'Field added';
			
		} elseif($alert == 'error_add_field'){
			$alert_message['error'][] = 'Error adding field';
		
		} elseif($alert == 'success_reorder_field'){
			$alert_message['success'][] = 'Fields reordered';
			
		} elseif($alert == 'error_reorder_field'){
			$alert_message['error'][] = 'Error reordering field';
		
		
		
		
		
		
		
		
		
		// Default and custom messages
		} elseif($alert_type == 'txt'){
			$alert_message['error'][] = stripslashes(nl2br($alert));
			
		} else {
			$alert_message['error'][] = stripslashes($alert);
		}
		
		// Log all errors
		if($alert_type == 'error'){
			$last_page = ($_SESSION['cms_last_page'])? $_SESSION['cms_last_page'] : 'None';
			$error_info = $_SERVER['REMOTE_ADDR'] . ', ';
			$alert_detail = ($alert_detail)? "\n" . $alert_detail : '';
			echo '<!-- '.insert_activity($table = $last_page, $row = $error_info, $type = 'error', $sql = $alert_message.$alert_detail, $active = 0).' -->';
		}
			
	}
	
	//////////////////////////////////////////////////////////////////////////////
	// If there are errors, display
	
	$alert_types = array_unique(array_keys($alert_message));
	
	foreach($alert_types as $alert_type){
		if(count($alert_message[$alert_type]) > 0){
			?>
			<div class="alert_box<?PHP echo ($persistent)? ' persistent':'';?>" style="display:none;">
				<div class="alert_box_message <?PHP echo $alert_type;?>">
					<div class="alert_box_icon"></div>
					<ul>
						<?PHP
						foreach($alert_message[$alert_type] as $alert_item){
							echo "<li>$alert_item</li>";
						}
						?>
					</ul>
				</div>
			</div>
			<?PHP
		}
	}
	
	//////////////////////////////////////////////////////////////////////////////
}
?>