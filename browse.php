<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once("inc/setup.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// If not table ID, get ID from name

if($_GET['table'] || $_GET['table'] == 0){
	$table_id = (array_search($_GET['table'], $tables) !== false)? array_search($_GET['table'], $tables) : $_GET['table'];
	$table_rows = get_rows($table_id);
	//print_r($table_rows);
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// If nothing is found...

if(!$table_rows){ $_SESSION['alert'] = "no_such_table_".addslashes($_GET['table']); header("Location: ".CMS_INSTALL_PATH."tables.php"); die(); }

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Check Permissions

$allow = get_permissions($table_rows['name']);
if(!$allow['view']){ $_SESSION['alert'] = "permissions_view"; header("Location: ".CMS_INSTALL_PATH."tables.php"); die(); }

// Disable Reordering if more than X items
if(count($table_rows['rows']) > MAX_TABLE_REORDERABLE_ITEMS){
	$allow['reorder'] = false;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get browse rows (Loop through all items in this table)

function show_browse_rows() {
	
	global $dbh;
	global $table_rows;
	global $allow;
	global $settings;
	
	$item_count = 0;
	
	foreach($table_rows['rows'] as $key => $row){

		// Cutoff for active items
		if($item_count++ >= MAX_TABLE_ITEMS){
			break;
		}
		
		// if there is an active field, set the classes
		if(!$table_rows['active']){
			$status = 'status_active';
		} else {
			if($row['active']==0){
				$status = 'status_deleted';
			} else if($row['active']==1) {
				$status = 'status_active';
			} else {
				$status = 'status_inactive';
			}
		}
		
		?>
		<tr id="item_<?PHP echo $row['id']; ?>" class="item <?PHP echo $status;?>">
			
			<?PHP 
			// Only show the handles if this table has an sort field
			if($table_rows['sort'] && $allow['reorder']){ 
				?><td class="order handle" title="#<?PHP echo $row['sort'];?>"><span class="order_field"><img src="media/site/icons/ui-splitter-horizontal.png" width="16" height="16" /></span></td><?PHP 
			}
			
			// Only show the checkboxes if this table has an active field
			if($table_rows['active'] && $allow['delete']){ 
				?><td class="check"><input class="status_action_check" id="<?PHP echo $row['id'];?>" type="checkbox" name="" value=""></td><?PHP
			}
			
			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// Loop through all field in this item
			
			foreach($table_rows['fields'] as $key => $field){
				$type = $table_rows['info'][$field]['type_lengthless'];
				$value = $row[$field];
				
				$field_format = $settings["field_format"][$table_rows['name'].",".$field];
				$browse_media = ($field_format == 'media' && intval($settings['field_option'][$table_rows['name'].",".$field]['media']["browse_media"]) > 0)? true : false;
				
				// Check if field is hidden by admin
				if($settings['field_hidden'][$table_rows['name'].','.$field] != 'true'){
					// Need no spaces here for proper DOM sorting
					?>
					<td class="<?PHP echo ($allow['edit'])?'editable':'';?> field_<?PHP echo $field;?> <?PHP echo ($key==0)?'first_field':'';?> <?PHP echo ($browse_media)? 'thumb':'';?>" <?PHP echo ((!$table_rows['header_fields'] && $key<8) || $settings['field_primary'][$table_rows['name']] == $field || strpos($table_rows['header_fields'], ','.$field.',') !== false)?'':'style="display:none;"';?> ><div <?PHP echo ($browse_media || $field_format == 'rating')?'':'class="wrap"';?> ><?PHP 
						
						// If there is an active field, enable status badges
						if($table_rows['active']){
							if($row['active']==2 && $key==0){
								?><span class="status">Inactive</span><?PHP
							} elseif($row['active']==0 && $key==0) {
								?><span class="status deleted">Deleted</span><?PHP
							}
						}
						
						//////////////////////////////////////////////////////////////////////////////
						// Check if field is relational
						
						$is_relational = ($settings['field_format'][$table_rows['name'].",".$field] == 'relational')? true : false;
						$row_relational = $settings['field_option'][$table_rows['name'].','.$field]['relational'];
						$field_settings = $settings['field_option'][$table_rows['name'].','.$field];
						
						//print_r($field_settings);
						
						if($is_relational){
						
							// Get options
							$echo_relational = array();
							$option_fields = explode(",", $row_relational["option"]);
							$sth = $dbh->query($row_relational["sql"]);
							while($row_options = $sth->fetch()){
								
								// Get the saved field
								$option_value = $row_options[$row_relational["value"]];
								
								// Loop through visible fields (field1,field2)
								unset($option_array);
								foreach($option_fields as $temp){
									$option_array[] = $row_options[$temp];
								}
								
								// Check if this value is selected
								if($row_relational["multiple"] || $row_relational['style'] == 'fancy'){
									$found = (strpos($value, ",".$option_value.",") !== false)? true: false;
								} else {
									$found = ($value == $option_value || $value == ','.$option_value.',')? true : false;
								}
								
								// If found then add this item to printed array
								if($found !== false){
									$echo_relational[] = implode($row_relational["option_glue"],$option_array);
								}
							}
							
							echo implode(', ', $echo_relational);
							
						} elseif($field_format == 'media'){
							unset($array_media);
							$array_media = explode(',',$value);
							$array_media = array_filter($array_media);
							if(count($array_media) > 0 && $browse_media){
								
								$sth = $dbh->prepare("SELECT * FROM `directus_media` WHERE `active` = '1' AND `id` = :id ");
								$media_allowed_visible = 0;
								foreach($array_media as $media){
									if($media_allowed_visible++ < $field_settings['media']["browse_media"]){
										$sth->bindParam(':id', $media);
										$sth->execute();
										if($browse_media = $sth->fetch()){
											generate_media_image($browse_media['extension'], $browse_media['source'], $browse_media['height'], $browse_media['width'], $browse_media['file_size']);
										}
									}
								}
							} else {
								echo count($array_media) . ' Files';
							}
						} elseif($type == 'date'){
							echo ($value == "0000-00-00")? "No date" : date('M j, Y',strtotime($value));
						} elseif($type == 'time'){
							?><span title="and <?PHP echo ltrim(date('s',strtotime($value)), '0');?> seconds"><?PHP echo date('g:i A',strtotime($value));?></span><?PHP
						} elseif($type == 'datetime'){
							?><span title="<?PHP echo contextual_time(strtotime($value));?>"><?PHP echo date('M j, Y - g:i A',strtotime($value));?></span><?PHP
						} elseif($field_format == 'tags'){
							echo str_replace(',',', ',substr($value,1,-1));
						} elseif($field_format == 'password'){
							echo str_repeat("*", strlen($value));
						} elseif($field_format == 'checkbox'){
							?><input type="checkbox" name="" value="1" disabled="disabled" <?PHP echo ($value == '1')?'checked="checked"':'';?> ><?PHP
						} elseif($field_format == 'histogram'){
						
							//$histogram_table = get_rows_info($data['name']);
							//$histogram_sql = ($histogram_table['active'] == true)? "AND `active` = '1' " : "";
							//$sth = $dbh->query("SELECT max($key) as peak_amount FROM `".$data['name']."` WHERE id != '".$data['item_id']."' $histogram_sql");
							//$peak_amount = ($peak = $sth->fetch())? ($peak["peak_amount"]) : 0;
							
							?><span class="histogram_bar" peak_amount="calculate"><span field="<?PHP echo $field;?>" title="<?PHP echo $value;?>" this_amount="<?PHP echo $value;?>"></span></span> 
							<span class="histogram_percent">0%</span><?PHP
						} elseif($field_format == 'rating'){
							$max = $settings['field_option'][$table_rows['name'].",".$field]['rating']["max"];
							?><div class="rating_system" style="width:<?PHP echo 16*$max;?>px;">
								<div class="rating_bar" style="width:<?PHP echo 16*$value;?>px;">&nbsp;</div><?PHP
								for($i=1;$i<=$max;$i++) {
									?><span class="star" alt="<?PHP echo $i;?>" count="<?PHP echo $i;?>"></span><?PHP
								}
							?></div><?PHP
						} else {
							echo strip_tags($value);
						}
						
					?></div></td><?PHP	// Need no spaces here for proper DOM sorting
				}
			}
			
			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			?>
		</tr>
		<?PHP
	}
	
	//////////////////////////////////////////////////////////////////////////////
	// Get number of visible fields
	$num_fields_shown = 0;
	foreach($table_rows['fields'] as $key => $field){
		if($settings['field_hidden'][$table_rows['name'].','.$field] != 'true'){
			$num_fields_shown++;
		}
	}
	?><tr class="item no_rows"><td colspan="<?PHP echo $num_fields_shown+3;?>">No items</td></tr><?PHP
	
	if(count($table_rows['rows']) > MAX_TABLE_ITEMS){
		?><tr class="item"><td colspan="<?PHP echo $num_fields_shown+3;?>"><b>The table "<?PHP echo $table_rows['name_uc']; ?>" has reached the maximum allowed number of items (<?PHP echo MAX_TABLE_ITEMS;?>)</b><br>Please have your admin remove some items from your trash or raise this limit</td></tr><?PHP
	}
}

// Only get rows if we're ajaxing the results
if(isset($_GET['ajax'])){
	show_browse_rows();
	die();
}	

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get header

$cms_html_title = ($table_rows)? 'Browsing ' . $table_rows['name_uc'] : 'No such table';
require_once("inc/header.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>

<div id="hidden_vars" style="display:none;">
	<div id="cms_table"><?PHP echo $table_rows['name']; ?></div>
	<div id="cms_table_id"><?PHP echo $table_id; ?></div>
</div>

<div id="page_header" class="clearfix">
	<h2 class="col_8"><a href="tables.php">Tables</a> <span class="divider">/</span> <?PHP echo $table_rows['name_uc']; ?></h2>
	<?PHP if($allow['add']){ ?><a id="add_item_button" class="button big color right add_header" href="edit.php?table=<?PHP echo $table_rows['name']; ?>">Add New Item</a><?PHP } ?>
</div>

<hr class="chubby">

<div class="table_actions clearfix">
	
		<?PHP 
		// If there is an active field, show status buttons and status filter dropdown
		if($table_rows['active']){ 
			// If user is allowed to change status show buttons
			if($allow['delete']){
				?>
				<ul id="status_actions">
					<li><a id="status_action_active" href="">Active</a></li>
					<li><a id="status_action_inactive" href="">Inactive</a></li>
					<li><a id="status_action_delete" href="">Delete</a></li>
				</ul>
				<?PHP 
			} 
			?>
	
			<ul id="view_options">
				<li>
					<a class="view_dropdown" href=""><span class="viewing">Viewing All</span> <span class="count">(0)</span><span class="ui-icon ui-icon-triangle-1-s arrow"></span></a>
					<ul>
						<li class="current"><a class="toggle_all" href="">View All <span class="count">(0)</span></a></li>
						<li><a class="toggle_active" href="#">View Active <span class="count">(0)</span></a></li>
						<li><a class="toggle_inactive" href="#">View Inactive <span class="count">(0)</span></a></li>
						<li><a class="toggle_deleted" href="#">View Trash</a></li>
					</ul>
				</li>
			</ul>
			<?PHP
		}
		?>

		<ul id="header_options">
			<li>
				<a class="header_dropdown" href="">Header Options<span class="ui-icon ui-icon-triangle-1-s arrow"></span></a>
				<ul>
					<?PHP
					//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					// Show dropdown for choosing headers
					
					$header_count = count(array_filter(explode(',', $table_rows['header_fields'])));
					foreach($table_rows['fields'] as $key => $field){
						if($settings['field_hidden'][$table_rows['name'].','.$field] != 'true'){
							$header_checked = ((!$table_rows['header_fields'] && $key<8) || $settings['field_primary'][$table_rows['name']] == $field || strpos($table_rows['header_fields'], ','.$field.',') !== false)? true : false;
							$disable_header = (!$header_checked && $header_count >= 8)? true : false;
							?>
							<li>
								<label for="field_<?PHP echo $field; ?>">
									<input id="field_<?PHP echo $field; ?>" name="field_<?PHP echo $field; ?>" class="header_option <?PHP echo ($settings['field_primary'][$table_rows['name']] == $field)?'primary_field':'';?>" type="checkbox" name="" value="" <?PHP echo ($settings['field_primary'][$table_rows['name']] == $field || $disable_header)? 'disabled="disabled"':''; ?> <?PHP echo ($header_checked)?'checked="checked"':'';?> >
									<?PHP echo uc_convert($field); ?>
								</label>
							</li>
							<?PHP
						}
					}
				
					//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					?>
				</ul>
			</li>
		</ul>

		<div id="live_filter_container">
			<label for="live_filter">Filter</label>
			<input name="live_filter" type="text" id="table_filter">
		</div>
			
</div>

<table id="browse_table" class="table actions" cellpadding="0" cellspacing="0" border="0" style="width:100%">
	<thead>
		<tr>
			<?PHP 
			// Get sort field and direction
			$field_order = explode(' ', $table_rows['sort_field']);
			$field_direction = ($field_order[1] == 'DESC')? 'headerSortUp' : 'headerSortDown';
			
			// Only show the handles if this table has an sort field
			if($table_rows['sort'] && $allow['reorder']){ 
				?><th sort="sort" class="header order <?PHP echo (!$field_order[0] || $field_order[0] == 'sort')? $field_direction : '';?>"><div class="wrap"><span class="ui-icon up"></span></div></th><?PHP 
			}
			
			// Only show the checkboxes if this table has an active field and is deletable by the user
			if($table_rows['active'] && $allow['delete']){ 
				?><th class="check"><?PHP if(count($table_rows['rows']) <= MAX_TABLE_REORDERABLE_ITEMS){ ?><input id="status_action_check_all" type="checkbox" name="" value=""><?PHP } ?></th><?PHP
			}
			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// Show column headers
			
			foreach($table_rows['fields'] as $key => $field){
				if($settings['field_hidden'][$table_rows['name'].','.$field] != 'true'){
					?>
					<th sort="<?PHP echo $field; ?>" class="header field_<?PHP echo $field; ?> <?PHP echo ($key == 0)? 'first_field':''; ?> <?PHP echo ($field_order[0] == $field)? $field_direction : '';?>" <?PHP echo ((!$table_rows['header_fields'] && $key<8) || $settings['field_primary'][$table_rows['name']] == $field || strpos($table_rows['header_fields'], ','.$field.',') !== false)?'':'style="display:none;"';?> >
						<div class="wrap">
							<?PHP 
							echo uc_convert($field);
							
							// Add mail icon for email fields
							echo ($settings["field_format"][$table_rows['name'].",".$field] == 'email')? ' <a href="#" field="'.$field.'" table="'.$table_rows['name'].'" class="generate_email_list"><img src="media/site/icons/mail-medium.png" width="16" height="16" /></a>':'';
							?>
							<span class="ui-icon up"></span>
						</div>
					</th>
					<?PHP
				}
			}
			
			//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			?>
		</tr>
	</thead>
	<tbody class="sortable check_no_rows">
		<?PHP show_browse_rows(); ?>
	</tbody>
</table>

<?PHP
require_once("inc/footer.php");
?>
