<?PHP
/*
//////////////////////////////////////////////////////////////////////////////

Version 5.1.b

//////////////////////////////////////////////////////////////////////////////
*/

$setup_ajax = true;
require_once("setup.php");

if(isset($cms_user['id'])){
	if($_GET['clean'] && $cms_user["admin"] == '1'){
		ob_start();
		phpinfo();
		$phpinfo = ob_get_contents();
		ob_end_clean();
		
		$phpinfo = preg_replace( '%^.*<body>(.*)</body>.*$%ms','$1',$phpinfo);
		
		// Get MySQL version
		$sth = $dbh->query("SELECT VERSION()");
		$mysql_version = ($row = $sth->fetch())? ($row[0]) : "Unknown";
		?>
		<div class="fog"></div> 
			
		<div class="modal_window" style="display:none;"> 
			<div class="modal_window_header" class="clearfix"> 
				Server details
				<a class="close_modal" href=""></a> 
			</div> 
			
			<div class="modal_window_content" id="phpinfo">
				
				<h2>Requirements</h2>
				<table border="0" cellpadding="3" width="600">
					<tbody>
						<tr><td class="e">Directus Path</td><td class="v"><?PHP echo $directus_path;?></td></tr>
						<tr><td class="e">Directus Version</td><td class="v"><?PHP echo $settings['cms']['version'];?></td></tr>
						<tr><td class="e">PHP Version</td><td class="v"><?PHP echo phpversion();?></td></tr>
						<tr><td class="e">MySQL Server</td><td class="v"><?PHP echo $db_server;?></td></tr>
						<tr><td class="e">MySQL Database</td><td class="v"><?PHP echo $db_database;?></td></tr>
						<tr><td class="e">MySQL Table Prefix</td><td class="v"><?PHP echo ($db_prefix)?$db_prefix:'<span class="quiet">None</span>';?></td></tr>
						<tr><td class="e">MySQL User</td><td class="v"><?PHP echo $db_username;?> <span class="quiet">(<?PHP echo ($db_password)?'Using':'No';?> Password)</span></td></tr>
						<tr><td class="e">MySQL Version</td><td class="v"><?PHP echo $mysql_version;?></td></tr>
						<tr><td class="e">MySQL</td><td class="v"><?PHP echo (extension_loaded('mysql'))?'<span class="green">On</span>':'<span class="bright_red">Off</span>';?></td></tr>
						<tr><td class="e">File Uploads</td><td class="v"><?PHP echo (ini_get('file_uploads'))?'<span class="green">On</span>':'<span class="bright_red">Off</span>';?></td></tr>
						<tr><td class="e">Session Autostart</td><td class="v"><?PHP echo (ini_get('session.auto_start'))?'<span class="bright_red">On</span>':'<span class="green">Off</span>';?></td></tr>
						<tr><td class="e">GD</td><td class="v"><?PHP echo (extension_loaded('gd'))?'<span class="green">On</span>':'<span class="bright_red">Off</span>';?></td></tr>
						<tr><td class="e">ZLIB</td><td class="v"><?PHP echo (extension_loaded('zlib'))?'<span class="green">On</span>':'<span class="bright_red">Off</span>';?></td></tr>
						<tr><td class="e">cURL</td><td class="v"><?PHP echo (function_exists('curl_init'))?'<span class="green">On</span>':'<span class="bright_red">Off</span>';?></td></tr>
						<tr><td class="e">Directus - Temp Folder</td><td class="v"><?PHP echo (is_writable('../media/temp/'))?'<span class="green">Writable</span>':'<span class="bright_red">Read Only</span>';?> <span class="quiet">(media/temp/)</span></td></tr>
						<tr><td class="e">Directus - Media Folder</td><td class="v"><?PHP echo (is_writable('../../'.$settings['cms']['media_path']))?'<span class="green">Writable</span>':'<span class="bright_red">Read Only</span>';?> <span class="quiet">(../<?PHP echo $settings['cms']['media_path'];?><?PHP echo (file_exists('../../'.$settings['cms']['media_path']))?'':' <b>does not exist</b>';?> )</span></td></tr>
						<tr><td class="e">Directus - Thumb Folder</td><td class="v"><?PHP echo (is_writable('../'.$settings['cms']['thumb_path']))?'<span class="green">Writable</span>':'<span class="bright_red">Read Only</span>';?> <span class="quiet">(<?PHP echo str_replace('./','',$settings['cms']['thumb_path']);?>)</span></td></tr>
						<tr><td class="e">Directus - CMS Thumb Folder</td><td class="v"><?PHP echo (is_writable('../media/cms_thumbs/'))?'<span class="green">Writable</span>':'<span class="bright_red">Read Only</span>';?> <span class="quiet">(media/cms_thumbs/)</span></td></tr>
						<tr><td class="e">Directus - Avatar Folder</td><td class="v"><?PHP echo (is_writable('../media/users/'))?'<span class="green">Writable</span>':'<span class="bright_red">Read Only</span>';?> <span class="quiet">(media/users/)</span></td></tr>
						<tr><td class="e">Directus - Backups Folder</td><td class="v"><?PHP echo (is_writable('backups/'))?'<span class="green">Writable</span>':'<span class="bright_red">Read Only</span>';?> <span class="quiet">(inc/backups/)</span></td></tr>
					</tbody>
				</table>
				
				<div class="pad_top">
					<a class="button pill" href="#" onclick="$('#phpinfo_extended').toggle(); return false;">Show extended details</a>
				</div>
				
				<div id="phpinfo_extended" style="display:none;">
					
					<h2 class="pad_top">Session Variables</h2>
					<table border="0" cellpadding="3" width="600">
						<tbody>
						<?PHP 
						foreach($_SESSION as $key => $value){
							?><tr><td class="e"><?PHP echo $key;?></td><td class="v"><pre><?PHP 
							if($key == 'remote_notifications'){
								print_r(unserialize($_SESSION['remote_notifications']));
							} else { 
								if(is_array($value)){
									print_r($value); 
								} else { 
									echo $value; 
								}
							}
							?></pre></td></tr><?PHP
						}
						?>
						</tbody>
					</table>
					 
					<span id="hide_logo">
						<h2 class="pad_top">PHP Info</h2>
						<span class="quiet block pad_bottom"><em>Below is a print out from phpinfo()</em></span>
						<?PHP 
						echo $phpinfo;
						?>
					</span>
					
				</div>
			</div> 
		</div>
		<?PHP
	} else {
		phpinfo();
	}
} else {
	// Must be logged in to see this
}

//////////////////////////////////////////////////////////////////////////////
?>