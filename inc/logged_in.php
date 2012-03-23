<?PHP
//////////////////////////////////////////////////////////////////////////////
// Setup without saving location

$setup_ajax = true;
require_once("setup.php");

//////////////////////////////////////////////////////////////////////////////

if($server_success){

	//////////////////////////////////////////////////////////////////////////////
	// Auto logoff
	
	if($settings['cms']['idle_logoff_min'] > 0){
		// Ask user to stay on after certain duration
		$_SESSION['user_idle'] = ($_SESSION['user_idle'])? $_SESSION['user_idle']+1 : 1;
		if(($_SESSION['user_idle']%$settings['cms']['idle_logoff_min']) == 0){		// Ask every X minutes with modulus
		
			if($_POST['current_page']){
				setrawcookie("cms_redirect", $_POST['current_page'], time() + (60*43200), CMS_PATH);	// 30 days
			}
			
			$_SESSION['alert'] = 'timeout';
			
			echo 'timeout';
			die();
		}
	}
	
	//////////////////////////////////////////////////////////////////////////////
	// Get current users online
	
	$updated_users_online = array();
	
	$sth = $dbh->prepare("SELECT * FROM `directus_users` WHERE `active` = '1' AND `last_login` BETWEEN (:time - INTERVAL 2 MINUTE) AND (:time - INTERVAL -1 MINUTE) ");
	$sth->bindValue(':time', CMS_TIME);
	$sth->execute();
	while($row = $sth->fetch()){
		array_push($updated_users_online, $row["id"]);
	}
	
	//////////////////////////////////////////////////////////////////////////////
	// If users are differenet than what it was then send an alert
	
	if($_SESSION['users_online']){
		$new_users_online = array_diff($updated_users_online,$_SESSION['users_online']);
		foreach( (array) $new_users_online as $new_user){
			$alert[] = 'user_on_'.$cms_all_users[$new_user]['username'];
		}
	
		$new_users_offline = array_diff($_SESSION['users_online'],$updated_users_online);
		foreach( (array) $new_users_offline as $new_user){
			$alert[] = 'user_off_'.$cms_all_users[$new_user]['username'];
		}
		
		if(is_array($alert) && count($alert) > 0){
			echo implode(',', $alert);
		}
	}
	
	$_SESSION['users_online'] = $updated_users_online;
	
	//////////////////////////////////////////////////////////////////////////////
	// Update user time and page
	
	$sth = $dbh->prepare("UPDATE `directus_users` SET `last_login` = :last_login, `last_page` = :last_page, `ip` = :ip WHERE `id` = :id ");
	$sth->bindValue(':last_login', CMS_TIME);
	$sth->bindParam(':last_page', $_SESSION['cms_last_page']);
	$sth->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
	$sth->bindParam(':id', $_SESSION['cms_user_id']);
	$sth->execute();
	
} else {
	// Could not connect to the database
	echo 'error_connection';
}

//////////////////////////////////////////////////////////////////////////////
?>
