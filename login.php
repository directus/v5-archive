<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

@include_once("inc/config.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// If no DB info and install file exists then go there...

if(!$server_success){
	if(file_exists('install.php')){
		header("Location: install.php");
		die();
	} else {
		$settings['cms']['site_name'] = 'Connection Error';
		$settings['cms']['site_url'] = 'http://getdirectus.com/';
		$alert[] = 'error_server';
	}
} else {
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// If DB connection is good then continue
	
	require_once("inc/setup.php");
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Reset password
	
	if($_POST['reset']){
	
		if($_POST["password_reset"] != $_POST["password_confirm"]){
			$alert[] = "passwords_match";
		}
		if(strlen($_POST["password_reset"]) < 3){
			$alert[] = "password_format";
		}
		
		if(count($alert) == 0){
			$sth = $dbh->prepare("SELECT * FROM `directus_users` WHERE `reset_token` = :reset_token AND `reset_expiration` > NOW() ");
			$sth->bindParam(':reset_token', $_POST['reset']);
			$sth->execute();
			if($row = $sth->fetch()){
				
				// Hash the new password
				$hasher = new PasswordHash(8, FALSE);
				$new_password = $hasher->HashPassword($_POST["password_reset"]);
				
				// Save new password and reset token and expiration
				$sth2 = $dbh->prepare("UPDATE `directus_users` SET `password` = :password, `reset_token` = '', `reset_expiration` = NOW() WHERE `id` = :id ");
				$sth2->bindParam(':password', $new_password);
				$sth2->bindParam(':id', $row["id"]);
				if($sth2->execute()){
					$_SESSION['cms_user_id'] = $row["id"];
				} else {
					$alert[] = "general_error";
				}
			}
		}
	}
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Check if user is already logged in
	
	// With cookie? 
	check_user_cookie();
	
	
	if(!isset($_SESSION['cms_user_id'])) {
	
		// If they attempt to log in
		if($_POST['attempt']) {
	
			// Check if email is filled in
			if($_POST['login_email']){
				
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// Check if email exists with parameterized PDO
				
				$sth = $dbh->prepare("SELECT * FROM `directus_users` WHERE `email` = :email ");
				$sth->bindParam(':email', $_POST['login_email']);
				$sth->execute();
				if($row = $sth->fetch()){	
					
					// Ensure this is an active user (not inactive)
					if($row['active'] == '1'){
						
						// Send password if forgotten, else check password
						if($_POST["forgot"] == "true"){
						
							// Update reset token and expiration here
							$reset_token = nonce();
							$sth2 = $dbh->prepare("UPDATE `directus_users` SET `reset_token` = :reset_token, `reset_expiration` = NOW() + INTERVAL 30 MINUTE WHERE `id` = :id ");
							$sth2->bindParam(':reset_token', $reset_token);
							$sth2->bindParam(':id', $row["id"]);
							$sth2->execute();
		
							$body = "Hello ".$row["first_name"]." ".$row["last_name"].",\n<br>\n<br>Below is a link to reset your Directus user password for \"" . $settings['cms']['site_name'] . "\". This link will work for the next 30 minutes, after that you will need to request a new password reset.\n<br>\n<br>\n<br>" . CMS_INSTALL_PATH . "login.php?reset-token=".urlencode($reset_token);
		
							$sent = send_email("Directus Password Reset", $body, $to = $row["email"], $bcc = false, $from = false);
							
							if($sent) {
								$_SESSION['alert'] = "password_sent";
							} else {
								$alert[] = "email_fail_forgot_password_".$_POST['login_email'];
							}
							
						} else {
							if($_POST['login_password']){
								
								// Check that the password is correct
								$hasher = new PasswordHash(8, FALSE);
								
								if($hasher->CheckPassword($_POST['login_password'], $row["password"])){
									
									// Check to ensure that user is only online once (USE IP ADDRESS)
									
									$_SESSION['cms_user_id'] = $row["id"];
									
									// If user would like to "Remember Me" then store token
									if($_POST['remember_me'] == 'true'){
									
										// Generate a new user token
										$token = nonce();
										$sth2 = $dbh->prepare("UPDATE `directus_users` SET `token` = :token WHERE `id` = :id ");
										$sth2->bindParam(':token', $token);
										$sth2->bindParam(':id', $row["id"]);
										$sth2->execute();
										
										setcookie("token", $token, time() + (60*$settings['cms']['cookie_life']), CMS_PATH);
									}
									
									// Redirect to where you last were or the homepage
									if(file_exists('install.php')){
										$_SESSION['alert'] = (unlink('install.php'))? "installer_removed" : "installer_remove_manual";
										header("Location: ".CMS_INSTALL_PATH."settings.php");
										die();
									} elseif($_COOKIE['cms_redirect']){
									
										// The installer should no longer be present
										if(file_exists('install.php')){
											$_SESSION['alert'] = "remove_install";
										}
										
										// Redirect to previous page if one exists
										$redirect = $_COOKIE['cms_redirect'];
										setrawcookie("cms_redirect", "", time() - 3600, CMS_PATH);
										header("Location: $redirect");
										die();
									} else {
									
										// The installer should no longer be present
										if(file_exists('install.php')){
											$_SESSION['alert'] = "remove_install";
										}
										
										// If this is the users first time logging in then present with help info
										if($row["last_login"] == '0000-00-00 00:00:00'){
											$_SESSION['alert'] = "virgin";
										}
										
										if($row["last_page"] != ""){
											header("Location: ".CMS_INSTALL_PATH.$row["last_page"]);
										} else {
											header("Location: ".CMS_INSTALL_PATH."tables.php");
										}
										die();
									}
		
								} else{
									$alert[] = "password_fail_".$_POST['login_email'].' / '.$_POST['login_password']; // Could be same error message bad user/password for safer error handling
								}
							} else {
								$alert[] = "password_required";
							}
						}
					} else {
						$alert[] = "login_user_disabled_".$_POST['login_email']; // Could be same error message bad user/password for safer error handling
					}
				} else {
					$alert[] = "email_fail_".$_POST['login_email'];	// Could be same error message bad user/password for safer error handling
				}
			} else{
				$alert[] = "email_required";
			}
		}
	} else {
		// Redirect if already logged in
		header("Location: ".CMS_INSTALL_PATH."tables.php");
		die();
	}
	
	if(isset($_COOKIE['cms_alert'])){
		$_SESSION['alert'] = $_COOKIE['cms_alert'];
		setcookie("cms_alert", "", time() - 3600, CMS_PATH);
	}
	
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Check for password reset

$reset_password = false;

if($_GET['reset-token']){
			
	// Check reset-token
	$sth = $dbh->prepare("SELECT * FROM `directus_users` WHERE `reset_token` = :reset_token AND `reset_expiration` > NOW() ");
	$sth->bindParam(':reset_token', $_GET['reset-token']);
	$sth->execute();
	if($row = $sth->fetch()){
		$reset_password = true;
	} else {
		$alert[] = "reset_expired";
	}
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en-US">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />

	<title><?PHP echo $settings['cms']['site_name']; ?> - Login to Directus</title>
	
	<link rel="shortcut icon" href="<?PHP echo $directus_path;?>media/site/favicon.ico">
	<link rel="stylesheet" href="<?PHP echo $directus_path;?>inc/css/directus.css<?PHP echo '?'.time(); ?>" type="text/css" media="screen" title="" charset="utf-8">
	<link rel="stylesheet" href="<?PHP echo $directus_path;?>inc/css/cms_colors/<?PHP echo $settings['cms']['cms_color'];?>.css<?PHP echo '?'.time(); ?>" type="text/css" media="screen" title="" charset="utf-8">

	<script type="text/javascript" src="<?PHP echo $directus_path;?>inc/js/jquery.js"></script>
	<script type="text/javascript" src="<?PHP echo $directus_path;?>inc/js/jquery-ui.js"></script>
	<script type="text/javascript" src="<?PHP echo $directus_path;?>inc/js/directus.js" id="base_path" base_path="<?PHP echo $directus_path;?>"></script>

	<script>
		var browser=navigator.appName;
		var b_version=navigator.appVersion;
		var version=parseFloat(b_version);

		if(browser == 'Microsoft Internet Explorer'){
			alert('You are using IE :(\n\nChrome/FireFox/Safari are the recommended browsers for Directus.\n\nYou can continue, but things may not be as pretty.');
		} else if(browser != 'Netscape') {
			alert('Chrome/FireFox/Safari are the recommended browsers for Directus.\n\nThank you.');
		}

		<?PHP
		if(count($alert) > 0) {
			?>
			$(document).ready(function(){
				// Shake if alert
				$("#shake").effect("shake", { times: 2 }, 150);
			});
			<?PHP
		}
		?>
	</script>

</head>

<body>
	
	<div id="alert_container">
		<?PHP
		// Add URL alert to existing array of alerts
		if($_SESSION['alert']){
			$alert[] = $_SESSION['alert'];
			// Clear session so alert doesn't repeat
			unset($_SESSION['alert']);
		}
		if(count($alert) > 0){
			require_once(BASE_PATH . "inc/alert.php");
		}
		?>
	</div>
	
	<div id="toolbar">
		<div class="container clearfix">
			<div id="site_title">
				<span class="title"><?PHP echo $settings['cms']['site_name']; ?></span>
				<a class="badge view_site" href="<?PHP echo $settings['cms']['site_url']; ?>" target="_blank"><?PHP echo ($server_success)?'View Site':'Troubleshoot';?></a>
			</div>
		</div>
	</div>
	
	<?PHP if($server_success){ ?>
	<div id="login">
		
		<div id="shake">
			<?PHP
			if($reset_password){
				?>
				<form id="login_form" name="login_form" action="login.php?reset-token=<?PHP echo urlencode($_GET['reset-token']); ?>" method="post" enctype="multipart/form-data">
					<input id="reset" name="reset" type="hidden" value="<?PHP echo $_GET['reset-token']; ?>" />
					<label class="primary" for="password_reset">New Password</label><br>
					<input id="password_reset" name="password_reset" class="title" type="password" maxlength="50"><br>
					<label class="primary" for="password_confirm">Confirm Password</label><br>
					<input id="password_confirm" name="password_confirm" class="title" type="password" maxlength="50"><br>
					<input class="button big color login" type="submit" value="Save">	
				</form>
				<?PHP
			} else {
				?>
				<form id="login_form" name="login_form" action="login.php" method="post" enctype="multipart/form-data">
					<input id="attempt" name="attempt" 	type="hidden" value="true" />
					<input id="forgot" 	name="forgot" 	type="hidden" value="false" />
					<label class="primary" for="login_email">Email</label><br>
					<input id="login_email" name="login_email" class="title" type="text" maxlength="50"><br>
					<label class="primary" for="login_password">Password</label><br>
					<input id="login_password" name="login_password" class="title" type="password" maxlength="50"><br>
					<input type="checkbox" id="remember_me" name="remember_me" value="true" /> <label for="remember_me">Remember Me</label><br>
					<input class="button big color login" type="submit" value="Login">
					<a href="#" id="forgot_password">Forgot your password?</a>		
				</form>
				<?PHP
			}
			?>
		</div>

		<div id="login_footer">
			Powered by <a href="http://getdirectus.com">Directus (v<?PHP echo $settings['cms']['version']; ?>)</a>
		</div>
		
	</div>
	<?PHP } ?>

</body>
</html>
