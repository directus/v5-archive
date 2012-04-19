<?PHP
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once("inc/setup.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

if($_GET['table'] || $_GET['table'] == 0){
	// If not table ID, get table ID from name
	$table_id = (array_search($_GET['table'], $tables) !== false)? array_search($_GET['table'], $tables) : $_GET['table'];
	
	// Bypass getting item data if we're creating a new item
	$get_id = (isset($_GET['item']))? $_GET['item'] : 'bypass';
	
	// Get table and row data
	$table_rows = get_rows($table_id, $get_id);
	
	// Set cleaned variables
	$table = $table_rows['name'];
	$id = $table_rows['item_id'];
	
	// New or Edit
	$has_id = ( is_numeric($id) && $id > 0 )? true : false;	
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get restrictions (If you shouldnt be here, redirect and warn)

$allow = get_permissions($table);
if(!$allow['view'] || $table_id === false){ $_SESSION['alert'] = "permissions_view"; header("Location: ".CMS_INSTALL_PATH."tables.php"); die(); }
if(!$allow['edit'] && $has_id){ $_SESSION['alert'] = "permissions_edit"; header("Location: ".CMS_INSTALL_PATH."tables.php"); die(); }
if(!$allow['add'] && !$has_id){ $_SESSION['alert'] = "permissions_add"; header("Location: ".CMS_INSTALL_PATH."tables.php"); die(); }
if(!$table_rows || ($has_id && count($table_rows['rows']) == 0)){ $_SESSION['alert'] = "permissions_exist"; header("Location: ".CMS_INSTALL_PATH."tables.php"); die(); }

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Check that no other users are editing this item

$other_user_id = array_search("edit.php?table=$table&item=$get_id", $occupied_pages);
if($other_user_id && $get_id != 'bypass'){
	$alert[] = "user_double_edit_".$cms_all_users[$other_user_id]['username'];
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Revert to revision

if(isset($_GET['revision'])) {
	$sth = $dbh->prepare("SELECT * FROM directus_activity WHERE `id` = :id AND `table` = :table AND `row` = :row LIMIT 1 ");
	$sth->bindParam(':id', $_GET['revision']);
	$sth->bindParam(':table', $table);
	$sth->bindParam(':row', $id);
	$sth->execute();
	if($revision = $sth->fetch()){
		
		// Reminder: Checksum of fields this table to ensure revision has same fields
		
		// Revert
		if($dbh->query($revision['sql'])){
			// Success, so add THIS reversion to revision log
			if(insert_activity($table = $table, $row = $id, $type = 'reverted', $sql = $revision['sql'])){
				// Success
				$_SESSION['alert'] = "success_revert";
				header("Location: ".CMS_INSTALL_PATH."edit.php?table=$table&item=$id");
				die();
			}
		} else {
			// Error
			$alert[] = "error_revert";
		}
	} else {
		$alert[] = "invalid_revert";
	}
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Save row

if(isset($_POST['saving'])) {
	
	$query_save  = ($has_id && $_POST['save_and'] != 'duplicate')? 'UPDATE ' : 'INSERT INTO ';
	$query_save .= "`$table` SET ";
	$save_array  = array();
	
	foreach($table_rows['fields'] as $field){
		$value = $_POST[$field];
		$value = (is_array($value))? ',' . implode(',',$value) . ',' : $value;	// For drop downs ARRAY
		$value = ($value == "NULL" || $value == ",NULL,")? '' : $value;			// For drop downs NULL
		$value = convert_smart_quotes($value);									// Removes some WORD style characters
		
		// Based on field settings:
		$value = ($settings['field_option'][$table.','.$field]['text_area']['no_nl2br'] == 'true' || $settings['field_format'][$table.','.$field] == 'wysiwyg')? $value : nl2br($value);
		$value = ($settings['field_option'][$table.','.$field]['text_area']['urls_to_links'] == 'true')? convert_urls_to_links($value) : $value;
		
		$value = str_replace("'", "\'", $value);
		
		$save_array[] = "`$field` = '$value'";
	}
	
	if($table_rows['active'] == '1'){
		$save_array[] = "`active` = '".$_POST['active']."'";
	}
	
	if($table_rows['sort'] == '1' && !$has_id && $_POST['save_and'] != 'duplicate'){
		// Get the next highest sort int
		$sth = $dbh->query("SELECT max(sort) AS max_sort FROM `$table` ");
		$next_sort = ($sort = $sth->fetch())? ($sort["max_sort"]+1) : 1;
		
		$save_array[] = "`sort` = '$next_sort'";
	} elseif($table_rows['sort'] == '1' && $has_id) {
		$save_array[] = "`sort` = '".$_POST['sort']."'";
	}
	
	$query_save  .= implode(", ", $save_array);
	$query_save  .= ($has_id && $_POST['save_and'] != 'duplicate')? " WHERE `id` = '$id' " : "";
	
	//die($query_save);
	
	if($dbh->query($query_save)){
		// Save in revisions
		$id = ($has_id)? $id : $dbh->lastInsertId();
		$type = ($has_id)? 'edited':'added';
		
		$query_save .= (!$has_id)? " WHERE `id` = '$id' " : "";
		$revision_sql = preg_replace('/INSERT INTO /', 'UPDATE ', $query_save, 1);
		
		insert_activity($table = $table, $row = $id, $type = $type, $sql = $revision_sql);
		
		// Saved
		if(!isset($_GET['modal'])){
			$_SESSION['alert'] = "saved";
			if($_POST['save_and'] == 'stay'){
				header("Location: ".CMS_INSTALL_PATH."edit.php?table=$table&item=$id");
			} elseif($_POST['save_and'] == 'add'){
				header("Location: ".CMS_INSTALL_PATH."edit.php?table=$table");
			} elseif(in_array($table,$settings['table_single'])) {
				header("Location: ".CMS_INSTALL_PATH."tables.php");
			} else {
				header("Location: ".CMS_INSTALL_PATH."browse.php?table=$table");
			}
			die();
		}
	}
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Add to fancy or modal edit window

if(isset($_GET['modal'])){
	if(isset($_POST['saving'])){
		//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Add this code to fancy relational
		
		$table_rows = get_rows($table_id, $id);
		?>
		<html>
			<head><title>Add or edit an item </title>
				<script src="inc/js/jquery.js" type="text/javascript"></script>
				<script src="inc/js/jquery-ui.js" type="text/javascript"></script>
				<script src="inc/js/directus.js" type="text/javascript"></script>
			</head>
			<body onLoad="top.modal_html_transfer('fancy_<?PHP echo $_GET['modal'];?>', <?PHP echo ($has_id)? $id : 'false';?>);">
				<span>
					Info: <?PHP print_r($table_rows); ?>
					  1: <?PHP echo $_GET['parent_table'];?>|
					  2: <?PHP echo $_GET['table'];?>|
					  3: <?PHP echo $_GET['modal'];?>|
					  4: <?PHP echo $id;?>|
				</span><br />
				<div id="modal_html">
					<table>
					<tbody>
					<?PHP
					
					$is_relational = ($settings['field_format'][$_GET['parent_table'].",".$_GET['modal']] == 'relational')? true : false;
					$row_relational = $settings['field_option'][$_GET['parent_table'].','.$_GET['modal']]['relational'];
					
					if($is_relational){
						
						$option_fields = explode(",", $row_relational["option"]);
						
						// Make visible option with multiple fields  (field1,field2)
						//$safe_value = str_replace('"', "&#34;", $ordered_value);
						unset($option_array);
						foreach($option_fields as $temp){
							$option_array[] = $table_rows['rows'][$id][$temp];
						}

						?>
						<tr class="item" replace_with="<?PHP echo $id;?>"> 
							<td class="order handle"><img src="media/site/icons/ui-splitter-horizontal.png" width="16" height="16" /></td> 
							<td>
								<div class="wrap">
									<?PHP echo implode($row_relational["option_glue"],$option_array);?>
									<input type="hidden" name="<?PHP echo $_GET['modal'];?>[]" value="<?PHP echo $id;?>">
									<a class="badge edit_fancy modal" href="edit.php?modal=<?PHP echo $_GET['modal'];?>&table=<?PHP echo $row_relational["add_from_table"];?>&parent_table=<?PHP echo $_GET['parent_table'];?>&item=<?PHP echo $id;?>">Edit</a>
								</div>
							</td> 
							<td width="10%"><a class="ui-icon ui-icon-close right remove_fancy" href=""></a></td> 
						</tr> 
						<?PHP
					}
					?>
					</tbody>
					</table>
				</div>
			</body>
		</html>
		<?PHP
	} else {
		//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Edit in modal window
		
		?>
		<div class="fog"></div> 
			
		<div class="modal_window" style="display:none;"> 
			<div class="modal_window_header" class="clearfix"> 
				<?PHP echo ($has_id)? 'Edit Item' : 'Add New Item'; ?>
				<?PHP echo (isset($_GET['relational_media']))? ' within ' . uc_convert($_GET['relational_media']) : ''; ?>
				<a class="close_modal" href=""></a> 
			</div>
			
			<form id="edit_form" name="edit_form" target="iframe" action="edit.php?modal=<?PHP echo $_GET['modal'];?>&table=<?PHP echo $_GET['table'];?>&parent_table=<?PHP echo $_GET['parent_table'];?><?PHP echo ($has_id)? '&item='.$id:'';?>" method="post">
			
			<input name="saving" type="hidden" value="true">
			
			<div class="modal_window_content">

					<div class="fieldset"> 
						
						<input name="active" type="hidden" value="1">
						
						<?PHP
						//////////////////////////////////////////////////////////////////////////////
						//////////////////////////////////////////////////////////////////////////////
						// Display all the fields
						
						generate_fields($table_rows);
						
						//////////////////////////////////////////////////////////////////////////////
						//////////////////////////////////////////////////////////////////////////////
						?>
						
					</div> 
					
			</div>
			
			<div class="modal_window_actions">
				<div class="pad_full_small">
					<input class="button color pill edit_save_button" type="button" value="Save Item"> 
					<span>or <a class="cancel cancel_modal" href="browse.php?table=<?PHP echo $table; ?>">Cancel</a></span>
				</div>
			</div>
			
			</form>	
			
		</div>
		<?PHP
	}
	
	
	
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// If we are editing an item regularly
	
} else {
	
	$action_title = ($has_id)? 'Editing Item' : 'Creating New Item';
	$cms_html_title = ($table_rows)? $action_title . ' in ' . $table_rows['name_uc'] : 'No such table';
	require_once("inc/header.php");
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	?>
	
	<div id="hidden_vars" style="display:none;">
		<div id="cms_table"><?PHP echo $table; ?></div>
		<div id="cms_table_id"><?PHP echo $table_id; ?></div>
		<div id="cms_id"><?PHP echo $get_id; ?></div>
	</div>
	
	<h2>
		<a href="tables.php">Tables</a> <span class="divider">/</span> 
		<?PHP 
		if(!in_array($table,$settings['table_single'])){ 
			?><a href="browse.php?table=<?PHP echo $table; ?>"><?PHP echo $table_rows['name_uc']; ?></a> <span class="divider">/</span> <?PHP echo $action_title; ?><?PHP 
		} else {
			echo 'Editing ' . $table_rows['name_uc']; 
		} 
		?>
	</h2> 
	
	<hr class="chubby"> 
	
	<div class="clearfix" style="position: relative;"> 
		
		<form id="edit_form" name="edit_form" action="edit.php?table=<?PHP echo $_GET['table'];?><?PHP echo ($has_id)? '&item='.$id:'';?>" method="post"> 
			
			<input name="saving" type="hidden" value="true">
			
			<div id="edit_sidebar"> 
				
				<div id="sidebar_sticky"> 
				
					<div id="edit_actions" class="item_module"> 
						<div class="item_module_title"> 
							Item Status
						</div>
						<?PHP
						// If there is a sort field then save it
						if($table_rows['sort'] == '1' && $has_id){
							?>
							<input name="sort" type="hidden" value="<?PHP echo $table_rows['rows'][$id]['sort'];?>">
							<?PHP
						}
						
						// If there is an active field show status options
						if($table_rows['active']){
							
							// Single item tables cant have items turned inactive
							if(!in_array($table,$settings['table_single'])){
							
								// Get default status of new items
								if(!$has_id){
									if(in_array($table,$settings['table_inactive_default'])) {
										$active = 2;
									} else {
										$active = 1;
									}
								} else {
									$active = $table_rows['rows'][$id]['active'];
								}
							} else {
								$active = 1;
							}
							
							if($allow['delete'] && !in_array($table,$settings['table_single'])){
								?>
								<div class="item_module_box section"> 
									<label for="6"><input id="6" name="active" type="radio" value="1" <?PHP echo ($active == '1')?'checked="checked"':'';?>> Active</label> 
									<label for="7"><input id="7" name="active" type="radio" value="2" <?PHP echo ($active == '2')?'checked="checked"':'';?>> Inactive</label> 
								</div>
								<?PHP
							} else {
								?>
								<input name="active" type="hidden" value="<?PHP echo $active; ?>">
								<?PHP
							}
						}
						?>
						<div class="item_module_box <?PHP echo ($has_id)?'section':'';?>" style="overflow:visible;">
							<div id="save_actions">
								<div id="save_button">
									<a save_and="return" class="edit_save_button now_activity" activity="saving" href="#">Save Item</a>
								</div>
								<div id="save_toggle">
									<span class="ui-icon white ui-icon-triangle-1-s"></span>
								</div>
								<div id="save_options">
									<ul>
										<li><a save_and="stay" class="edit_save_button now_activity" activity="saving" href="#">Save &amp; Stay</a></li>
										<?PHP
										if(!in_array($table,$settings['table_single'])){
											?>
											<li><a save_and="add" class="edit_save_button now_activity" activity="saving" href="#">Save &amp; Add</a></li>
											<?PHP
											if($has_id){
												?>
												<li><a save_and="duplicate" class="edit_save_button now_activity" activity="saving" href="#">Save as Copy</a></li>
												<?PHP
											}
										}
										?>
									</ul>
								</div>
							</div>
							<input id="save_and" name="save_and" type="hidden" value="return">
							<span> or <a class="cancel" href="browse.php?table=<?PHP echo $table; ?>">Cancel</a></span> 
						</div> 
						<?PHP
						// If there is an active field and the user is allowed, show delete button
						if($table_rows['active'] && $allow['delete']){
							// Single item tables cant have items deleted
							if(!in_array($table,$settings['table_single']) && $has_id){
								?>
								<div class="item_module_box"> 
									<a class="delete now_activity" activity="deleting" id="edit_page_delete" href="browse.php">Delete</a> <?PHP echo ($active == '0')?'<i class="quiet"> - Item is already in the trash</i>':'';?>
								</div>
								<?PHP
							}
						}
						?>
					</div> 
					
					
					<?PHP
					//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					// Revisions history
					
					if($has_id){
						$sth = $dbh->prepare("SELECT * FROM directus_activity WHERE `table` = :table AND `row` = :row ORDER BY `datetime` DESC ");
						$sth->bindParam(':table', $table);
						$sth->bindParam(':row', $id);
						$sth->execute();
						$revisions = $sth->fetchAll();
						?>
						<div id="edit_revisions" class="item_module toggle closed"> 
							<div class="item_module_title toggle"> 
								<div class="title">Item Revisions (<?PHP echo count($revisions);?>)<span class="item_module_toggle ui-icon down"></span></div> 
							</div> 
							<div class="item_module_box"> 
								<ul id="item_revisions" class="item_module_list">
									<?PHP
									foreach($revisions as $revision){
										?>
										<li><div class="<?PHP echo $revision['type']; ?>"><a class="revert_item" href="#" confirm_href="edit.php?table=<?PHP echo $table;?>&item=<?PHP echo $id;?>&revision=<?PHP echo $revision['id'];?>" title="<?PHP echo date('M j, Y @ g:i:s A',strtotime(gmt_datetime_to_local($revision['datetime'])));?>"><?PHP echo ucwords($revision['type']);?> <?PHP echo contextual_time(strtotime($revision['datetime']));?></a> <span class="username">by <?PHP echo $cms_all_users[$revision['user']]['username'];?></span></div></li>
										<?PHP
									}
									if(count($revisions) < 1){ echo 'No revisions'; }
									?>
								</ul> 
							</div>
						</div>
						<?PHP
					}
					
					//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					// Item messages
					
					if($has_id){
						$sth = $dbh->prepare("SELECT * FROM directus_messages WHERE `table` = :table AND `row` = :row ORDER BY `datetime` DESC ");
						$sth->bindParam(':table', $table);
						$sth->bindParam(':row', $id);
						$sth->execute();
						$messages = $sth->fetchAll();
						?>
						<div id="edit_messaging" class="item_module toggle closed">
							<div class="item_module_title toggle"> 
								<div class="title">Item Messages (<span id="item_message_count"><?PHP echo count($messages);?></span>) <span class="item_module_toggle ui-icon down"></span></div> 
							</div>
							<div class="item_module_box section">
								
								<!-- <form id="item_messaging" name="item_messaging" action="" method="post"> -->
								<label for="item_message" class="primary">Leave a message for this item</label><br>
								<textarea id="item_message" name="item_message" class="small" rows="4"></textarea>
								<input id="item_message_send" class="button pill" type="button" name="Send" value="Send" table="<?PHP echo $table;?>" row="<?PHP echo $id;?>">
								
							</div>
							<div class="item_module_box">
								<ul id="item_messages">
									<?PHP
									foreach($messages as $message){
										?>
										<li>
											<span class="item_message_user"><?PHP echo ($message['from'] == $cms_user['id'])? '<b>You</b>' : $cms_all_users[$message['from']]['username'];?> wrote:</span>
											<?PHP echo $message['message']; ?><!-- <a href="#">more</a> -->
											<span class="item_message_date"><?PHP echo contextual_time(strtotime($message['datetime']));?></span>
										</li>
										<?PHP
									}
									if(count($messages) < 1){ echo '<span id="no_item_messages">No messages</span>'; }
									?>
								</ul>
							</div>
						</div>
						<?PHP
					}
					
					//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					?>
					
				</div> 
				
			</div> 
		
			<div id="edit_main"> 
				<div class="fieldset"> 
					
					<?PHP
					//////////////////////////////////////////////////////////////////////////////
					//////////////////////////////////////////////////////////////////////////////
					// Display all the fields
					
					generate_fields($table_rows);
					
					//////////////////////////////////////////////////////////////////////////////
					//////////////////////////////////////////////////////////////////////////////
					?>
					
				</div> 
			</div> 
			
		</form> 
		
	</div>
		
	<?PHP
	require_once("inc/footer.php");
	
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
?>