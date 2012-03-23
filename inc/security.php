<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Check if the user is logged in


// With cookie?
check_user_cookie();


if(isset($_SESSION['cms_user_id'])){
	
	//////////////////////////////////////////////////////////////////////////////
	// Check if user still exists and is active
	
	$sth = $dbh->prepare("SELECT * FROM `directus_users` WHERE `id` = :id AND `active` = '1' LIMIT 1 ");
	$sth->bindParam(':id', $_SESSION['cms_user_id']);
	$sth->execute();
	if($cms_user = $sth->fetch()){
		
		//////////////////////////////////////////////////////////////////////////////
		// Check to ensure that user is only online once (USE IP ADDRESS)
		
		if(!$_SESSION['duplicate_user'] && $cms_user["ip"] != $_SERVER['REMOTE_ADDR'] && (strtotime('-2 minutes', CMS_TIME_RAW) < strtotime($cms_user["last_login"]) && strtotime($cms_user["last_login"]) < strtotime('+5 seconds', CMS_TIME_RAW))){
			$_SESSION['duplicate_user'] = $_SERVER['REMOTE_ADDR'];
			$alert[] = "duplicate_user";
		}
		
		//////////////////////////////////////////////////////////////////////////////		
		// Check if user wants to "Remember Me" and update cookie if so (we can regenerate token here for more security)
		
		if(isset($_COOKIE['token'])){
			setcookie("token", $cms_user["token"], time() + (60*$settings['cms']['cookie_life']), CMS_PATH);
		}
		
		//////////////////////////////////////////////////////////////////////////////
		// Update the time/page the user logged in -- Except for AJAX pages
		
		if($setup_ajax){
			// If this is an AJAX page then dont save the page we're on
			$last_page = "";
		} else {
			$_SESSION['cms_last_page'] = CMS_PAGE_FILE . CMS_PAGE_QUERYSTRING;
			$last_page = "`last_page` = :last_page,";
		}
		
		$sth = $dbh->prepare("UPDATE `directus_users` SET `last_login` = :last_login, $last_page `ip` = :ip WHERE `id` = :id ");
		$sth->bindValue(':last_login', CMS_TIME);
		$sth->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
		$sth->bindParam(':id', $cms_user["id"]);
		
		// Only bind this value if required
		if(!$setup_ajax){ 
			$sth->bindValue(':last_page', CMS_PAGE_FILE . CMS_PAGE_QUERYSTRING); 
		}
		
		$sth->execute();
		
		//////////////////////////////////////////////////////////////////////////////
		// See who is online... anyone in the last two minutes... (AJAX runs every minute)
		
		$users_online = array();
		$occupied_pages = array();
		$cms_admin_users = array();
		$cms_all_users = array();
		$cms_active_user_count = 0;
		
		foreach($dbh->query("SELECT * FROM `directus_users` ORDER BY `last_login` DESC ") as $row){
			//$query .= "AND `last_login` BETWEEN (CMS_TIME - INTERVAL 2 MINUTE) AND CMS_TIME ";
			if($row["active"] == '1'){ 
				if(strtotime('-2 minutes', CMS_TIME_RAW) < strtotime($row["last_login"]) && strtotime($row["last_login"]) < strtotime('+1 minute', CMS_TIME_RAW)) { 
					array_push($users_online, $row["id"]); 
					if($row["id"] != $cms_user["id"]){
						$occupied_pages[$row["id"]] = $row["last_page"];
					}
				}
				if($row["admin"] == '1'){
					$cms_admin_users[] = $row["id"];
				}
				$cms_active_user_count++;
			}
			$cms_all_users[$row["id"]] = $row;
		}
		
		$users_online_total = count($users_online);
		
		//////////////////////////////////////////////////////////////////////////////
		// Get unique usernames
		
		$_users_info = array("first_name_count"=>array(),"last_name_count"=>array(),"first_name_last_initial_count"=>array());
		foreach($cms_all_users as $username_format){
			$_users_info["first_name_count"][$username_format["first_name"]] = isset($_users_info["first_name_count"][$username_format["first_name"]]) ? ++$_users_info["first_name_count"][$username_format["first_name"]] : 1;
			$_users_info["last_name_count"][$username_format["last_name"]] = isset($_users_info["last_name_count"][$username_format["last_name"]]) ? ++$_users_info["last_name_count"][$username_format["last_name"]] : 1;
			$_users_info["first_name_last_initial_count"][$username_format["first_name"]."#".substr($username_format["last_name"],0,1)] = isset($_users_info["first_name_last_initial_count"][$username_format["first_name"]."#".substr($username_format["last_name"],0,1)]) ? ++$_users_info["first_name_last_initial_count"][$username_format["first_name"]."#".substr($username_format["last_name"],0,1)] : 1;
			$_users_info["complete_name_count"][$username_format["first_name"]."#".$username_format["last_name"]] = isset($_users_info["complete_name_count"][$username_format["first_name"]."#".$username_format["last_name"]]) ? ++$_users_info["complete_name_count"][$username_format["first_name"]."#".$username_format["last_name"]] : 1;
			$_users_info["complete_name_allocated"][$username_format["first_name"]."#".$username_format["last_name"]] = 0;
		}
		
		foreach($cms_all_users as $user_key => $username_format) {
			$username = null;
			if($_users_info["first_name_count"][$username_format["first_name"]]==1){
				$username = $username_format["first_name"];
			} else if($_users_info["first_name_last_initial_count"][$username_format["first_name"]."#".substr($username_format["last_name"],0,1)]==1){
				$username = $username_format["first_name"]." ".substr($username_format["last_name"],0,1).".";
			} else if($_users_info["last_name_count"][$username_format["last_name"]]==1){
				$username = $username_format["first_name"]." ".$username_format["last_name"];
			} else {
				$username = $username_format["first_name"]." ".$username_format["last_name"].sprintf(" (%d)",++$_users_info["complete_name_allocated"][$username_format["first_name"]."#".$username_format["last_name"]]);
			}
			//printf("%s %s => %s\n",$username_format["first_name"],$username_format["last_name"],$username);
			$cms_all_users[$user_key]['username'] = $username;
			
			if($cms_user['id'] == $user_key){
				$cms_user['username'] = $username;
			}
		}
		
		//////////////////////////////////////////////////////////////////////////////
		// Check if any users have logged on or off based on differences in user arrays
		
		if($_SESSION['users_online']){
			$new_users_online = array_diff($users_online,$_SESSION['users_online']);
			foreach( (array) $new_users_online as $new_user){
				$alert[] = 'user_on_'.$cms_all_users[$new_user]['username'];
			}
			
			$new_users_offline = array_diff($_SESSION['users_online'],$users_online);
			foreach( (array) $new_users_offline as $new_user){
				$alert[] = 'user_off_'.$cms_all_users[$new_user]['username'];
			}
		}
		
		if(!$setup_ajax){
			$_SESSION['user_idle'] = 0;
		}
		$_SESSION['users_online'] = $users_online;
				
		//////////////////////////////////////////////////////////////////////////////
	} else {
		// User not found in database
		$_SESSION['alert'] = "no_such_user";
		header("Location: ".CMS_INSTALL_PATH."inc/logoff.php");
		die();												
	}
	
} else {
	if(CMS_PAGE_FILE != "index.php" && CMS_PAGE_FILE != "login.php" && CMS_PAGE_FILE != "logoff.php" && !$setup_ajax){
		// No session info but save the current page
		if(!$_COOKIE['cms_redirect']){
			setrawcookie("cms_redirect", CMS_PAGE_FILE . CMS_PAGE_QUERYSTRING, time() + (60*$settings['cms']['cookie_life']), CMS_PATH);
		}	
		$_SESSION['alert'] = "security";
		header("Location: ".CMS_INSTALL_PATH."inc/logoff.php"); 
		die();
		
	} else {
		// No session info
		$_SESSION['alert'] = "security";
		header("Location: ".CMS_INSTALL_PATH."inc/logoff.php"); 
		die();
	}
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
