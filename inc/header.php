<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en-US">

<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex">
	
	<title><?PHP echo $settings['cms']['site_name']; ?> - <?PHP echo $cms_html_title; ?></title>
	
	<link rel="shortcut icon" href="<?PHP echo $directus_path;?>media/site/favicon.ico">
	
	<link rel="stylesheet" href="<?PHP echo $directus_path;?>inc/css/directus.css<?PHP echo ($cms_debug)? '?'.time():''; ?>" type="text/css" media="screen" title="" charset="utf-8">
	<link rel="stylesheet" href="<?PHP echo $directus_path;?>inc/css/cms_colors/<?PHP echo $settings['cms']['cms_color'];?>.css<?PHP echo ($cms_debug)? '?'.time():''; ?>" type="text/css" media="screen" title="" charset="utf-8">
	
	<script type="text/javascript" src="<?PHP echo $directus_path;?>inc/js/jquery.js"></script>
	<script type="text/javascript" src="<?PHP echo $directus_path;?>inc/js/jquery-ui.js"></script>
	<script type="text/javascript" src="<?PHP echo $directus_path;?>inc/js/directus.js<?PHP echo ($cms_debug)? '?'.time():''; ?>" id="base_path" base_path="<?PHP echo $directus_path;?>"></script>
</head>

<body>
	
	<!-- Media Dropzone -->
	
	<form id="media_dropzone" target="iframe" action="inc/upload.php" method="post" enctype="multipart/form-data" name="media_dropzone_form" style="position:absolute; top:0px; left:0px; width:0px; height:0px; opacity:0.1; filter:alpha(opacity=10); background-color:#2F2F2F; z-index:9999999; display:none;">
		<input type="hidden" id="media_dropzone_type" name="type" value="inline|relational">
		<input type="hidden" id="media_dropzone_parent_item" name="parent_item" value="id_of_parent">
		<input type="hidden" id="media_dropzone_extensions" name="extensions" value="jpg,gif,png,etc">
		<input type="file" id="media_dropzone_input" name="upload_media[]" class="short" multiple="multiple" style="position:absolute; top:0px; left:0px; width:100%; height:100%; opacity:0.0; filter:alpha(opacity=0);">
	</form>
	
	<iframe width="0" height="0" id="iframe" name="iframe" src="" scrolling="no" style="position:fixed; top:0px; left:0px; width:0px; height:0px; border:0px solid #cccccc;"></iframe>
	
	<!-- Modal Windows -->
	
	<div id="modal_current_level">0</div>
	
	<!-- Dialog Windows -->
	
	<div id="dialog_window" class="hide"></div>
	
	<!-- Alert Windows -->
	
	<div id="throbber_preload"></div>
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
	
	<!-- Hat -->
	
	<div id="toolbar">
		<div class="container clearfix">

			<div id="site_title">
				<span class="title"><?PHP echo $settings['cms']['site_name']; ?></span>
				<?PHP
				if($settings['cms']['site_url'] && $settings['cms']['site_url'] != '#'){
					?><a class="badge view_site" href="<?PHP echo $settings['cms']['site_url']; ?>" target="_blank">View Site</a><?PHP 
				}
				
				if(false){
					?><a class="badge alert" href="http://getdirectus.com/">Free Version</a><?PHP
				}
				?>
			</div>

			<ul id="user_nav" class="clearfix">
				<li id="throbber"><img src="<?PHP echo $directus_path;?>media/site/throbber.gif" width="16" height="16" /></li>
				<li><img class="user_avatar" src="<?PHP echo get_avatar($cms_user['id']);?>" width="16" height="16"> <span class="username" title="<?PHP echo implode(', ',get_usernames(implode(',',$users_online)));?>"><?PHP echo $cms_user["username"]; ?></span></li>
				<li><a href="<?PHP echo $directus_path;?>messages.php"><span>Inbox<?PHP echo ($unread_messages_total>0)? '<span class="badge inbox_count">'.$unread_messages_total.'</span>' : '';?></span></a></li>
				<?PHP if($cms_user['editable'] == '1'){ ?><li><a href="<?PHP echo $directus_path;?>user_settings.php?u=<?PHP echo strtolower($cms_user["id"]); ?>">User Settings</a></li><?PHP } ?>
				<li><a href="<?PHP echo $directus_path;?>inc/logoff.php" class="now_activity" activity="logging_out">Logout</a></li>
			</ul>

		</div>
	</div>
	
	<!-- Tabs -->
	
	<div id="main_nav" class="clearfix">
		<div class="container">
			<div class="clearfix">
				<ul>
					<li><a <?PHP echo (CMS_PAGE_FILE == 'dashboard.php')?'class="current"':''; ?> href="<?PHP echo $directus_path;?>dashboard.php"><span>Dashboard</span></a></li>
					<li><a <?PHP echo (CMS_PAGE_FILE == 'tables.php' || CMS_PAGE_FILE == 'browse.php' || CMS_PAGE_FILE == 'edit.php')?'class="current"':''; ?> href="<?PHP echo $directus_path;?>tables.php"><span>Tables<span class="badge count"><?PHP echo count($visible_tables) ?></span></span></a></li>
					<li><a <?PHP echo (CMS_PAGE_FILE == 'media.php')?'class="current"':''; ?> href="<?PHP echo $directus_path;?>media.php"><span>Media<span class="badge count"><?PHP echo $media_total;?></span></span></a></li>
					<li><a <?PHP echo (CMS_PAGE_FILE == 'users.php' || CMS_PAGE_FILE == 'user_settings.php' )?'class="current"':''; ?> href="<?PHP echo $directus_path;?>users.php"><span>Users<span class="badge count"><?PHP echo $cms_active_user_count;?></span></span></a></li>
					<?PHP if($cms_user['admin']){ ?><li><a <?PHP echo (CMS_PAGE_FILE == 'settings.php')?'class="current"':''; ?> href="<?PHP echo $directus_path;?>settings.php"><span>Settings</span></a></li><?PHP } ?>
					<?PHP
					//////////////////////////////////////////////////////////////////////////////
					// Add tabs for plugins
					
					foreach($plugins as $plugin){
						?>
						<li><a <?PHP echo ($plugin_on == $plugin)?'class="current"':''; ?> href="<?PHP echo $directus_path;?>plugins/<?PHP echo $plugin;?>/index.php"><span><?PHP echo uc_convert($plugin);?></span><!-- <span class="count">0</span> --></a></li>
						<?PHP
					}
					
					//////////////////////////////////////////////////////////////////////////////
					?>
				</ul>
			</div>
		</div>
	</div>
	
	<!-- Page Content -->
	
	<div id="content">
		<div class="container">

			<div id="page">
			
			<?PHP
			// Show the header if we're on a plugin page
			if($plugin_on){
				?>
				<div id="page_header" class="clearfix"> 
					<h2 class="col_8">
						<?PHP 
						if(CMS_PAGE_FILE == 'plugin_settings.php'){
							echo '<a href="'.$directus_path.'plugins/'.$plugin_on.'/index.php">' . uc_convert($plugin_on) . '</a> <span class="divider">/</span> Settings ';
						} else {
							echo uc_convert($plugin_on);
						}
						?>
					</h2> 
					<?PHP 
					if($cms_user['admin'] && file_exists(BASE_PATH . 'plugins/'.$plugin_on.'/plugin_settings.php') && CMS_PAGE_FILE != 'plugin_settings.php'){ 
						?><a id="plugin_settings" class="button pill right" href="plugin_settings.php">Plugin Settings</a><?PHP 
					} ?>
					<a id="plugin_info" class="button pill right" href="info.txt">Plugin Info</a>
				</div> 
				
				<hr class="chubby">
				<?PHP
			}
			?>