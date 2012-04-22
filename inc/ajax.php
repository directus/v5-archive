<?PHP
//////////////////////////////////////////////////////////////////////////////
// Setup without saving location

$setup_ajax = true;
require_once("setup.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Set active state

if($_POST['action'] == 'set_active'){
	
	if($_POST['table'] && $_POST['id'] !== false && $_POST['status'] !== false){
		
		//////////////////////////////////////////////////////////////////////////////
		// Get array of IDs to change and other variables
		
		$status_ids = explode(',',$_POST['id']);
		$status_ids = array_filter($status_ids);
		
		$total_count = count($status_ids);
		$success_count = 0;
		$error_count = 0;
		
		$table = $_POST['table'];
		$status = $_POST['status'];
		
		if($status == 1){
			$action = 'activated';
		} else if($status == 0){
			$action = 'deleted';
		} else {
			$action = 'deactivated';
		}
		
		//////////////////////////////////////////////////////////////////////////////
		// Update status for each item
		
		// Reminder: Compare against existing tables
		foreach($status_ids as $id){
			$sth = $dbh->prepare("UPDATE `$table` SET `active` = :active WHERE `id` = :id AND `active` != :active ");
			$sth->bindParam(':active', $status);
			$sth->bindParam(':id', $id);
			if( $sth->execute() ){
				
				// If the item was actually changed
				if($sth->rowCount() > 0){
				
					$success_count++;
			
					$id_safe = intval($id);
				
					// Save status change to revisions
					insert_activity($table = $table, $row = $id, $type = $action, $sql = "UPDATE `$table` SET `active` = '$status' WHERE `id` = '$id_safe' ");
				}
			} else {
				$error_count++;
			}
		}
		
		//////////////////////////////////////////////////////////////////////////////
		
		// Alert user of how many items were changed
		if($success_count > 0){
			$_SESSION['alert'] = "item_$action"."_$success_count";
		}
		
		// Alert user of how many items had errors
		if($error_count > 0){
			$plural = ($error_count == 1)?'':'s';
			echo "Error: $error_count/$total_count item$plural not $action";
		}
		
		//////////////////////////////////////////////////////////////////////////////
	}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Set sort state

} elseif($_POST['action'] == 'set_sort') {
	
	$i = 1;
	$table = $_POST['table'];
	
	// Loop through all items and set a new sort value
	// Reminder: Check table against table names
	foreach($_POST['item'] as $id) {
		$sth = $dbh->prepare("UPDATE `$table` SET `sort` = '$i' WHERE `id` = :id ");
		$sth->bindParam(':id', $id);
		if( $sth->execute() ){
			$i++;
		} else {
			$error = true;
			break;
		}
	}
	
	//////////////////////////////////////////////////////////////////////////////
	// Alert user as to results
	
	if($error){
		echo 'error_sorting';
	} else {
		echo 'items_reordered_'.count($_POST['item']);
	}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Set preference

} elseif($_POST['action'] == 'set_preference') {
	
	$value = ($_POST["value"])? $_POST["value"] : implode(",", str_replace('%', '_', $_POST['field']));	// Serialize doesnt allow underscores
	
	// For saving a database preference
	$sth = $dbh->prepare("SELECT * FROM `directus_preferences` WHERE `user` = :user AND `type` = :type AND `name` = :name ");
	$sth->bindParam(':user', $cms_user['id']);
	$sth->bindParam(':type', $_POST["type"]);
	$sth->bindParam(':name', $_POST["name"]);
	$sth->execute();
	if( $row = $sth->fetch() ){
		$sth = $dbh->prepare("UPDATE `directus_preferences` SET `name` = :name, `value` = :value WHERE `id` = :id ");
		$sth->bindParam(':name', $_POST["name"]);
		$sth->bindParam(':value', $value);
		$sth->bindParam(':id', $row['id']);
		if( $sth->execute() ){
			echo 'preference_saved';
		} else {
			echo 'preference_not_saved';
		}
	} else {
		$sth = $dbh->prepare("INSERT INTO `directus_preferences` SET `user` = :user, `type` = :type, `name` = :name, `value` = :value ");
		$sth->bindParam(':user', $cms_user['id']);
		$sth->bindParam(':type', $_POST["type"]);
		$sth->bindParam(':name', $_POST["name"]);
		$sth->bindParam(':value', $value);
		if( $sth->execute() ){
			echo 'preference_added';
		} else {
			echo 'preference_not_added';
		}
	}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Set session

} elseif($_POST['action'] == 'set_session') {

	if($_POST['session'] == "settings_open_table"){
		$_SESSION["settings_open_table"] = $_POST['value'];
	}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Get tag autocomplete values for this table/field combination

} elseif($_POST['action'] == 'tag_autocomplete') {

	$term = trim(strip_tags($_POST['term']));
	$table = clean_db_item($_POST['table']);
	$field = clean_db_item($_POST['field']);
	$tag_string = '';
	
	// Add all fields tags to one string
	foreach($dbh->query("SELECT $field FROM `$table` ORDER BY `$field` ASC ") as $row){
		$tag_string .= $row[$field];
	}
	
	// Get all tags from string
	$tags = array_unique(array_filter(explode(',', $tag_string)));
	sort($tags);
	
	// Tags that match the term
	$final_tags = array();
	$tag_count = 0;
	foreach($tags as $value){
		if(strpos($value, $term) !== false && $tag_count++ < 10){
			$final_tags[] = $value;
		}
	}
	
	echo json_encode($final_tags);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Add a table to the database
	
} elseif($_POST['action'] == 'add_table' && $cms_user['admin'] == 1) {
	
	$table = clean_db_item($_POST['table_name']);
	$active = ($_POST['table_active'])? ", `active` TINYINT(1) NOT NULL DEFAULT '1'" : "";
	$sort = ($_POST['table_sort'])? ", `sort` INT(10) NOT NULL DEFAULT '0'" : "";
		
	$sql = "CREATE TABLE IF NOT EXISTS `$table` (`id` INT(10) NOT NULL AUTO_INCREMENT, PRIMARY KEY(id)$active $sort)";
	
	if($dbh->query($sql)){
		// Success, so add to revision log
		if(insert_activity($table = $table, $row = 0, $type = 'structure', $sql = $sql)){
			$_SESSION['alert'] = "success_add_table";
		}
	} else {
		// Error
		$sql_error = print_r($dbh->errorInfo(), true);
		if(insert_activity($table = $table, $row = 0, $type = 'error', $sql = $sql . "\n\n\nError:\n" . $sql_error)){
			//
		}
		echo "error_add_table";
	}
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Add a field to a table

} elseif($_POST['action'] == 'add_field' && $cms_user['admin'] == 1) {
	
	$table = clean_db_item($_POST['table_name']);
	$field = clean_db_item($_POST['field_name']);
	$type = clean_db_item($_POST['field_type']);
	
	$length = intval($_POST['field_length']);
	$length = ($length > 0)? $length : 255;
	$length_sql = ($type == "VARCHAR" || $type == "TINYINT" || $type == "INT")? "($length)" : "";
	
	$default = $_POST['field_default'];
	$default_sql = ($default == "" || $type == "TINYTEXT" || $type == "TEXT" || $type == "MEDIUMTEXT" || $type == "LONGTEXT" || $type == "TINYBLOB" || $type == "BLOB" || $type == "MEDIUMBLOB" || $type == "LONGBLOB")? "" : "DEFAULT '$default'";
		
	$sql = "ALTER TABLE `$table` ADD `$field` $type $length_sql NOT NULL $default_sql";
	
	if($dbh->query($sql)){
		// Success, so add to revision log
		if(insert_activity($table = $table, $row = $field, $type = 'structure', $sql = $sql)){
			$_SESSION['alert'] = "success_add_field";
		}
	} else {
		// Error
		$sql_error = print_r($dbh->errorInfo(), true);
		if(insert_activity($table = $table, $row = $field, $type = 'error', $sql = $sql . "\n\n\nError:\n" . $sql_error)){
			//
		}
		echo "error_add_field";
	}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Reorder field

} elseif($_POST['action'] == 'reorder_field' && $cms_user['admin'] == 1) {
	
	$table = clean_db_item($_POST['table_name']);
	$field = clean_db_item($_POST['field_name']);
	$type = $_POST['field_type'];
	
	$table_info = get_rows_info($table);
	$first_field = ($table_info['active'])? 'active' : 'id';
	$first_field = ($table_info['sort'])? 'sort' : $first_field;
	$after = ($_POST['field_after'])? clean_db_item($_POST['field_after']) : $first_field;
	
	$sql = "ALTER TABLE `$table` MODIFY `$field` $type AFTER `$after`";

	if($dbh->query($sql)){
		// Success, so add to revision log
		if(insert_activity($table = $table, $row = $field, $type = 'structure', $sql = $sql)){
			//
		}
	} else {
		// Error
		$sql_error = print_r($dbh->errorInfo(), true);
		if(insert_activity($table = $table, $row = $field, $type = 'error', $sql = $sql . "\n\n\nError:\n" . $sql_error)){
			//
		}
		echo "error_reorder_field";
	}
	
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>