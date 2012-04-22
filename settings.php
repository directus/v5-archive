<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once("inc/setup.php");

if($_POST['submit']){
	if($cms_user["admin"] == '1'){
		
		//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Create an array with settings in SQL groups
		
		unset($settings_query);
		
		foreach($_POST as $type => $data){
			
			// Save when the settings were saved as a timestamp by user ID who did the saving
			if($type == "submit"){
				unset($data);
				$data[$cms_user["id"]] = CMS_TIME;
			}
			
			// Loop through all inputs POSTed and save them (expand arrays into the table fields)
			foreach( (array) $data as $option => $value){
				// Might remove this (it doesn't save empty notes)
				if($value !== false){
					if(is_array($value)){
						foreach( (array) $value as $option_2 => $value_2){
							$parent_value = $_POST['field_format'][$option];
							$settings_query[] = "('".addslashes($type)."', '".addslashes($option)."', '".addslashes($parent_value)."', '".addslashes($option_2)."', '".addslashes($value_2)."')";
						}
					} else {
						$settings_query[] = "('".addslashes($type)."', '".addslashes($option)."', '".addslashes($value)."', '', '')";
					}
				}
			}
		}
		
		//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Remove the backup settings - Can save multiple versions with `active` > 'X' (but high IDs become an issue... 'X' should be < 10)
		
		if(!$dbh->query("DELETE FROM `directus_settings` WHERE `active` > 2 ")){
			$alert[] = "error_remove_settings";
		}
		
		//////////////////////////////////////////////////////////////////////////////
		// If IDs get too high then reset them
		
		$sth = $dbh->query("SELECT max(id) FROM `directus_settings` ");
		$next_id = ($row = $sth->fetch())? ($row["max(id)"]+1) : 1;
		if($next_id > 100000){
			$decrease_amount = intval(($next_id - 100000) + 10000);
			if(!$dbh->query("UPDATE `directus_settings` SET `id` = id-$decrease_amount ")){
				$alert[] = "error_cleaning_settings";
			} else {
				if(!$dbh->query("ALTER TABLE `directus_settings` AUTO_INCREMENT = 10001 ")){
					$alert[] = "error_resetting_settings";
				}
			}
		}
		
		//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		if(count($error) == 0){
			if(!$dbh->query("UPDATE `directus_settings` SET `active` = active+1 WHERE `active` > 0 ")){ 				// Saves old settings - WHERE `type` != 'field_option'
			//if(!$dbh->query("TRUNCATE `directus_settings` ")){ 														// Deletes old settings -- Risky since if INSERT doesn't work you lose all settings
				$alert[] = "error_backup_settings";
			} else {
				// Save the new settings (SHOULD be parameterized... but not sure of the best way
				if(!$dbh->query("INSERT INTO `directus_settings` (`type`, `option`, `value`, `option_2`, `value_2`) VALUES " . implode(', ', $settings_query) . " ")){ 
					//$alert[] = "Error saving new settings \n\n " . implode("\n", $settings_query);
					$alert[] = "error_save_settings";
				} else {
					$_SESSION['alert'] = "saved";
					header("location: ".CMS_INSTALL_PATH."settings.php");
					die();
				}
			}
		}
		
		//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	}
}

$cms_html_title = "Settings";
require_once("inc/header.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>


<div id="page_header" class="clearfix">
	<h2 class="col_8">Settings</h2>
	<a id="add_table_button" class="button big right add_header" href="#">Add New Table</a>
</div>

<hr class="chubby">

<div class="clearfix" style="position:relative;">
	
	<form id="cms_settings" name="cms_settings" action="settings.php" method="post">
		
		<div id="settings_general">
			
			<div class="box">
				<div class="pad_bottom"> 
					<label class="primary" for="site_name">Site Name</label><br> 
					<input class="settings_general" type="text" name="cms[site_name]" maxlength="255" value="<?PHP echo $settings['cms']['site_name'];?>"> 
				</div>
				<div class="pad_bottom"> 
					<label class="primary" for="site_url">Site URL</label><br> 
					<input class="settings_general" type="text" name="cms[site_url]" maxlength="255" value="<?PHP echo $settings['cms']['site_url'];?>"> 
				</div>
				<div class="pad_bottom"> 
					<label class="primary" for="color">CMS Color</label>
					<select name="cms[cms_color]">
						<?PHP
						$css_files = dir_list(BASE_PATH . 'inc/css/cms_colors', $plugins = false, $alphabetical = true);
						foreach($css_files as $css_file){
							$cms_color = str_replace('.css', '', $css_file);
							?><option value="<?PHP echo $cms_color;?>" <?PHP echo ($settings['cms']['cms_color'] == $cms_color)? 'selected="selected"' : ''; ?> ><?PHP echo uc_convert($cms_color);?></option><?PHP
						}
						?>
					</select> 
				</div>
				<div> 
					<label class="primary" for="idle_logoff_min">CMS Users Auto Logout</label><br> 
					<input type="text" class="small force_numeric" name="cms[idle_logoff_min]" maxlength="3" size="4" value="<?PHP echo ($settings['cms']['idle_logoff_min'] > 0)? $settings['cms']['idle_logoff_min'] : 0; ?>"> minutes on same page <?PHP echo ($settings['cms']['idle_logoff_min'] == 0)? '<span class="quiet">(Off)</span>':'';?>
				</div>
			</div>
			<div class="box">
				<div class="pad_bottom">
					<label class="primary" for="media_folder">Media Folder</label><br>
					<input class="settings_general" type="text" name="cms[media_path]" maxlength="255" value="<?PHP echo $settings['cms']['media_path'];?>">
				</div>
				<div class="pad_bottom">
					<label class="primary">Media Naming</label><br>
					<input id="media_random" type="radio" name="cms[media_naming]" value="unique" <?PHP echo (!$settings['cms']['media_naming'] || $settings['cms']['media_naming'] == 'unique')? 'checked="checked"' : ''; ?> > <label for="media_random">Unique</label>
					<span class="instruct block small"><em>Generates unique filename</em></span>
					<input id="media_sequential" type="radio" name="cms[media_naming]" value="sequential" <?PHP echo ($settings['cms']['media_naming'] == 'sequential')? 'checked="checked"' : ''; ?> > <label for="media_sequential">Sequential</label>
					<span class="instruct block small"><em>Padded number e.g. 00001, 00002</em></span>
					<input id="media_original" type="radio" name="cms[media_naming]" value="original" <?PHP echo ($settings['cms']['media_naming'] == 'original')? 'checked="checked"' : ''; ?> > <label for="media_original">Original filename</label>
					<span class="instruct block small"><em>File names will be cleaned, duplicate names are safe</em></span>
				</div>
				<div class="pad_bottom">
					<label class="primary">Thumbnail Quality</label><br>
					<input id="thumb_quality" class="small force_numeric" maxlength="3" size="3" type="text" name="cms[thumb_quality]" value="<?PHP echo $settings['cms']['thumb_quality'];?>"> <label for="thumb_quality">%</label>
					<br><br>
					<label class="primary">Allowed Thumbnails</label><br>
					<div class="clearfix">
						<label>Width</label> <input id="thumb_width" type="text" size="4" class="small force_numeric" maxlength="4" name="thumb_width" value=""> <label>Height</label> <input id="thumb_height" type="text" class="small force_numeric" maxlength="4" size="4" name="thumb_height" value=""> <input id="thumb_crop" type="checkbox" name="thumb_crop"> <label for="thumb_crop">Crop?</label> <input id="thumb_add" class="button pill" type="button" value="Add">
					</div>
				</div>
				<div>
					<ul id="cms_media_thumbs" class="check_no_rows">
						<?PHP
						if(count($settings['image_autothumb']) > 0){
							sort($settings['image_autothumb'], SORT_NUMERIC);
							foreach($settings['image_autothumb'] as $autothumb){
								$thumb_dimensions = explode(",", $autothumb);
								?>
								<li>
									<div class="clearfix">
										<?PHP echo $thumb_dimensions[0];?> &times; <?PHP echo $thumb_dimensions[1];?> <span class="quiet"><?PHP echo ($thumb_dimensions[2] == 'true')? 'Crop to fit' : 'Shrink to fit';?></span> <a class="ui-icon ui-icon-close right thumb_remove" href="#"></a>
									</div>
									<input type="hidden" name="image_autothumb[]" value="<?PHP echo $autothumb;?>" />
								</li>
								<?PHP
							}
						}
						?>
						<li class="item no_rows">No automatic thumbnails</li>
					</ul>
				</div>
			</div>
			<div class="box">
				<a href="#" id="settings_extended_details">View Server Details</a>
			</div>
			<input class="button color big now_activity" activity="saving" type="submit" value="Update Settings" name="submit"> <span>or <a class="cancel" href="settings.php">Cancel</a></span>			
		</div>
		
		<div id="settings_tables">
			<?PHP 
			// Loop through all tables
			foreach($tables as $key => $table){
				?>
				
				<div class="item_module toggle <?PHP echo (strpos($_SESSION['settings_open_table'], ','.$table.',') !== false)? '' : 'closed'; ?>" id="settings_open_table_<?PHP echo $table; ?>">
					<div class="item_module_title toggle">
						<div class="title">
							<img class="table_settings_icon" src="media/site/icons/<?PHP echo (in_array($table,$settings['table_single']))?'database-arrow':'database';?>.png" width="16" height="16" />
							<span class="table_settings_title"><?PHP echo uc_table($table); ?></span>
							<span class="item_module_toggle ui-icon down"></span>
						</div>
					</div>
					<div class="item_module_box">
						<div class="table_options">
							<input type="checkbox" name="table_hidden[]" value="<?PHP echo $table;?>" <?PHP echo (in_array($table, $settings['table_hidden']))? 'checked="checked"' : ''; ?> ><label class="normal" style="margin-right:0.5em;">Hidden</label>
							<input type="checkbox" name="table_single[]" value="<?PHP echo $table;?>" <?PHP echo (in_array($table, $settings['table_single']))? 'checked="checked"' : ''; ?> ><label class="normal" style="margin-right:0.5em;">Single</label>
							<input type="checkbox" name="table_inactive_default[]" value="<?PHP echo $table;?>" <?PHP echo (in_array($table, $settings['table_inactive_default']))? 'checked="checked"' : ''; ?> ><label class="normal" style="margin-right:0.5em;">Inactive by default</label>
							<input class="button pill right add_field_button" type="button" table="<?PHP echo $table;?>" value="Add New Field">
						</div>
						<table class="field_options">
							<thead>
								<tr>
									<th style="width:130px;">Field</th>
									<th style="width:220px;">Options</th>
									<th class="text_center" style="width:50px;">Required</th>
									<th class="text_center" style="width:50px;">Hidden</th>
									<th class="text_center" style="width:50px;">Primary</th>
									<th>Note</th>
								</tr>
							</thead>
							<tbody>
								
								<?PHP
								// Loop through all fields in this table
								$table_rows = get_rows_info($table);
								foreach($table_rows['fields'] as $field_key => $field){
									$table_field = $table.','.$field;
									$datatype = $settings['field_format'][$table_field];
									$type = $table_rows['info'][$field]['type_lengthless'];
									?>
									<tr>
										<td><div class="wrap" title="<?PHP echo $type;?>"><?PHP echo uc_convert($field); ?></div></td>
										<td>
											<select class="datatype_options" name="field_format[<?PHP echo $table_field;?>]" tablefield="<?PHP echo $table_field;?>">
												<?PHP
												//////////////////////////////////////////////////////////////////////////////
												// Print out all dropdown options for this datatype
												
												foreach( (array) get_datatype_formats($type) as $value){
													?><option value="<?PHP echo $value;?>" <?PHP echo ($datatype == $value)? 'selected="selected"' : ''; ?> ><?PHP echo uc_convert($value);?><?PHP echo ($value == "table_view")?" (Beta)":""; ?></option><?PHP
												}
												
												//////////////////////////////////////////////////////////////////////////////
												?>
											</select>
											<a id="" class="datatype_more_options" href="#">more options</a>
											<div class="options_container"><?PHP include('inc/datatype_options.php'); ?></div>
										</td>
										<td class="text_center"><input title="Required" type="checkbox" name="field_required[<?PHP echo $table_field;?>]" value="true" <?PHP echo ($settings['field_required'][$table_field])? 'checked="checked"' : ''; ?> ></td>
										<td class="text_center"><input title="Hidden" type="checkbox" name="field_hidden[<?PHP echo $table_field;?>]" value="true" <?PHP echo ($settings['field_hidden'][$table_field])? 'checked="checked"' : ''; ?> ></td>
										<td class="text_center"><input title="Primary" type="radio" name="field_primary[<?PHP echo $table;?>]" value="<?PHP echo $field;?>" <?PHP echo ($settings['field_primary'][$table] == $field || (!$settings['field_primary'][$table] && $field_key === 0))? 'checked="checked"' : ''; ?> ></td>
										<td><input type="text" class="field_note" maxlength="255" name="field_note[<?PHP echo $table_field;?>]" value="<?PHP echo str_replace('"', "'", $settings['field_note'][$table_field]);?>"></td>
									</tr>
									<?PHP
								}
								?>
								
							</tbody>
						</table>
					</div>
				</div>
				
				<?PHP
			}
			?>
		</div>
	</form>
</div>

<?PHP
require_once("inc/footer.php");
?>