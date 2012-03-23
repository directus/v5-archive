<?PHP
require_once("setup.php");

if($server_success){
	// Update user time and page
	$sth = $dbh->prepare("UPDATE `directus_users` SET `last_login` = :last_login, `last_page` = :last_page, `ip` = :ip WHERE `id` = :id ");
	$sth->bindParam(':last_login', date("Y-m-d H:i:s", strtotime("-2 minutes")-date("Z",time())));
	$sth->bindParam(':last_page', $_SESSION['cms_last_page']);
	$sth->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
	$sth->bindParam(':id', $cms_user['id']);
	$sth->execute();
}

// Delete user cookies and save the session alert
setcookie("token", "", time() - 3600, CMS_PATH);
setcookie("cms_alert", $_SESSION['alert'], time() + (60*$settings['cms']['cookie_life']), CMS_PATH);

// Clear sessions
session_regenerate_id();			// Get a new session ID
session_destroy();					// Kill empty session
unset($_SESSION);					// Unset the session variable

header("Location: ".CMS_INSTALL_PATH."login.php"); 
die();
?>